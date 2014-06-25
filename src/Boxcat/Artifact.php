<?php
namespace Boxcat;

/**
* Artifact in project
*/
class Artifact
{
    /**
     * Stores files using GridFS 'files' is default collection for GridFs 
     * @var string
     */
    protected $collection = 'fs.files';
    /**
     * @var string
     */
    protected $id;

     /**
     * @var string
     */
    protected $filename;

    /**
     * @var string
     */
    protected $contentType;


     /**
     * @var timestamp
     */
    protected $uploadDate;

    /**
     * @var array
     */
    protected $metaData;

    /**
     * Get response id
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set response id
     * @param  string $title
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get filename
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set filename id
     * @param  string $filename
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    }


    /**
     * Get upload date
     * @return string
     */
    public function getUploadDate()
    {
        return $this->uploadDate;
    }

    /**
     * Set upload date
     * @param  string $uploadDate
     */
    public function setUploadDate($uploadDate)
    {
        $this->uploadDate = $uploadDate;
    }

    /**
     * Get content type
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * Set content type
     * @param  string $contentType
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

     /**
     * Get meta data
     * @return meta
     */
    public function getMetaData()
    {
        return $this->metaData;
    }

    /**
     * Set response meta data
     * @param  mixed meta
     */
    public function setMetaData($metaData)
    {
        $this->metaData = $metaData;
    }

    /**
     * Params we're going to use to create the response
     * @param  array $params
     */
    public function validate($params)
    {
        $v = new \Valitron\Validator($params);
        //expId already guaranteed by routing and _construct
        $v->rule('required', []);

        if($v->validate()) {
            return true;
        } else {
            return $v;
        }

    }

    /**
    * Save response to DB
    * @param  Database $db
    */
    public function save(Database $db) {
        //Doc to save to DB
        $doc = $this->toMongo();

        $path = '../files/'.basename($this->filename);

        //Copy file from server
        $this->copyfile($this->filename, $path);

        $finfo = new \finfo(FILEINFO_MIME);
        $type = $finfo->file($path);

        //Save new artifact
        $write = $db->storeFile(
            $path, 
            array(
                'filename' => $this->filename,
                'contentType' => $type,
                'metaData' => $this->metaData
            )
        );

        //Delete temporary file
        unlink($path);

        $this->id = (string) $write;

        return true;
    }

    /**
    * Load artifact from DB
    * @param  string $id
    * @param  Database $db
    */
    public function load($id, Database $db) {
        $doc = $db->findOne($this->collection,$id);

        $this->setId((string) $doc['_id']);
        $this->setFilename($doc['filename']);
        $this->setContentType($doc['contentType']);
        $this->setUploadDate($doc['uploadDate']->sec);
        $this->setMetaData($doc['metaData']);

        return true;
    }

    /**
    * Download artifact from DB
    * @param  string $id
    * @param  Database $db
    */
    public function download($id, Database $db) {
        return $db->findOneFile($id);
    }

    /**
    * delete artifact from DB
    * @param  string $id
    * @param  Database $db
    */
    public function delete($id, Database $db) {
        $db->removeFile($id);
        return true;
    }

    /**
    * Return response as array
    */
    public function toArray() {
        return array(
            'id' => $this->getId(),
            'filename' => $this->getFilename(),
            'contentType' => $this->getContentType(),
            'uploadDate' => $this->getUploadDate(),
            'metaData' => $this->getMetaData(),
        );
    }

    /**
    * Return as Mongo Doc ready for insert or update
    */
    public function toMongo() {
        //Get object as array
        $mongoDoc = $this->toArray();
        //Replace primitive types with MongoObjects where appropiate
        $mongoDoc['_id'] = new \MongoId($this->id);
        unset($mongoDoc['id']);
        $mongoDoc['uploadDate'] = new \MongoDate($this->getUploadDate());

        return $mongoDoc;
    }


    /**
    * Copy remote file over HTTP one small chunk at a time.
    * http://stackoverflow.com/questions/4000483/how-download-big-file-using-php-low-memory-usage
    * @param $infile The full URL to the remote file
    * @param $outfile The path where to save the file
    */
    private function copyfile($infile, $outfile) {
        $chunksize = 10 * (1024 * 1024); // 10 Megs

        /**
         * parse_url breaks a part a URL into it's parts, i.e. host, path,
         * query string, etc.
         */
        $parts = parse_url($infile);
        $i_handle = fsockopen($parts['host'], 80, $errstr, $errcode, 5);
        $o_handle = fopen($outfile, 'wb');

        if ($i_handle == false || $o_handle == false) {
            return false;
        }

        if (!empty($parts['query'])) {
            $parts['path'] .= '?' . $parts['query'];
        }

        /**
         * Send the request to the server for the file
         */
        $request = "GET {$parts['path']} HTTP/1.1\r\n";
        $request .= "Host: {$parts['host']}\r\n";
        $request .= "User-Agent: Mozilla/5.0\r\n";
        $request .= "Keep-Alive: 115\r\n";
        $request .= "Connection: keep-alive\r\n\r\n";
        fwrite($i_handle, $request);

        /**
         * Now read the headers from the remote server. We'll need
         * to get the content length.
         */
        $headers = array();
        while(!feof($i_handle)) {
            $line = fgets($i_handle);
            if ($line == "\r\n") break;
            $headers[] = $line;
        }

        /**
         * Look for the Content-Length header, and get the size
         * of the remote file.
         */
        $length = 0;
        foreach($headers as $header) {
            if (stripos($header, 'Content-Length:') === 0) {
                $length = (int)str_replace('Content-Length: ', '', $header);
                break;
            }
        }

        /**
         * Start reading in the remote file, and writing it to the
         * local file one chunk at a time.
         */
        $cnt = 0;
        while(!feof($i_handle)) {
            $buf = '';
            $buf = fread($i_handle, $chunksize);
            $bytes = fwrite($o_handle, $buf);
            if ($bytes == false) {
                return false;
            }
            $cnt += $bytes;

            /**
             * We're done reading when we've reached the conent length
             */
            if ($cnt >= $length) break;
        }

        fclose($i_handle);
        fclose($o_handle);
        return $cnt;
    }

}
