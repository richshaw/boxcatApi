<?php
namespace Boxcat;

/**
* Boxcat Project
*/
class Project
{

     /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $collection = 'project';

    /**
     * @var timestamp
     */
    protected $created;

    /**
     * @var int
     * Access setting 1 = invite only, 2 = link
     */
    protected $access = 1;

    /**
     * @var array
     * Artfact ID's of files
     */
    protected $artifact = array();

    /**
     * Get experiment id
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set experiment id
     * @param  string $title
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get created timestamp
     * @return created
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set created
     * @param  int access
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }


    /**
     * Get access type
     * @return access
     */
    public function getAccess()
    {
        return $this->access;
    }

    /**
     * Set access type
     * @param  int access
     */
    public function setAccess($access)
    {
        $this->access = $access;
    }

    /**
     * Get artifact Id's
     * @return artifact
     */
    public function getArtifact()
    {
        return $this->artifact;
    }

    /**
     * Set artifact Id's
     * @param  array edit
     */
    public function setArtifact($artifact)
    {
        $this->artifact = $artifact;
    }

    /**
     * Params we're going to use to create the experiment
     * @param  mixed $params
     */
    public function validate($params)
    {
        $v = new \Valitron\Validator($params);
        $v->rule('required', array('access'));
        $v->rule('numeric',array('access'));

        if($v->validate()) {
            return true;
        } else {
            return $v;
        }

    }

    /**
    * Save project to DB
    * @param  mixed $params
    * @param  Database $db
    */
    public function save(Database $db) {
        //Doc to save to DB
        $doc = $this->toMongo();

        if(!isset($this->id)) {
            //Save new experiment
            $write = $db->insert($this->collection,$doc);
            $this->setId((string) $write['_id']);
            $this->setCreated($write['created']->sec);
            $this->setAccess($write['access']);
            $this->setArtifact($write['artifact']);
        }
        else {
            //Update existing experiment
            //Don't change id
            unset($doc['_id']);
            //Don't change created
            unset($doc['created']);
            $write = $db->update($this->collection,$this->id,$doc);
        }
        return true;
    }

    /**
    * Load project from DB
    * @param  string $id
    * @param  Database $db
    */
    public function load($id, Database $db) {

        $doc = $db->findOne($this->collection,$id);

        if($doc == NULL) {throw new \Exception('Invalid project Id.');}

        $this->setId((string) $doc['_id']);
        $this->setCreated($doc['created']->sec);
        //Array walk converts MongoId's to string
        $this->setAccess($doc['access']);
        array_walk_recursive($doc['artifact'],array($this, 'toStrVal'));
        $this->setArtifact($doc['artifact']);

        return true;
    }

    /**
    * delete project to DB
    * @param  Database $db
    */
    public function delete(Database $db) {
        $db->remove($this->collection,$this->id);
        //Delete files associated with project 
        //Can only be done a loop which is kind of shitty
        foreach ($this->artifact as $aId) {
            $db->removeFile($aId);
        }

        return true;
    }

    /**
    * Add artifact to project
    * @param  string $id artifact Id
    * @param  Database $db
    */
    public function addArtifact($id, Database $db) {

        $doc = array(
           'artifact' => new \MongoId($id)
        );

        $write = $db->push($this->collection,$this->id,$doc);

        return true;
    }

    /**
    * Remove artifact to project
    * @param  string $id artifact Id
    * @param  Database $db
    */
    public function removeArtifact($id, Database $db) {

        $doc = array(
           'artifact' => new \MongoId($id)
        );
        //Remove artifact reference from project
        $write = $db->pull($this->collection,$this->id,$doc);
        //Delete artifact
        $db->removeFile($id);

        return true;
    }



    /**
    * Return project as array
    */
    public function toArray() {
        return array(
            'id' => $this->getId(),
            'created' => $this->getCreated(),
            'access' => (int) $this->getAccess(),
            'artifact' => $this->getArtifact()
        );
    }

    /**
    * Return project as Mongo doc
    */
    public function toMongo() {
        //Get object as array
        $mongoDoc = $this->toArray();
        unset($mongoDoc['id']);
        //Replace primitive types with MongoObjects where appropiate
        if($this->id)
        {
            $mongoDoc['_id'] = new \MongoId($this->id);
        }

        if($this->created) {
            $mongoDoc['created'] = new \MongoDate($this->getCreated());
        }

        if(!empty($mongoDoc['artifact'])) {
            $mongoDoc['artifact'] = array_map(array($this,'toMongoId'), $mongoDoc['artifact']);
        }

        return $mongoDoc;
    }

    /**
    * Convert string to Mongo ID obect
    */
    private function toMongoId($id) {
        return new \MongoId($id);
    }

    /**
    * Converts val to string
    * Used with array_walk_recursive($doc['artifact'],array($this, 'toStrVal'))
    */
    public function toStrVal(&$val) {
        $val =  strval($val); 
    }
}
