<?php
/**
 * Created by PhpStorm.
 * User: PC
 * Date: 2018/4/12
 * Time: 15:21
 */
namespace moon\exception;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MoonException extends \Exception{

    protected $request;
    protected $response;

    public function __construct(ServerRequestInterface $request,ResponseInterface $response)
    {
        $this->request = $request;
        $this->response = $response;
        parent::__construct();
    }

    /**
     * Get request
     *
     * @return ServerRequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get response
     *
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }
}