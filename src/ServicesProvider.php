<?php
/**
 * Created by PhpStorm.
 * User: PC
 * Date: 2018/4/13
 * Time: 10:31
 */
namespace moon;

class ServicesProvider{

    public function register(&$pool){
        if(!isset($pool['env'])){
            $pool['env'] = function (){

            };
        }
    }
}