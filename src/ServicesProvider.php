<?php
/**
 *default service provider
 * router request response callableResolver errorHandle etc
 */
namespace moon;

use DI\Container;
use moon\error\Error;
use moon\error\NotAllowed;
use moon\error\NotFound;
use moon\error\PhpError;
use moon\handlers\RequestResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use moon\http\Env;
use moon\http\Headers;
use moon\http\Request;
use moon\http\Response;
use moon\router\Router;
use Psr\Container\ContainerInterface;



class ServicesProvider{

    public function register(&$config){

        if(!isset($config['env'])){
            $config['env'] = function (){
                return new Env($_SERVER);
            };
        }
        if (!isset($config['router'])){
            $config['router'] = function (ContainerInterface $container){
              $router = new Router();
              $router->setCacheFile($container->get('settings')['routerCacheFile']);
              if (method_exists($router,'setContainer')){
                    $router->setContainer($container);
              }

              return $router;
            };
        }
        if (!isset($config['errorHandler'])){

            $config['errorHandler'] = function (){
                return new Error();
            };
        }
        if (isset($config['phpErrorHandler'])){

            $config['phpErrorHandler'] = function (ContainerInterface $container){
                return new PhpError($container->get('settings')['showErrorDetails']);
            };

        }
        if (!isset($config['notFoundHandler'])){

            $config['notFoundHandler'] = function (){
                return new NotFound();
            };
        }
        if (!isset($config['notAllowedHandler'])){

            $config['notAllowedHandler'] = function (){
              return new NotAllowed();
            };
        }
        if (!isset($config['request'])){

            /**
             * PSR-7 Request object
             *
             * @param Container $container
             *
             * @return ServerRequestInterface
             */
            $config['request'] = function ( $container){
                return Request::createFromEnvironment($container->get('env'));
            };
        }
        if (!isset($config['response'])){
            /**
             * PSR-7 Request object
             *
             * @param Container $container
             *
             * @return ResponseInterface
             */
            $config['response'] = function ( $container){
              $headers = new Headers();
              $response = new Response(200,$headers);
              return $response->withProtocolVersion($container->get('settings')['httpVersion']);
            };
        }
        if (!isset($config['foundHandler'])){

            $config['foundHandler'] = function (){
                return new RequestResponse();
            };
        }

        if (!isset($config['callableResolver'])) {
            /**
             * Instance of \moon\CallableResolverInterface
             *
             * @param Container $container
             *
             * @return CallableResolverInterface
             */
            $config['callableResolver'] = function ($container) {
                return new CallableResolver($container);
            };
        }

    }
}