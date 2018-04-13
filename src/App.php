<?php
/**
 * author: lazar
 * Date: 2018/4/11
 * Time: 13:45
 */
namespace moon;

use \DI\ContainerBuilder;
use \FastRoute\RouteCollector;
use \FastRoute\Dispatcher;
use moon\http\Env;
use moon\http\Headers;
use moon\http\Request;
use moon\http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class App{

    const  VERSION = '1.0';
    /*
     * object provider pool
     * */
    protected $pool;

    /***
     * dispatcher
     * @var Dispatcher
     * */
    protected $dispatcher;

    public function __construct($config = [])
    {
        if (is_array($config)){
            $containerBuilder = new ContainerBuilder();
            $containerBuilder->addDefinitions($config);
            $this->pool = $containerBuilder->build();
        }else{
            throw new \InvalidArgumentException('Init Pool Error,entry params type invalid');
        }
    }

    public function getPool(){
        return $this->pool;
    }

    public function get($pattern,$callable){
        return $this->map(['GET'],$pattern,$callable);
    }

    public function post($pattern, $callable)
    {
        return $this->map(['POST'], $pattern, $callable);
    }

    public function map(array $methods, $pattern, $callable)
    {

        $this->dispatcher = \FastRoute\simpleDispatcher(function (RouteCollector $r){
            $r->addRoute('GET','/',function (){
                return '__invoke';
            });
            $r->addRoute('GET','/arts',['app\blog\controller\ArticleController','index']);
            $r->addRoute('GET','/art','app\blog\controller\ArticleController');
        },[]);

        return true;
    }

    public function run(){
        $response = new Response(200,new Headers(['Content-Type' => 'text/html; charset=UTF-8']));
        $env = new Env($_SERVER);

        $request = Request::createFromEnvironment($env);

        $this($response,$request);
    }

    public function __invoke(ResponseInterface $response,ServerRequestInterface $request)
    {

//        $httpMethod = $_SERVER['REQUEST_METHOD'];
//        $uri = $_SERVER['REQUEST_URI'];
//        $time = $_SERVER['REQUEST_TIME'];
//
//        $params = '';
//        if (false!==$pos = strpos($uri,'?')){
//            $params = substr($uri,$pos+1);
//            $uri = substr($uri,0,$pos);
//        }
//
//        $uri = rawurldecode($uri);


        //var_dump($request->getMethod());
        $routeInfo = $this->dispatcher->dispatch($request->getMethod(), (string)$request->getUri());

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                // ... 404 Not Found
                print_r('404');
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                // ... 405 Method Not Allowed
                break;
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                // ... call $handler with $vars
                //        echo $handler($vars);
                var_dump($this->pool->call($handler,$vars))  ;
                break;
        }
    }
}