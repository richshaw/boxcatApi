<?php

namespace Boxcat;

class Database
{

    public $db = NULL;

    public $mongoUri;

    public function __construct($mongoUri)
    {
        $this->connect($mongoUri);
    }


    public function __destruct()
    {
        $this->disconnect();
    }


    public function connect($mongoUri, $retry = 3)
    {
        try {
            $m = new \MongoClient($mongoUri);
            $url = parse_url($mongoUri);
            $dbName = preg_replace('/\/(.*)/', '$1', $url['path']);
            $this->db = $m->selectDB($dbName);
            return $this->db;
        } catch ( MongoConnectionException $e ) {
            //TODO Log exception
            //die('Error connecting to MongoDB server');
        } catch ( MongoException $e ) {
            die('Mongo Error: ' . $e->getMessage());
        } catch ( Exception $e ) {
            die('Error: ' . $e->getMessage());
        }

        //Automatically retry connection on fail
        if ($retry > 0) {
            return getMongoClient($seeds, $options, --$retry);
        }

        //The connection is definately fucked
        die('Error connecting to MongoDB server');
    }

    public function disconnect()
    {
        if ($this->db === null)
        {
            $this->db->close();
            $this->db = null;
        }

        return $this->db;
    }


    public function insert($collection, $document)
    {
        if(!isset($document['created']))
        {
            //Add timestamp if not given
            $document['created'] = new \MongoDate(strtotime('now'));
        }
        else
        {
            //Make MongoObject
            $document['created'] = new \MongoDate($document['created']);
        }

        $this->db->$collection->insert($document);
        return $document;
    }

    public function batchInsert($collection, $documents)
    {
        $this->db->$collection->batchInsert($documents);
        return $documents;
    }


    public function update($collection, $id, $document)
    {
        $update = array('$set' => $document);
        $this->db->$collection->update(array('_id' => new \MongoId($id)),$update,array('w' => 1));
        return $document;
    }

    public function push($collection, $id, $document)
    {
        $update = array('$push' => $document);
        $this->db->$collection->update(array('_id' => new \MongoId($id)),$update,array('w' => 1));
        return $document;
    }

    public function pull($collection, $id, $document)
    {
        $update = array('$pull' => $document);
        $this->db->$collection->update(array('_id' => new \MongoId($id)),$update,array('w' => 1));
        return $document;
    }

    public function findOne($collection, $id)
    {
        return $this->db->$collection->findOne(array('_id' => new \MongoId($id)));
    }

    public function find($collection, $query = array())
    {
        return $this->db->$collection->find($query);
    }

    public function remove($collection, $id)
    {
        return $this->db->$collection->remove(array('_id' => new \MongoId($id)));
    }

    public function removeAll($collection)
    {
        return $this->db->$collection->remove();
    }

    public function createCollection($collection)
    {
        return $this->db->createCollection($collection);
    }

    public function dropCollection($collection)
    {
        return $this->db->$collection->drop();
    }

    public function storeFile($filename, $metaData)
    {
        $gridfs = $this->db->getGridFS();
        //Returns file ID
        return $gridfs->storeFile($filename, $metaData);   
    }

    public function removeFile($id)
    {
        $gridfs = $this->db->getGridFS();
        //Returns file ID
        return $gridfs->remove(array('_id' => new \MongoId($id)));   
    }

    public function findOneFile($id)
    {
        $gridfs = $this->db->getGridFS();
        //Returns file ID
        return $gridfs->findOne(array('_id' => new \MongoId($id)));  
    }

}
