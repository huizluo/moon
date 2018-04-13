<?php
namespace app\blog\model;
/**
 *
 */

class Article implements \JsonSerializable {
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

    function jsonSerialize()
    {
        $tmp['id'] = $this->id;
        $tmp['title'] = $this->title;
        $tmp['content'] = $this->content;

        return json_encode($tmp);
    }


}