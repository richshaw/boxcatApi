<?php
/**
 * JsonApiMiddler - MiddleWare wrapper with better error handling.
 */

namespace Middleware;

class JsonMiddleware extends \JsonApiMiddleware {


    /**
     * Sets a buch of static API calls
     *
     */
    function __construct(){

        $app = \Slim\Slim::getInstance();

        // Mirrors the API request
        $app->get('/return', function() use ($app) {

            $app->render(200,array(
                'method'    => $app->request()->getMethod(),
                'name'      => $app->request()->get('name'),
                'headers'   => $app->request()->headers(),
                'params'    => $app->request()->params(),
            ));
        });

        // Generic error handler
        $app->error(function (\Exception $e) use ($app) {

            if($app->config('mode') === 'dev') {
                $app->render(500,array(
                    'error' => true,
                    'msg'   => \JsonApiMiddleware::_errorType($e->getCode()) .": ". $e->getMessage() . " (File: " . $e->getFile() . " Line: " . $e->getLine() . ")",
                ));
            }
            else {
                $app->render(500,array(
                    'error' => true,
                    'msg'   => \JsonApiMiddleware::_errorType($e->getCode()) .": ". $e->getMessage(),
                ));
            }
        });

        // Not found handler (invalid routes, invalid method types)
        $app->notFound(function() use ($app) {
            $app->render(404,array(
                'error' => TRUE,
                'msg'   => 'Invalid route',
            ));
        });

        // Handle Empty response body
        $app->hook('slim.after.router', function () use ($app) {
            //Fix sugested by: https://github.com/bdpsoft
            //Will allow download request to flow
            if($app->response()->header('Content-Type')==='application/octet-stream'){
                return;
            }

            if (strlen($app->response()->body()) == 0) {
                $app->render(500,array(
                    'error' => TRUE,
                    'msg'   => 'Empty response',
                ));
            }
        });

    }

}
