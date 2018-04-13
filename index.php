<?php
/**
 * entry
 */
require 'vendor/autoload.php';

$config = [
    \app\blog\data\ArticleData::class => function(){
        return new \app\blog\data\ArticleData();
    },
];
$app = new \moon\App($config);
$app->get('/','');
$app->run();


//路由分发器
//$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r){
//    $r->addRoute('GET','/',function (){
//        return '__invoke';
//    });
//    $r->addRoute('GET','/arts',['app\blog\controller\ArticleController','index']);
//    $r->addRoute('GET','/art','app\blog\controller\ArticleController');
//},[]);
//
////依赖注入容器
//$config = [
//    \app\blog\data\ArticleData::class => function(){
//        return new \app\blog\data\ArticleData();
//    },
//];
//$containerBuilder = new \DI\ContainerBuilder();
//$containerBuilder->addDefinitions($config);
//$container = $containerBuilder->build();
//
////Fetch method and uri from $server
//
//$httpMethod = $_SERVER['REQUEST_METHOD'];
//$uri = $_SERVER['REQUEST_URI'];
//$time = $_SERVER['REQUEST_TIME'];
//
//$params = '';
//if (false!==$pos = strpos($uri,'?')){
//    $params = substr($uri,$pos+1);
//    $uri = substr($uri,0,$pos);
//}
//
//
//$uri = rawurldecode($uri);
//
//$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
//
//switch ($routeInfo[0]) {
//    case FastRoute\Dispatcher::NOT_FOUND:
//        // ... 404 Not Found
//        print_r('404');
//        break;
//    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
//        $allowedMethods = $routeInfo[1];
//        // ... 405 Method Not Allowed
//        break;
//    case FastRoute\Dispatcher::FOUND:
//        $handler = $routeInfo[1];
//        $vars = $routeInfo[2];
//        // ... call $handler with $vars
////        echo $handler($vars);
//        var_dump($container->call($handler,$vars))  ;
//        break;
//}