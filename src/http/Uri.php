<?php
/**
 * Created by PhpStorm.
 * User: PC
 * Date: 2018/4/13
 * Time: 13:15
 */
namespace moon\http;

use moon\http\interfaces\UriInterface;

class Uri implements UriInterface{

    /***
     * URI scheme
     * @var string
     * */
    protected $scheme = '';

    /**
     * uri users
     * @var string
     * */
    protected $user = '';

    /**
     * uri password
     * @var string
     * */
    protected $password;

    /**
     * uri host
     * @var string
     * */
    protected $host;

    /**
     * uri port
     * */
    protected $port;

    /**
     * Uri base path
     *
     * @var string
     */
    protected $basePath = '';

    /**
     * Uri path
     *
     * @var string
     */
    protected $path = '';

    /**
     * Uri query string (without "?" prefix)
     *
     * @var string
     */
    protected $query = '';

    /**
     * Uri fragment string (without "#" prefix)
     *
     * @var string
     */
    protected $fragment = '';

    /**
     * Create new Uri.
     *
     * @param string $scheme   Uri scheme.
     * @param string $host     Uri host.
     * @param int    $port     Uri port number.
     * @param string $path     Uri path.
     * @param string $query    Uri query string.
     * @param string $fragment Uri fragment.
     * @param string $user     Uri user.
     * @param string $password Uri password.
     */
    public function __construct(
        $scheme,
        $host,
        $port = null,
        $path = '/',
        $query = '',
        $fragment = '',
        $user = '',
        $password = ''
    ) {
        $this->scheme = $this->validateScheme($scheme);
        $this->host = $host;
        $this->port = $this->validatePort($port);
        $this->path = empty($path) ? '/' : $this->filterPath($path);
        $this->query = $this->filterQuery($query);
        $this->fragment = $this->filterQuery($fragment);
        $this->user = $user;
        $this->password = $password;
    }

    protected function validateScheme($scheme)
    {
        static $valid = [
            '' => true,
            'https' => true,
            'http' => true,
        ];

        if (!is_string($scheme) && !method_exists($scheme, '__toString')) {
            throw new \InvalidArgumentException('Uri scheme must be a string');
        }

        $scheme = str_replace('://', '', strtolower((string)$scheme));
        if (!isset($valid[$scheme])) {
            throw new \InvalidArgumentException('Uri scheme must be one of: "", "https", "http"');
        }

        return $scheme;
    }

    protected function validatePort($port)
    {
        if (is_null($port) || (is_integer($port) && ($port >= 1 && $port <= 65535))) {
            return $port;
        }

        throw new \InvalidArgumentException('Uri port must be null or an integer between 1 and 65535 (inclusive)');
    }

    protected function filterPath($path)
    {
        return preg_replace_callback(
            '/(?:[^a-zA-Z0-9_\-\.~:@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/',
            function ($match) {
                return rawurlencode($match[0]);
            },
            $path
        );
    }

    protected function filterQuery($query)
    {
        return preg_replace_callback(
            '/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/',
            function ($match) {
                return rawurlencode($match[0]);
            },
            $query
        );
    }

    /**
     * Set base path.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param  string $basePath
     * @return self
     */
    public function withBasePath($basePath)
    {
        if (!is_string($basePath)) {
            throw new \InvalidArgumentException('Uri path must be a string');
        }
        if (!empty($basePath)) {
            $basePath = '/' . trim($basePath, '/'); // <-- Trim on both sides
        }
        $clone = clone $this;

        if ($basePath !== '/') {
            $clone->basePath = $this->validatePath($basePath);
        }

        return $clone;
    }

    protected function validatePath($path)
    {
        return preg_replace_callback(
            '/(?:[^a-zA-Z0-9_\-\.~:@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/',
            function ($match) {
                return rawurlencode($match[0]);
            },
            $path
        );
    }



    public function getScheme()
    {
        // TODO: Implement getScheme() method.
    }

    public function getAuthority()
    {
        // TODO: Implement getAuthority() method.
    }

    public function getUserInfo()
    {
        // TODO: Implement getUserInfo() method.
    }

    public function getHost()
    {
        // TODO: Implement getHost() method.
    }

    public function getPort()
    {
        return $this->port;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getBasePath()
    {
        return $this->basePath;
    }

    public function getQuery()
    {
       return $this->query;
    }

    public function getFragment()
    {
        return $this->fragment;
    }

    public function withScheme($scheme)
    {
        $scheme = $this->validateScheme($scheme);
        $clone = clone $this;
        $clone->scheme = $scheme;

        return $clone;
    }

    public function withUserInfo($user, $password = null)
    {
        // TODO: Implement withUserInfo() method.
    }

    public function withHost($host)
    {
        // TODO: Implement withHost() method.
    }

    public function withPort($port)
    {
        $port = $this->validatePort($port);
        $clone = clone $this;
        $clone->port = $port;

        return $clone;
    }

    public function withPath($path)
    {
        if (!is_string($path)) {
            throw new \InvalidArgumentException('Uri path must be a string');
        }

        $clone = clone $this;
        $clone->path = $this->filterPath($path);

        // if the path is absolute, then clear basePath
        if (substr($path, 0, 1) == '/') {
            $clone->basePath = '';
        }

        return $clone;
    }

    public function withBaePath($basePath){
        if (!is_string($basePath)) {
            throw new \InvalidArgumentException('Uri path must be a string');
        }
        if (!empty($basePath)) {
            $basePath = '/' . trim($basePath, '/'); // <-- Trim on both sides
        }
        $clone = clone $this;

        if ($basePath !== '/') {
            $clone->basePath = $this->filterPath($basePath);
        }

        return $clone;
    }

    public function getBaseUrl()
    {
        $scheme = $this->getScheme();
        $authority = $this->getAuthority();
        $basePath = $this->getBasePath();

        if ($authority && substr($basePath, 0, 1) !== '/') {
            $basePath = $basePath . '/' . $basePath;
        }

        return ($scheme ? $scheme . ':' : '')
            . ($authority ? '//' . $authority : '')
            . rtrim($basePath, '/');
    }

    public function withQuery($query)
    {
        if (!is_string($query) && !method_exists($query, '__toString')) {
            throw new \InvalidArgumentException('Uri query must be a string');
        }
        $query = ltrim((string)$query, '?');
        $clone = clone $this;
        $clone->query = $this->filterQuery($query);

        return $clone;
    }

    public function withFragment($fragment)
    {
        if (!is_string($fragment) && !method_exists($fragment, '__toString')) {
            throw new \InvalidArgumentException('Uri fragment must be a string');
        }
        $fragment = ltrim((string)$fragment, '#');
        $clone = clone $this;
        $clone->fragment = $this->filterQuery($fragment);

        return $clone;
    }

    public function __toString()
    {
        $scheme = $this->getScheme();
        $authority = $this->getAuthority();
        $basePath = $this->getBasePath();
        $path = $this->getPath();
        $query = $this->getQuery();
        $fragment = $this->getFragment();

        $path = $basePath . '/' . ltrim($path, '/');

        return ($scheme ? $scheme . ':' : '')
            . ($authority ? '//' . $authority : '')
            . $path
            . ($query ? '?' . $query : '')
            . ($fragment ? '#' . $fragment : '');
    }

    /**
     * Create new Uri from environment.
     *
     * @param Env $env
     *
     * @return self
     */
    public static function createFromEnvironment(Env $env)
    {
        // Scheme
        $isSecure = $env->get('HTTPS');
        $scheme = (empty($isSecure) || $isSecure === 'off') ? 'http' : 'https';

        // Authority: Username and password
        $username = $env->get('PHP_AUTH_USER', '');
        $password = $env->get('PHP_AUTH_PW', '');

        // Authority: Host
        if ($env->has('HTTP_HOST')) {
            $host = $env->get('HTTP_HOST');
        } else {
            $host = $env->get('SERVER_NAME');
        }

        // Authority: Port
        $port = (int)$env->get('SERVER_PORT', 80);
        if (preg_match('/^(\[[a-fA-F0-9:.]+\])(:\d+)?\z/', $host, $matches)) {
            $host = $matches[1];

            if (isset($matches[2])) {
                $port = (int) substr($matches[2], 1);
            }
        } else {
            $pos = strpos($host, ':');
            if ($pos !== false) {
                $port = (int) substr($host, $pos + 1);
                $host = strstr($host, ':', true);
            }
        }

        // Path
        $requestScriptName = parse_url($env->get('SCRIPT_NAME'), PHP_URL_PATH);
        $requestScriptDir = dirname($requestScriptName);

        // parse_url() requires a full URL. As we don't extract the domain name or scheme,
        // we use a stand-in.
        $requestUri = parse_url('http://example.com' . $env->get('REQUEST_URI'), PHP_URL_PATH);

        $basePath = '';
        $virtualPath = $requestUri;
        if (stripos($requestUri, $requestScriptName) === 0) {
            $basePath = $requestScriptName;
        } elseif ($requestScriptDir !== '/' && stripos($requestUri, $requestScriptDir) === 0) {
            $basePath = $requestScriptDir;
        }

        if ($basePath) {
            $virtualPath = ltrim(substr($requestUri, strlen($basePath)), '/');
        }

        // Query string
        $queryString = $env->get('QUERY_STRING', '');
        if ($queryString === '') {
            $queryString = parse_url('http://example.com' . $env->get('REQUEST_URI'), PHP_URL_QUERY);
        }

        // Fragment
        $fragment = '';

        // Build Uri
        $uri = new static($scheme, $host, $port, $virtualPath, $queryString, $fragment, $username, $password);
        if ($basePath) {
            $uri = $uri->withBasePath($basePath);
        }

        return $uri;
    }

}