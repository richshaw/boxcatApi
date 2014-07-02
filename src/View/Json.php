<?php
/**
 * JsonApiView - view wrapper for json responses (with error code).
 */

namespace View;

class Json extends \JsonApiView  {

    public function render($status=200, $data = NULL) {
        $app = \Slim\Slim::getInstance();

        $status = intval($status);

        $response = $this->all();

        //append error bool
        if (!$this->has('error')) {
            $response['error'] = false;
        }

        //append status code
        $response['status'] = $status;

        //add flash messages
        if(isset($this->data->flash) && is_object($this->data->flash)){
            $flash = $this->data->flash->getMessages();
            if (count($flash)) {
                $response['flash'] = $flash;
            } else {
                unset($response['flash']);
            }
        }

        //Add jsonp is requirws
        $callback = $app->request()->get('callback');

        //If the JSONP callback parameter is set then wrap the response body in the original
        //callback string.
        if(!empty($callback)){
            $json_response = htmlspecialchars($callback) . "(" . json_encode($response) . ")";
        }
        else {
            $json_response = json_encode($response);
        }

        $app->response()->setStatus($status);

        $app->response()->header('Content-Type', 'application/json');

        $app->response()->header('Access-Control-Allow-Origin', '*');
        $app->response()->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $app->response()->header('Access-Control-Allow-Headers', 'Origin, Content-Length, Content-Type, X-Response-Auth-Token, X-Requested-With');

        $app->response()->header('Access-Control-Max-Age', '86400');

        $app->response()->body($json_response);

        $app->stop();
    }

}
