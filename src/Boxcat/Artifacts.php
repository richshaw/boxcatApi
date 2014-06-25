<?php
namespace Boxcat;

/**
* Loads multiple timed response experiments
*/
class Artifacts
{

    /**
     * Stores files using GridFS 'files' is default collection for GridFs 
     * @var string
     */
    protected $collection = 'fs.files';

    /**
    * Load multiple artifacts from DB
    * @param  Database $db
    */
    public function load($ids, Database $db) {

        $query = array('_id' => array('$in' => $ids));
        $cursor = $db->find($this->collection,$query);

        $artifacts = array();
        foreach ($cursor as $doc) {
            $artifact = new \Boxcat\Artifact();

            $artifact->setId((string) $doc['_id']);
            $artifact->setFilename($doc['filename']);
            $artifact->setContentType($doc['contentType']);
            $artifact->setUploadDate($doc['uploadDate']->sec);
            $artifact->setMetaData($doc['metaData']);

            $artifacts[] = $artifact;
        }

        return $artifacts;
    }


    /**
    * Save multiple artifacts to DB
    * @param  Database $db
    */
    /*
    public function save(array $docs,Database $db) {
        //Save new responses
        $db->batchInsert($this->collection,$docs);
        return true;
    }
    */
    /**
    * Delete all artifacts from DB
    * @param  Database $db
    */
    /*
    public function delete(Database $db) {
        //Save new experiment
        $db->removeAll($this->collection);
        return true;
    }
    */
}
