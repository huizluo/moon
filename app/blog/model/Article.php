<?php
namespace app\blog\model;
/**
 *
 */

class Article {

    private $id;
    private $title;
    private $content;


    public function __construct($id, $title, $content)
    {
        $this->id = $id;
        $this->title = $title;
        $this->content = $content;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getContent()
    {
        return $this->content;
    }

    function toArray()
    {
        $tmp['id'] = $this->id;
        $tmp['title'] = $this->title;
        $tmp['content'] = $this->content;

        return $tmp;
    }


}