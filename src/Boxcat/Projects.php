<?php

namespace Boxcat;

/**
* Loads multiple projects
*/
class Projects
{

    /**
     * @var string
     */
    protected $collection = 'project';

    /**
    * Load multuple experiments from DB
    * @param  Database $db
    */
    public function load(Database $db) {
        $cursor = $db->find($this->collection);

        $projects = array();
        foreach ($cursor as $doc) {
            $project = new \Boxcat\project();

            $project->setId((string) $doc['_id']);
            $project->setCreated($doc['created']->sec);
            $project->setAccess($doc['access']);
            $project->setArtifact(array_walk_recursive($doc['artifact'],array($project, 'toStrVal')));

            $projects[] = $project;
        }

        return $projects;
    }


}
