<?php
/**
 * http message
 */
namespace moon\http;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

abstract class Message implements MessageInterface{

    /**
     * http protocol version
     * @var string
     * */
    protected $protocolVersion = '1.1';

    /**
     * allow protocol version array
     * @var array
     * */
    protected static $validProtocolVersions = [
        '1.0'=>true,
        '1.1'=>true,
        '2.0'=>true
    ];

    /**
     * http headers
     * @var \moon\http\interfaces\HeadersInterface
     * */
    protected $headers;

    /**
     * http body
     * @var StreamInterface
     * */
    protected $body;

    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    /***
     * create a new instance
     *
     * @param string $version
     * @return MessageInterface
     * */
    public function withProtocolVersion($version)
    {
        if(!isset(self::$validProtocolVersions[$version])){
            throw new \InvalidArgumentException(
                'Invalid HTTP version. Must be one of: '
                . implode(', ', array_keys(self::$validProtocolVersions))
            );
        }

        $new = clone $this;
        $new->protocolVersion = $version;
        return $new;
    }

    public function getHeaders()
    {
        return $this->headers->all();
    }

    public function getHeader($name)
    {
        return $this->headers->get($name);
    }

    public function hasHeader($name)
    {
        return $this->headers->has($name);
    }

    public function getHeaderLine($name)
    {
        return implode(',', $this->headers->get($name, []));
    }

    public function withHeader($name, $value)
    {
        $new = clone $this;
        $new->headers->set($name, $value);

        return $new;
    }

    public function withAddedHeader($name, $value)
    {
        $new = clone $this;
        $new->headers->add($name, $value);

        return $new;
    }

    public function withoutHeader($name)
    {
        $new = clone $this;
        $new->headers->remove($name);

        return $new;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body)
    {
        $new = clone $this;
        $new->body = $body;
        return $new;
    }


}