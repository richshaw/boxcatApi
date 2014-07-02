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

        //Check auth before routing
        $app->hook('slim.before.router', function () use ($app) {
    
            //get authorisation header - this works for Apache, but you may have to customise this if you are using another platform.  If you are using a framework like Zend or Symfony, it might have inbuilt methods for getting hold of the headers, e.g. $request->getHeader('Authorization') for Zend
            $authorizationHeader = $app->request->headers->get('Authorization');

            //No auth 
            if(!isset($authorizationHeader)) {
                $app->render(401,array(
                    'error' => TRUE,
                    'msg'   => 'Unauthorized',
                ));
            }


            //Check auth
            if(isset($authorizationHeader)) {
                // validate the token
                $token = str_replace('Bearer ', '', $authorizationHeader);
                $secret = "DlMhBLdO4Qt9hIQ4lyHrEuqcrVrvQL_00OX4ekZM3BxbxDEubOPUsNW-_9dLalCO";

                //Attmpt JWT decode
                try {
                    $decoded_token = \JWT::decode($token, base64_decode(strtr($secret, '-_', '+/')) );
                } catch (Exception $e) {
                    
                    $app->render(401,array(
                        'error' => TRUE,
                        'msg'   => 'Unauthorized',
                    ));

                }
                
                // validate that this token was made for us
                if ($decoded_token->aud != "ePe1RLcew10DOzkZFB728pTEigpCCExb") {
                    $app->render(401,array(
                        'error' => TRUE,
                        'msg'   => 'Unauthorized',
                    ));
                }
            }

        });

    }

    /**
     * Call next with added auth
     */
    function call(){
        return $this->next->call();
    }

}
