<?php
/**
 * Created by PhpStorm.
 * User: PC
 * Date: 2018/4/13
 * Time: 13:18
 */
namespace moon\http;

use moon\Collection;
use moon\http\interfaces\EnvInterface;

class Env extends Collection implements EnvInterface {

    public static function mock(array $data = [])
    {
        if ((isset($data['HTTPS']) && $data['HTTPS'] !== 'off') ||
            isset($data['REQUEST_SCHEME']) && $data['REQUEST_SCHEME'] ==='https'){
            $scheme = 'https';
            $port = 443;
        }else{
            $scheme = 'http';
            $port = 80;
        }

        $config = array_merge([
            'SERVER_PROTOCOL'      => 'HTTP/1.1',
            'REQUEST_METHOD'       => 'GET',
            'REQUEST_SCHEME'       => $scheme,
            'SCRIPT_NAME'          => '',
            'REQUEST_URI'          => '',
            'QUERY_STRING'         => '',
            'SERVER_NAME'          => 'localhost',
            'SERVER_PORT'          => $port,
            'HTTP_HOST'            => 'localhost',
            'HTTP_ACCEPT'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.8',
            'HTTP_ACCEPT_CHARSET'  => 'ISO-8859-1,utf-8;q=0.7,*;q=0.3',
            'HTTP_USER_AGENT'      => 'Slim Framework',
            'REMOTE_ADDR'          => '127.0.0.1',
            'REQUEST_TIME'         => time(),
            'REQUEST_TIME_FLOAT'   => microtime(true),
        ],$data);


        return new self($config);
    }

}