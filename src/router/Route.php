<?php
/**
 * Created by PhpStorm.
 * User: PC
 * Date: 2018/4/13
 * Time: 15:47
 */
namespace moon\router;

use moon\handlers\interfaces\InvokeInterface;
use moon\MiddlewareAwareTrait;
use moon\router\abstracts\AbstractRoute;
use moon\router\interfaces\RouteInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use moon\handlers\RequestResponse;

class Route extends AbstractRoute implements RouteInterface
{
    use MiddlewareAwareTrait;

    /**
     *http methods supported by this route
     * */
    protected $methods = [];

    /**
     *this route id
     * */
    protected $id;

    /***
     * this route name
     * */
    protected $name;

    /***
     * this route group
     * @val RouteGroup[]
     * */
    protected $groups;

    private $finalized = false;

    /**
     * Output buffering mode
     *
     * One of: false, 'prepend' or 'append'
     *
     * @var boolean|string
     */
    protected $outputBuffering = 'append';

    /**
     * Route parameters
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * The callable payload
     *
     * @var callable
     */
    protected $callable;

    /**
     * Create new route
     *
     * @param string|string[]   $methods The route HTTP methods
     * @param string            $pattern The route pattern
     * @param callable          $callable The route callable
     * @param RouteGroup[]      $groups The parent route groups
     * @param int               $id The route identifier
     */
    public function __construct($methods, $pattern, $callable, $groups = [], $id = 0)
    {
        $this->methods  = is_string($methods) ? [$methods] : $methods;
        $this->pattern  = $pattern;
        $this->callable = $callable;
        $this->groups   = $groups;
        $this->id = 'route_' . $id;
    }

    /**
     * Finalize the route in preparation for dispatching
     */
    public function finalize()
    {
        if ($this->finalized) {
            return;
        }

        $groupMiddleware = [];
        foreach ($this->getGroups() as $group) {
            $groupMiddleware = array_merge($group->getMiddleware(), $groupMiddleware);
        }

        $this->middleware = array_merge($this->middleware, $groupMiddleware);

        foreach ($this->getMiddleware() as $middleware) {
            $this->addMiddleware($middleware);
        }

        $this->finalized = true;
    }

    /*****
     * Getter Setter
     * ******/


    /**
     * Get route callable
     *
     * @return callable
     */
    public function getCallable()
    {
        return $this->callable;
    }

    /**
     * This method enables you to override the Route's callable
     *
     * @param string|\Closure $callable
     */
    public function setCallable($callable)
    {
        $this->callable = $callable;
    }

    /**
     * Get route methods
     *
     * @return string[]
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * Get parent route groups
     *
     * @return RouteGroup[]
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Get route name
     *
     * @return null|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get route identifier
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get output buffering mode
     *
     * @return boolean|string
     */
    public function getOutputBuffering()
    {
        return $this->outputBuffering;
    }

    /**
     * Set output buffering mode
     *
     * One of: false, 'prepend' or 'append'
     *
     * @param boolean|string $mode
     *
     * @throws \InvalidArgumentException If an unknown buffering mode is specified
     */
    public function setOutputBuffering($mode)
    {
        if (!in_array($mode, [false, 'prepend', 'append'], true)) {
            throw new \InvalidArgumentException('Unknown output buffering mode');
        }
        $this->outputBuffering = $mode;
    }

    /**
     * Set route name
     *
     * @param string $name
     *
     * @return self
     *
     * @throws \InvalidArgumentException if the route name is not a string
     */
    public function setName($name)
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException('Route name must be a string');
        }
        $this->name = $name;
        return $this;
    }

    /**
     * Set a route argument
     *
     * @param string $name
     * @param string $value
     *
     * @return self
     */
    public function setArgument($name, $value)
    {
        $this->arguments[$name] = $value;
        return $this;
    }

    /**
     * Replace route arguments
     *
     * @param array $arguments
     *
     * @return self
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * Retrieve route arguments
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Retrieve a specific route argument
     *
     * @param string $name
     * @param string|null $default
     *
     * @return mixed
     */
    public function getArgument($name, $default = null)
    {
        if (array_key_exists($name, $this->arguments)) {
            return $this->arguments[$name];
        }
        return $default;
    }


    public function prepare(ServerRequestInterface $request, array $arguments)
    {
        foreach ($arguments as $k=>$v){
            $this->setArgument($k,$v);
        }
    }

    public function run(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->finalize();

        //call middleware stack
        return $this->callMiddlewareStack($request,$response);
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {

        $this->callable = $this->resolveCallable($this->callable);

        /** @var InvokeInterface $handler */
        $handler = isset($this->container) ? $this->container->get('foundHandler') : new RequestResponse();

        $newResponse = $handler($this->callable, $request, $response, $this->arguments);

        if ($newResponse instanceof ResponseInterface) {
            // if route callback returns a ResponseInterface, then use it
            $response = $newResponse;
        } elseif (is_string($newResponse)) {
            // if route callback returns a string, then append it to the response
            if ($response->getBody()->isWritable()) {
                $response->getBody()->write($newResponse);
            }
        }

        return $response;
    }
}