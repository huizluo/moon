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
$app->get('/',function (){
    return '123';
});
$app->get('/art',app\blog\controller\ArticleController::class);
$app->get('/arts',[app\blog\controller\ArticleController::class,'index']);
$app->get('/art/{id}',[app\blog\controller\ArticleController::class,'one']);

$app->run();
