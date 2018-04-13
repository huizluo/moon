<?php
/**
 * Created by PhpStorm.
 * User: PC
 * Date: 2018/4/13
 * Time: 10:38
 */
namespace moon\http;

use moon\Collection;
use moon\exception\InvalidHttpMethodException;
use moon\http\interfaces\HeadersInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\UploadedFileInterface;

class Request extends Message implements ServerRequestInterface {

    /**
     * The request method
     *
     * @var string
     */
    protected $method;

    /**
     * The original request method (ignoring override)
     *
     * @var string
     */
    protected $originalMethod;

    /**
     * The request URI object
     *
     * @var \moon\http\interfaces\UriInterface
     */
    protected $uri;

    /**
     * The request URI target (path + query string)
     *
     * @var string
     */
    protected $requestTarget;

    /**
     * The request query string params
     *
     * @var array
     */
    protected $queryParams;

    /**
     * The request cookies
     *
     * @var array
     */
    protected $cookies;

    /**
     * The server environment variables at the time the request was created.
     *
     * @var array
     */
    protected $serverParams;

    /**
     * The request attributes (route segment names and values)
     *
     * @var \moon\Collection
     */
    protected $attributes;

    /**
     * The request body parsed (if possible) into a PHP array or object
     *
     * @var null|array|object
     */
    protected $bodyParsed = false;

    /**
     * List of request body parsers (e.g., url-encoded, JSON, XML, multipart)
     *
     * @var callable[]
     */
    protected $bodyParsers = [];

    /**
     * List of uploaded files
     *
     * @var UploadedFileInterface[]
     */
    protected $uploadedFiles;

    /**
     * Valid request methods
     *
     * @var string[]
     * @deprecated
     */
    protected $validMethods = [
        'CONNECT' => 1,
        'DELETE' => 1,
        'GET' => 1,
        'HEAD' => 1,
        'OPTIONS' => 1,
        'PATCH' => 1,
        'POST' => 1,
        'PUT' => 1,
        'TRACE' => 1,
    ];

    /**
     * create a new http request
     *
     * @param string           $method        The request method
     * @param UriInterface     $uri           The request URI object
     * @param HeadersInterface $headers       The request headers collection
     * @param array            $cookies       The request cookies collection
     * @param array            $serverParams  The server environment variables
     * @param StreamInterface  $body          The request body object
     * @param array            $uploadFiles The request uploadedFiles collection
     * @throws \InvalidArgumentException on http method
     * */
    public function __construct(
        $method,
        UriInterface $uri,
        HeadersInterface $headers,
        array $cookies,
        array $serverParams,
        StreamInterface $body,
        array $uploadFiles = []
    )
    {
        try{
            $this->originalMethod = $this->validateMethod($method);
            $this->method = $this->validateMethod($method);
        }catch (InvalidHttpMethodException $exception){
            $this->originalMethod = $method;
        }


        $this->headers = $headers;
        $this->body = $body;
        $this->uri = $uri;
        $this->serverParams = $serverParams;
        $this->attributes = new Collection();
        $this->cookies = $cookies;
        $this->uploadedFiles = $uploadFiles;
        $this->queryParams = $this->getQueryParams();
        $this->attributes = $this->getAttributes();



        if (!$this->headers->has('Host') || $this->uri->getHost() !== '') {
            $this->headers->set('Host', $this->uri->getHost());
        }

        if (isset($serverParams['SERVER_PROTOCOL'])) {
            $this->protocolVersion = str_replace('HTTP/', '', $serverParams['SERVER_PROTOCOL']);
        }


        $this->registerDataTypeParser('application/json', function ($input) {
            $result = json_decode($input, true);
            if (!is_array($result)) {
                return null;
            }
            return $result;
        });

        $this->registerDataTypeParser('application/xml', function ($input) {
            $backup = libxml_disable_entity_loader(true);
            $backup_errors = libxml_use_internal_errors(true);
            $result = simplexml_load_string($input);
            libxml_disable_entity_loader($backup);
            libxml_clear_errors();
            libxml_use_internal_errors($backup_errors);
            if ($result === false) {
                return null;
            }
            return $result;
        });

        $this->registerDataTypeParser('text/xml', function ($input) {
            $backup = libxml_disable_entity_loader(true);
            $backup_errors = libxml_use_internal_errors(true);
            $result = simplexml_load_string($input);
            libxml_disable_entity_loader($backup);
            libxml_clear_errors();
            libxml_use_internal_errors($backup_errors);
            if ($result === false) {
                return null;
            }
            return $result;
        });

        $this->registerDataTypeParser('application/x-www-form-urlencoded', function ($input) {
            parse_str($input, $data);
            return $data;
        });

        // if the request had an invalid method, we can throw it now
        if (isset($e) && $e instanceof InvalidHttpMethodException) {
            throw $e;
        }
    }

    public function getOriginalMethod(){
        return $this->originalMethod;
    }

    /**
     * validate http method
     * @param String $method
     * @return String
     * */
    protected function validateMethod($method){
        if ($method === null) {
            return $method;
        }

        if (!is_string($method)) {
            throw new \InvalidArgumentException(sprintf(
                'Unsupported HTTP method; must be a string, received %s',
                (is_object($method) ? get_class($method) : gettype($method))
            ));
        }

        $method = strtoupper($method);
        if (preg_match("/^[!#$%&'*+.^_`|~0-9a-z-]+$/i", $method) !== 1) {
            throw new InvalidHttpMethodException($this, $method);
        }

        return $method;
    }

    /**
     * register data parser
     * @param String $dataType http header Content-type (text,xml,json)
     * @param callable $callable a callable that return parsed contents for data type
     * */
    public function registerDataTypeParser($dataType, callable $callable)
    {
        if ($callable instanceof \Closure) {
            $callable = $callable->bindTo($this);
        }
        $this->bodyParsers[(string)$dataType] = $callable;
    }
    public function __clone()
    {
         $this->headers = clone $this->headers;
         $this->body = clone $this->body;
         $this->attributes = clone $this->attributes;
    }


    public function withRequestTarget($requestTarget)
    {
         return $this->method;
    }

    public function getMethod()
    {
       return $this->method;
    }

    public function withMethod($method)
    {
        $method = $this->validateMethod($method);
        $new = clone $this;
        $new->originalMethod = $method;
        $new->method = $method;
        return $new;
    }

    /****
     * Uri
     *
     * */
    public function getRequestTarget()
    {
        if(isset($this->requestTarget)){
            return $this->requestTarget;
        }
        if ($this->uri === null){
            return '/';
        }

        $basePath = $this->uri->getBasePath();
        $path = $this->uri->getPath();
        $path = $basePath . '/' . ltrim($path,'/');
        $query = $this->uri->getQuery();
        if ($query){
            $path .='?'.$query;
        }

        $this->requestTarget = $path;

        return $this->requestTarget;
    }

    public function getUri()
    {
        return $this->uri;
    }


    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $new = clone $this;
        $new->uri = $uri;
        if (!$preserveHost) {
            if ($uri->getHost() !== '') {
                $new->headers->set('Host', $uri->getHost());
            }
        } else {
            if ($uri->getHost() !== '' && (!$this->hasHeader('Host') || $this->getHeaderLine('Host') === '')) {
                $new->headers->set('Host', $uri->getHost());
            }
        }

        return $new;

    }
    /**
     * Get request content type.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return string|null The request content type, if known
     */
    public function getContentType()
    {
        $result = $this->getHeader('Content-Type');

        return $result ? $result[0] : null;
    }

    /**
     * Get request Data type, if known.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return string|null The request media type, minus content-type params
     */
    public function getDataType()
    {
        $contentType = $this->getContentType();
        if ($contentType) {
            $contentTypeParts = preg_split('/\s*[;,]\s*/', $contentType);

            return strtolower($contentTypeParts[0]);
        }

        return null;
    }

    /**
     * Get request data type params, if known.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return array
     */
    public function getDataTypeParams()
    {
        $contentType = $this->getContentType();
        $contentTypeParams = [];
        if ($contentType) {
            $contentTypeParts = preg_split('/\s*[;,]\s*/', $contentType);
            $contentTypePartsLength = count($contentTypeParts);
            for ($i = 1; $i < $contentTypePartsLength; $i++) {
                $paramParts = explode('=', $contentTypeParts[$i]);
                $contentTypeParams[strtolower($paramParts[0])] = $paramParts[1];
            }
        }

        return $contentTypeParams;
    }

    /**
     * Get request content character set, if known.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return string|null
     */
    public function getContentCharset()
    {
        $dataTypeParams = $this->getDataTypeParams();
        if (isset($dataTypeParams['charset'])) {
            return $dataTypeParams['charset'];
        }

        return null;
    }

    /**
     * Get request content length, if known.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return int|null
     */
    public function getContentLength()
    {
        $result = $this->headers->get('Content-Length');

        return $result ? (int)$result[0] : null;
    }

    /***********
     * request type
     * */


    /**
     * Does this request use a given method?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param  string $method HTTP method
     * @return bool
     */
    public function isMethod($method)
    {
        return $this->getMethod() === $method;
    }

    /**
     * Is this a GET request?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isGet()
    {
        return $this->isMethod('GET');
    }

    /**
     * Is this a POST request?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isPost()
    {
        return $this->isMethod('POST');
    }

    /**
     * Is this a PUT request?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isPut()
    {
        return $this->isMethod('PUT');
    }

    /**
     * Is this a PATCH request?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isPatch()
    {
        return $this->isMethod('PATCH');
    }

    /**
     * Is this a DELETE request?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isDelete()
    {
        return $this->isMethod('DELETE');
    }

    /**
     * Is this a HEAD request?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isHead()
    {
        return $this->isMethod('HEAD');
    }

    /**
     * Is this a OPTIONS request?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isOptions()
    {
        return $this->isMethod('OPTIONS');
    }

    /*****
     * server params
     * */

    public function getServerParams()
    {
        return $this->serverParams;
    }

    /***
     * @param String $key
     * @return String
     * */
    public function getServerParam($key){
        return isset($this->serverParams[$key]) ? $this->serverParams[$key] : null;
    }

    /***********
     * Cookies
     * */

    public function getCookieParams()
    {
        return $this->cookies;
    }

    public function getCookie($key){

        if (isset($this->cookies[$key])){
            return $this->cookies[$key];
        }else{
            return null;
        }
    }

    public function withCookieParams(array $cookies)
    {
        $new = clone $this;
        $new->cookies = $cookies;

        return $new;
    }

    /***********
     * Query params
     * ***********/

    public function getQueryParams()
    {
        if (is_array($this->queryParams)) {
            return $this->queryParams;
        }

        if ($this->uri === null) {
            return [];
        }

        parse_str($this->uri->getQuery(), $this->queryParams); // <-- URL decodes data

        return $this->queryParams;
    }

    public function withQueryParams(array $query)
    {
        $new = clone $this;
        $new->queryParams = $query;
        return $new;
    }

    /************
     * File upload
     * */

    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    public function withUploadedFiles(array $uploadedFiles)
    {
        $new = clone $this;
        $new->uploadedFiles = $uploadedFiles;
        return $new;
    }

    /*****
     * http Body
     * */
    public function getParsedBody()
    {
        if ($this->bodyParsed !== false){
            return $this->bodyParsed;
        }
        if (!$this->body){
            return null;
        }

        $dataType = $this->getDataType();
        // look for a media type with a structured syntax suffix (RFC 6839)
        $parts = explode('+',$dataType);
        if (count($parts) >= 2){
            $dataType = 'application/' . $parts[count($parts)-1];
        }

        if (isset($this->bodyParsers[$dataType])){
            $body = (string)$this->getBody();
            $parsed = $this->bodyParsers[$dataType]($body);

            if (!is_null($parsed) && !is_object($parsed) && !is_array($parsed)) {
                throw new \RuntimeException(
                    'Request body media type parser return value must be an array, an object, or null'
                );
            }
            $this->bodyParsed = $parsed;
            return $this->bodyParsed;
        }

        return null;
    }

    public function withParsedBody($data)
    {
        if (!is_null($data) && !is_object($data) && !is_array($data)) {
            throw new \InvalidArgumentException('Parsed body value must be an array, an object, or null');
        }

        $clone = clone $this;
        $clone->bodyParsed = $data;

        return $clone;
    }

    /*****
     * http attr
     * */

    public function getAttributes()
    {
        return $this->attributes->all();
    }

    public function getAttribute($name, $default = null)
    {
        return $this->attributes->get($name,$default);
    }

    public function withAttribute($name, $value)
    {
        $new = clone $this;
        $new->attributes->set($name,$value);
        return $new;
    }

    public function withAttributes(array $attributes)
    {
        $new = clone $this;
        $new->attributes->replace($attributes);
        return $new;
    }

    public function withoutAttribute($name)
    {
        $new = clone $this;
        $new->attributes->remove($name);
        return $new;
    }

    /**
     * Create new HTTP request with data extracted from the application
     * Environment object
     *
     * @param  Env $env The Slim application Environment
     *
     * @return static
     */
    public static function createFromEnvironment(Env $env)
    {
        $method = $env['REQUEST_METHOD'];
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = Cookies::parseHeader($headers->get('Cookie', []));
        $serverParams = $env->all();
        $body = self::createRequestBody();
        $uploadedFiles = UploadedFile::createFromEnvironment($env);

        $request = new static($method, $uri, $headers, $cookies, $serverParams, $body, $uploadedFiles);

        if ($method === 'POST' &&
            in_array($request->getDataType(), ['application/x-www-form-urlencoded', 'multipart/form-data'])
        ) {
            // parsed body must be $_POST
            $request = $request->withParsedBody($_POST);
        }
        return $request;
    }

    /**
     * create a request body
     * @return StreamInterface
     * php:// (i/o streams)
     * php://input | php://output | php://fd | php://memory | php://temp
     * php://filter
     * see http://php.net/manual/zh/wrappers.php.php
     * */
    public static function createRequestBody(){
        $stream = fopen('php://temp', 'w+');
        stream_copy_to_stream(fopen('php://input', 'r'), $stream);
        rewind($stream);

        return new Body($stream);
    }

}