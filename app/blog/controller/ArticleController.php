<?php
namespace app\blog\controller;
use app\blog\data\ArticleData;

/**
 *
 */
class ArticleController{

    protected $articledata;
    public function __construct(ArticleData $articleData){
        $this->articledata = $articleData;
    }

    public function index(){
        return $this->articledata->getArticles();
    }

    public function __invoke()
    {
        echo 'magic method invoke';
    }

}