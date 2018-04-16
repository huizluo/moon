<?php
/***
 * route manager
 *路由管理者，routes属性包含所有注册的路由器，指定路由的缓存文件
 * */
namespace moon\router;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser;
use FastRoute\RouteParser\Std as StdParser;
use InvalidArgumentException;
use moon\router\interfaces\RouteGroupInterface;
use moon\router\interfaces\RouteInterface;
use moon\router\interfaces\RouterInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;


class Router implements RouterInterface
{

    /**
     * Container Interface
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Parser
     *
     * @var \FastRoute\RouteParser
     */
    protected $routeParser;

    /**
     * Base path used in pathFor()
     *
     * @var string
     */
    protected $basePath = '';

    /**
     * Path to fast route cache file. Set to false to disable route caching
     *
     * @var string|False
     */
    protected $cacheFile = false;

    /**
     * Routes
     *
     * @var Route[]
     */
    protected $routes = [];

    /**
     * Route counter incrementer
     * @var int
     */
    protected $routeCounter = 0;

    /**
     * Route groups
     *
     * @var RouteGroup[]
     */
    protected $routeGroups = [];

    /**
     * @var \FastRoute\Dispatcher
     */
    protected $dispatcher;

    /**
     * Create new router
     *
     * @param RouteParser   $parser
     */
    public function __construct(RouteParser $parser = null)
    {
        $this->routeParser = $parser ?: new StdParser;
    }

    /**
     * Set the base path used in pathFor()
     *
     * @param string $basePath
     *
     * @return self
     */
    public function setBasePath($basePath)
    {
        if (!is_string($basePath)) {
            throw new InvalidArgumentException('Router basePath must be a string');
        }

        $this->basePath = $basePath;

        return $this;
    }

    /**
     * Set path to fast route cache file. If this is false then route caching is disabled.
     *
     * @param string|false $cacheFile
     *
     * @return self
     */
    public function setCacheFile($cacheFile)
    {
        if (!is_string($cacheFile) && $cacheFile !== false) {
            throw new InvalidArgumentException('Router cacheFile must be a string or false');
        }

        $this->cacheFile = $cacheFile;

        if ($cacheFile !== false && !is_writable(dirname($cacheFile))) {
            throw new RuntimeException('Router cacheFile directory must be writable');
        }


        return $this;
    }

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Add route
     *
     * @param  string[] $methods Array of HTTP methods
     * @param  string   $pattern The route pattern
     * @param  callable $handler The route callable
     *
     * @return RouteInterface
     *
     * @throws InvalidArgumentException if the route pattern isn't a string
     */
    public function match($methods, $pattern,$handler)
    {
        if (!is_string($pattern)) {
            throw new InvalidArgumentException('Route pattern must be a string');
        }

        // Prepend parent group pattern(s)
        if ($this->routeGroups) {
            $pattern = $this->processGroups() . $pattern;
        }

        // According to RFC methods are defined in uppercase (See RFC 7231)
        $methods = array_map("strtoupper", $methods);

        // Add route
        $route = $this->createRoute($methods, $pattern, $handler);
        $this->routes[$route->getId()] = $route;
        $this->routeCounter++;

        return $route;
    }

    /**
     * Dispatch router for HTTP request
     *
     * @param  ServerRequestInterface $request The current HTTP request object
     *
     * @return array
     *
     * @link   https://github.com/nikic/FastRoute/blob/master/src/Dispatcher.php
     */
    public function dispatch(ServerRequestInterface $request)
    {
        $uri = '/' . ltrim($request->getUri()->getPath(), '/');

        return $this->createDispatcher()->dispatch(
            $request->getMethod(),
            $uri
        );
    }

    /**
     * Create a new Route object
     *
     * @param  string[] $methods Array of HTTP methods
     * @param  string   $pattern The route pattern
     * @param  callable $callable The route callable
     *
     * @return RouteInterface
     */
    protected function createRoute($methods, $pattern, $callable)
    {
        $route = new Route($methods, $pattern, $callable, $this->routeGroups, $this->routeCounter);
        if (!empty($this->container)) {
            $route->setContainer($this->container);
        }

        return $route;
    }

    /**
     * @return \FastRoute\Dispatcher
     */
    protected function createDispatcher()
    {
        if ($this->dispatcher) {
            return $this->dispatcher;
        }

        $routeDefinitionCallback = function (RouteCollector $r) {
            foreach ($this->getRoutes() as $route) {
                $r->addRoute($route->getMethods(), $route->getPattern(), $route->getId());
            }
        };

        if ($this->cacheFile) {
            $this->dispatcher = \FastRoute\cachedDispatcher($routeDefinitionCallback, [
                'routeParser' => $this->routeParser,
                'cacheFile' => $this->cacheFile,
            ]);
        } else {
            $this->dispatcher = \FastRoute\simpleDispatcher($routeDefinitionCallback, [
                'routeParser' => $this->routeParser,
            ]);
        }

        return $this->dispatcher;
    }

    /**
     * @param Dispatcher $dispatcher
     */
    public function setDispatcher(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Get route objects
     *
     * @return Route[]
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Get named route object
     *
     * @param string $name        Route name
     *
     * @return Route
     *
     * @throws RuntimeException   If named route does not exist
     */
    public function getNamedRoute($name)
    {
        foreach ($this->routes as $route) {
            if ($name == $route->getName()) {
                return $route;
            }
        }
        throw new RuntimeException('Named route does not exist for name: ' . $name);
    }

    /**
     * Remove named route
     *
     * @param string $name        Route name
     *
     * @throws RuntimeException   If named route does not exist
     */
    public function removeNamedRoute($name)
    {
        $route = $this->getNamedRoute($name);

        // no exception, route exists, now remove by id
        unset($this->routes[$route->getId()]);
    }

    /**
     * Process route groups
     *
     * @return string A group pattern to prefix routes with
     */
    protected function processGroups()
    {
        $pattern = "";
        foreach ($this->routeGroups as $group) {
            $pattern .= $group->getPattern();
        }
        return $pattern;
    }

    /**
     * Add a route group to the array
     *
     * @param string   $pattern
     * @param callable $callable
     *
     * @return RouteGroupInterface
     */
    public function pushGroup($pattern, $callable)
    {
        $group = new RouteGroup($pattern, $callable);
        array_push($this->routeGroups, $group);
        return $group;
    }

    /**
     * Removes the last route group from the array
     *
     * @return RouteGroup|bool The RouteGroup if successful, else False
     */
    public function popGroup()
    {
        $group = array_pop($this->routeGroups);
        return $group instanceof RouteGroup ? $group : false;
    }

    /**
     * @param $id
     * @return RouteInterface
     */
    public function lookupRoute($id)
    {
        if (!isset($this->routes[$id])) {
            throw new RuntimeException('Route not found, looks like your route cache is stale.');
        }
        return $this->routes[$id];
    }

    /**
     * Build the path for a named route excluding the base path
     *
     * @param string $name        Route name
     * @param array  $data        Named argument replacement data
     * @param array  $queryParams Optional query string parameters
     *
     * @return string
     *
     * @throws RuntimeException         If named route does not exist
     * @throws InvalidArgumentException If required data not provided
     */
    public function relativePathFor($name, array $data = [], array $queryParams = [])
    {
        $route = $this->getNamedRoute($name);
        $pattern = $route->getPattern();

        $routeDatas = $this->routeParser->parse($pattern);
        // $routeDatas is an array of all possible routes that can be made. There is
        // one routedata for each optional parameter plus one for no optional parameters.
        //
        // The most specific is last, so we look for that first.
        $routeDatas = array_reverse($routeDatas);

        $segments = [];
        $segmentName = '';
        foreach ($routeDatas as $routeData) {
            foreach ($routeData as $item) {
                if (is_string($item)) {
                    // this segment is a static string
                    $segments[] = $item;
                    continue;
                }

                // This segment has a parameter: first element is the name
                if (!array_key_exists($item[0], $data)) {
                    // we don't have a data element for this segment: cancel
                    // testing this routeData item, so that we can try a less
                    // specific routeData item.
                    $segments = [];
                    $segmentName = $item[0];
                    break;
                }
                $segments[] = $data[$item[0]];
            }
            if (!empty($segments)) {
                // we found all the parameters for this route data, no need to check
                // less specific ones
                break;
            }
        }

        if (empty($segments)) {
            throw new InvalidArgumentException('Missing data for URL segment: ' . $segmentName);
        }
        $url = implode('', $segments);

        if ($queryParams) {
            $url .= '?' . http_build_query($queryParams);
        }

        return $url;
    }


    /**
     * Build the path for a named route including the base path
     *
     * @param string $name        Route name
     * @param array  $data        Named argument replacement data
     * @param array  $queryParams Optional query string parameters
     *
     * @return string
     *
     * @throws RuntimeException         If named route does not exist
     * @throws InvalidArgumentException If required data not provided
     */
    public function pathFor($name, array $data = [], array $queryParams = [])
    {
        $url = $this->relativePathFor($name, $data, $queryParams);

        if ($this->basePath) {
            $url = $this->basePath . $url;
        }

        return $url;
    }

    /**
     * Build the path for a named route.
     *
     * This method is deprecated. Use pathFor() from now on.
     *
     * @param string $name        Route name
     * @param array  $data        Named argument replacement data
     * @param array  $queryParams Optional query string parameters
     *
     * @return string
     *
     * @throws RuntimeException         If named route does not exist
     * @throws InvalidArgumentException If required data not provided
     */
    public function urlFor($name, array $data = [], array $queryParams = [])
    {
        trigger_error('urlFor() is deprecated. Use pathFor() instead.', E_USER_DEPRECATED);
        return $this->pathFor($name, $data, $queryParams);
    }


}