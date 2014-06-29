<?php
require '../bootstrap.php';

$config = require '../config/global.php';
$db = new \Boxcat\Database($config['Mongo']['uri']);
$app = new \Slim\Slim($config['Slim']);
$app->add(new \Middleware\Auth());
$app->view(new \View\Json());
$app->add(new \Middleware\JsonMiddleware());
$app->add(new \Slim\Middleware\ContentTypes());

$app->get('/', function () use ($app) {
    $app->render(200,array('message' => "Boxcat is **ONLINE** meow"));
});


/****
* Project routes
*/

$app->options('/project', function () use ($app) {
    $app->render(200);
});

$app->post('/project', function () use ($app,$db) {

    $request = $app->request();

    $params = $request->getBody();

    $project = new \Boxcat\Project();

    $valid = $project->validate($params);

    if ($valid === true) {
        //Array map converts MongoId's to string
        $project->setAccess((int) $params['access']);
        $project->setArtifact(array_map('strval', $params['artifact']));

        $project->save($db);

        $data = $project->toArray();

        $app->render(200,array('data' => $data));
    }
    else {
        $errors = $valid->errors();
        $app->render(400,array('error' => true,'errors' => $errors));
    }
});


$app->get('/project',  function () use ($app,$db) {

    $projects = new \Boxcat\Projects();

    $p = $projects->load($db);

    $data = array();
    foreach ($p as $project) {
        $data[] = $project->toArray();
    }

    $app->render(200,array('data' => $data));
});



$app->options('/project/:pId', function () use ($app) {
    $app->render(200);
});


$app->get('/project/:pId',  function ($pId) use ($app,$db) {

    $project = new \Boxcat\Project();

    $project->load($pId,$db);

    $data = $project->toArray();

    $app->render(200,array('data' => $data));
});


$app->put('/project/:pId',  function ($pId) use ($app,$db) {

    $request = $app->request();
    $params = $request->getBody();

    $project = new \Boxcat\Project();

    $project->load($pId,$db);

    if(isset($params['owner'])) {
        $project->setOwner($params['owner']);
    }

    if(isset($params['access'])) {
        $project->setAccess($params['access']);   
    }

    $project->save($db);

    $data = $project->toArray();
    $app->render(200,array('data' => $data));


});


$app->delete('/project/:pId',  function ($pId) use ($app,$db) {

    $project = new \Boxcat\Project();
    $project->load($pId,$db);
    $data = $project->delete($db);

    $app->render(200,array('data' => $data));

});


$app->get('/project/:pId/artifact', function ($pId) use ($app,$db) {

    $project = new \Boxcat\Project();
    $project->load($pId,$db);

    $p = $project->toMongo();
    $artifacts = new \Boxcat\Artifacts();
    $a = $artifacts->load($p['artifact'],$db);

    $data = array();
    foreach ($a as $artifact) {
        $data[] = $artifact->toArray();
    }

    $app->render(200,array('data' => $data));
});



$app->post('/project/:pId/artifact', function ($pId) use ($app,$db) {

    $request = $app->request();

    $params = $request->getBody();

    $artifact = new \Boxcat\Artifact();

    $project = new \Boxcat\Project();
    $project->load($pId,$db);

    $valid = $artifact->validate($params);

    if ($valid === true) {

        $artifact->setFilename($params['filename']);
        $artifact->setMetaData($params['metaData']);

        $artifact->save($db);

        $id = $artifact->getId();

        $project->addArtifact($id,$db);

        $data = $artifact->toArray();

        $app->render(200,array('data' => $data));
    }
    else {
        $errors = $valid->errors();
        $app->render(400,array('error' => true,'errors' => $errors));
    }
});

/*
$app->put('/project/:pId/artifact/:aId', function ($pId,$aId) use ($app,$db) {

    $project = new \Boxcat\Project();
    $artifact = new \Boxcat\Artifact();

    //Load project to add artifact to
    $project->load($pId,$db);
    //Load artifact to check exists
    $artifact->load($aId,$db);

    $project->addArtifact($artifact->getId(),$db);

    $app->render(200,array('data' => array('pId' => $pId,'aId' => $aId )));
    

});
*/

$app->get('/project/:pId/artifact/:aId', function ($pId,$aId) use ($app,$db) {
    
    $project = new \Boxcat\Project();
    $project->load($pId,$db);


    if (!in_array($aId, $project->getArtifact())) {
        throw new Exception('Invalid artifact Id.');
    }

    $artifact = new \Boxcat\Artifact();
    $artifact->load($aId,$db);

    $data = $artifact->toArray();

    $app->render(200,array('data' => $data));
});

$app->delete('/project/:pId/artifact/:aId',  function ($pId,$aId) use ($app,$db) {

    $project = new \Boxcat\Project();

    //Load project to remove artifact from
    $project->load($pId,$db);

    $data = $project->removeArtifact($aId,$db);

    $app->render(200,array('data' => $data));

});


$app->get('/project/:pId/artifact/:aId/download', function ($pId,$aId) use ($app,$db) {
    
    $project = new \Boxcat\Project();
    $project->load($pId,$db);

    if (!in_array($aId, $project->getArtifact())) {
        throw new Exception('Invalid artifact Id.');
    }

    $artifact = new \Boxcat\Artifact();
    $file = $artifact->download($aId,$db);

    header('Content-Type:' . $file->file['contentType']);

    $stream = $file->getResource();
    while (!feof($stream)) {
        echo fread($stream, 8192);
    }
    exit;

});


/**
* Artifact routes
*/

/*
$app->options('/artifact', function () use ($app) {
    $app->render(200);
});

$app->post('/artifact', function () use ($app,$db) {

    $request = $app->request();

    $params = $request->getBody();

    $artifact = new \Boxcat\Artifact();

    $valid = $artifact->validate($params);

    if ($valid === true) {

        $artifact->setFilename($params['filename']);
        $artifact->setMetaData($params['metaData']);

        $artifact->save($db);

        $id = $artifact->getId();

        $app->render(200,array('data' => array('id' => $id )));
    }
    else {
        $errors = $valid->errors();
        $app->render(400,array('error' => true,'errors' => $errors));
    }
});


$app->delete('/artifact/:aId',  function ($aId) use ($app,$db) {

    $artifact = new \Boxcat\Artifact();

    $data = $artifact->delete($aId,$db);

    $app->render(200,array('data' => $data));

});
*/

$app->run();
