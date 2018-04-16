<?php
namespace app\blog\controller;
use app\blog\data\ArticleData;
use moon\http\Request;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;

/**
 *
 */
class ArticleController{

    protected $articledata;
    protected $c;

    public function __construct(ArticleData $articleData,ContainerInterface $container){
        $this->articledata = $articleData;
        $this->c = $container;
    }

    public function index(){

        $tmp = $this->articledata->getArticles();
        $return = [];
        foreach ($tmp as &$value){
             array_push($return,$value->toArray());
        }
        return json_encode($return);
    }

    public function one(Request $request){
        $id = $request->getAttribute('id');

        return json_encode($this->articledata->getArticle($id)->toArray());
    }

    public function __invoke()
    {
        return  'magic method invoke';
    }

}