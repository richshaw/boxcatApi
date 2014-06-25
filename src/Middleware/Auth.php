<?php

namespace Middleware;

class Auth extends \Slim\Middleware
{
    public function call()
    {
        $req = $this->app->request();
        $res = $this->app->response();
        $key = $req->headers('Response-Key');
        $hash = $req->headers('Response-Hash');
        $params = $req->getBody();

        if ($this->authenticate($key,$hash,$params)) {
            $this->next->call();
        } else {
            $res->status(401);
        }
    }


    public function authenticate($key,$hash,$params) {
        //TODO auth function
        return true;
    }
}
