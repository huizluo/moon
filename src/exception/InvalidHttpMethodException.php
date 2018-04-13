<?php
/**
 *
 * User: PC
 * Date: 2018/4/12
 * Time: 15:12
 */
namespace moon\exception;
use Psr\Http\Message\ServerRequestInterface;


class InvalidHttpMethodException extends \InvalidArgumentException{

    protected $request;

    public function __construct(ServerRequestInterface $request,$method)
    {
        $this->request = $request;
        parent::__construct(sprintf('Unsupported HTTP method "%s" provided', $method));
    }

    public function getRequest(){
        return $this->request;
    }
}