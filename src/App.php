<?php
/**
 * author: lazar
 * Date: 2018/4/11
 * Time: 13:45
 */
namespace moon;

use \DI\ContainerBuilder;
use \FastRoute\Dispatcher;
use moon\exception\InvalidHttpMethodException;
use moon\exception\MethodNotAllowedException;
use moon\exception\MoonException;
use moon\exception\NotFoundException;
use moon\router\interfaces\RouterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class App{

    use MiddlewareAwareTrait;

    const  VERSION = '1.0';
    /*
     * object provider pool
     * */
    protected $pool;

    protected $defaultSettings = [
        'settings'=>[
            'httpVersion' => '1.1',
            'responseChunkSize' => 4096,
            'outputBuffering' => 'append',
            'determineRouteBeforeAppMiddleware' => false,
            'displayErrorDetails' => false,
            'addContentLengthHeader' => true,
            'routerCacheFile' => false,
        ]
    ];

    public function __construct($config = [])
    {
        if (is_array($config)){
            $config = array_merge($this->defaultSettings,$config);
            $this->registerServiceProvider($config);
            $containerBuilder = new ContainerBuilder();
            $containerBuilder->addDefinitions($config);
            $this->pool = $containerBuilder->build();
        }else{
            throw new \InvalidArgumentException('Init Pool Error,entry params type invalid');
        }
    }

    protected function registerServiceProvider(&$config){
        $provider = new ServicesProvider();
        $provider->register($config);
    }

    public function getPool(){
        return $this->pool;
    }

    public function run($silent = false){

        $response = $this->pool->get('response');

        try {
            ob_start();
            $response = $this->process($this->pool->get('request'), $response);
        } catch (InvalidHttpMethodException $e) {
            $response = $this->processInvalidMethod($e->getRequest(), $response);
        } finally {
            $output = ob_get_clean();
        }

        if (!empty($output) && $response->getBody()->isWritable()) {
            $outputBuffering = $this->pool->get('settings')['outputBuffering'];
            if ($outputBuffering === 'prepend') {
                // prepend output buffer content
                $body = new Http\Body(fopen('php://temp', 'r+'));
                $body->write($output . $response->getBody());
                $response = $response->withBody($body);
            } elseif ($outputBuffering === 'append') {
                // append output buffer content
                $response->getBody()->write($output);
            }
        }

        $response = $this->finalize($response);

        if (!$silent) {
            $this->send($response);
        }

        return $response;
    }

    protected function process(ServerRequestInterface $request, ResponseInterface $response){
        // Ensure basePath is set
        $router = $this->pool->get('router');
        if (is_callable([$request->getUri(), 'getBasePath']) && is_callable([$router, 'setBasePath'])) {
            $router->setBasePath($request->getUri()->getBasePath());
        }

        // Dispatch the Router first if the setting for this is on
        if ($this->pool->get('settings')['determineRouteBeforeAppMiddleware'] === true) {
            // Dispatch router (note: you won't be able to alter routes after this)
            $request = $this->dispatchRouterAndPrepareRoute($request, $router);
        }

        // Traverse middleware stack
        try {
            $response = $this->callMiddlewareStack($request, $response);
        } catch (\Exception $e) {
            $response = $this->handleException($e, $request, $response);
        } catch (\Throwable $e) {
            $response = $this->handlePhpError($e, $request, $response);
        }

        return $response;
    }

    protected function processInvalidMethod(ServerRequestInterface $request, ResponseInterface $response)
    {
        $router = $this->pool->get('router');
        if (is_callable([$request->getUri(), 'getBasePath']) && is_callable([$router, 'setBasePath'])) {
            $router->setBasePath($request->getUri()->getBasePath());
        }

        $request = $this->dispatchRouterAndPrepareRoute($request, $router);
        $routeInfo = $request->getAttribute('routeInfo', [RouterInterface::DISPATCH_STATUS => Dispatcher::NOT_FOUND]);

        if ($routeInfo[RouterInterface::DISPATCH_STATUS] === Dispatcher::METHOD_NOT_ALLOWED) {
            return $this->handleException(
                new MethodNotAllowedException($request, $response, $routeInfo[RouterInterface::ALLOW_METHODS]),
                $request,
                $response
            );
        }

        return $this->handleException(new NotFoundException($request, $response), $request, $response);
    }

    protected function finalize($response){
        return $response;
    }

    /**
     * send response data to client
     * @param ResponseInterface $response
     * */
    protected function send($response){
        // Send response
        if (!headers_sent()) {
            // Headers
            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), false);
                }
            }

            // Set the status _after_ the headers, because of PHP's "helpful" behavior with location headers.
            // See https://github.com/slimphp/Slim/issues/1730

            // Status
            header(sprintf(
                'HTTP/%s %s %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ));
        }

        // Body
        if (!$this->isEmptyResponse($response)) {
            $body = $response->getBody();
            if ($body->isSeekable()) {
                $body->rewind();
            }
            $settings       = $this->pool->get('settings');
            $chunkSize      = $settings['responseChunkSize'];

            $contentLength  = $response->getHeaderLine('Content-Length');
            if (!$contentLength) {
                $contentLength = $body->getSize();
            }


            if (isset($contentLength)) {
                $amountToRead = $contentLength;
                while ($amountToRead > 0 && !$body->eof()) {
                    $data = $body->read(min($chunkSize, $amountToRead));
                    echo $data;

                    $amountToRead -= strlen($data);

                    if (connection_status() != CONNECTION_NORMAL) {
                        break;
                    }
                }
            } else {
                while (!$body->eof()) {
                    echo $body->read($chunkSize);
                    if (connection_status() != CONNECTION_NORMAL) {
                        break;
                    }
                }
            }
        }
    }

    public function get($pattern,$callable){
        return $this->match(['GET'],$pattern,$callable);
    }

    public function post($pattern, $callable)
    {
        return $this->match(['POST'], $pattern, $callable);
    }

    public function match(array $methods, $pattern, $callable)
    {

        if ($callable instanceof \Closure){
            $callable = $callable->bindTo($this->pool);
        }

        $router = $this->pool->get('router')->match($methods,$pattern,$callable);
        if (is_callable([$router,'setContainer'])){
            $router->setContainer($this->pool);
        }
        if (is_callable([$router, 'setOutputBuffering'])) {
            $router->setOutputBuffering($this->pool->get('settings')['outputBuffering']);
        }

        return $router;
    }

    protected function isEmptyResponse(ResponseInterface $response)
    {
        if (method_exists($response, 'isEmpty')) {
            return $response->isEmpty();
        }

        return in_array($response->getStatusCode(), [204, 205, 304]);
    }
    protected function handleException(\Exception $e, ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($e instanceof MethodNotAllowedException) {
            $handler = 'notAllowedHandler';
            $params = [$e->getRequest(), $e->getResponse(), $e->getAllowedMethods()];
        } elseif ($e instanceof NotFoundException) {
            $handler = 'notFoundHandler';
            $params = [$e->getRequest(), $e->getResponse(), $e];
        } elseif ($e instanceof MoonException) {
            // This is a Stop exception and contains the response
            return $e->getResponse();
        } else {
            // Other exception, use $request and $response params
            $handler = 'errorHandler';
            $params = [$request, $response, $e];
        }

        if ($this->pool->has($handler)) {
            $callable = $this->pool->get($handler);
            // Call the registered handler
            return call_user_func_array($callable, $params);
        }

        // No handlers found, so just throw the exception
        throw $e;
    }
    protected function handlePhpError(\Throwable $e, ServerRequestInterface $request, ResponseInterface $response)
    {
        $handler = 'phpErrorHandler';
        $params = [$request, $response, $e];

        if ($this->pool->has($handler)) {
            $callable = $this->pool->get($handler);
            // Call the registered handler
            return call_user_func_array($callable, $params);
        }

        // No handlers found, so just throw the exception
        throw $e;
    }

    protected function dispatchRouterAndPrepareRoute(ServerRequestInterface $request, RouterInterface $router)
    {
        $routeInfo = $router->dispatch($request);

        if ($routeInfo[0] === Dispatcher::FOUND) {
            $routeArguments = [];
            foreach ($routeInfo[2] as $k => $v) {
                $routeArguments[$k] = urldecode($v);
            }

            $route = $router->lookupRoute($routeInfo[1]);
            $route->prepare($request, $routeArguments);

            // add route to the request's attributes in case a middleware or handler needs access to the route
            $request = $request->withAttribute('route', $route);
        }

        $routeInfo['request'] = [$request->getMethod(), (string) $request->getUri()];

        return $request->withAttribute('routeInfo', $routeInfo);
    }

    /**
     * Call middleware stack
     *
     * @param  ServerRequestInterface $request A request object
     * @param  ResponseInterface      $response A response object
     *
     * @return ResponseInterface
     */
    public function callMiddlewareStack(ServerRequestInterface $request, ResponseInterface $response)
    {
        if (is_null($this->tip)) {
            $this->seedMiddlewareStack();
        }
        /** @var callable $start */
        $start = $this->tip;
        $this->middlewareLock = true;
        $response = $start($request, $response);
        $this->middlewareLock = false;
        return $response;
    }

    public function __invoke(ServerRequestInterface $request,ResponseInterface $response)
    {
        // Get the route info
        $routeInfo = $request->getAttribute('routeInfo');

        /** @var \moon\router\interfaces\RouterInterface $router */
        $router = $this->pool->get('router');

        // If router hasn't been dispatched or the URI changed then dispatch
        if (null === $routeInfo || ($routeInfo['request'] !== [$request->getMethod(), (string) $request->getUri()])) {
            $request = $this->dispatchRouterAndPrepareRoute($request, $router);
            $routeInfo = $request->getAttribute('routeInfo');
        }

        if ($routeInfo[0] === Dispatcher::FOUND) {
            //router find route that match with routeInfo[1] and routeInfo is route_id
            $route = $router->lookupRoute($routeInfo[1]);

            return $route->run($request, $response);
        } elseif ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            if (!$this->pool->has('notAllowedHandler')) {
                throw new MethodNotAllowedException($request, $response, $routeInfo[1]);
            }
            /** @var callable $notAllowedHandler */
            $notAllowedHandler = $this->pool->get('notAllowedHandler');
            return $notAllowedHandler($request, $response, $routeInfo[1]);
        }

        if (!$this->pool->has('notFoundHandler')) {
            throw new NotFoundException($request, $response);
        }
        /** @var callable $notFoundHandler */
        $notFoundHandler = $this->pool->get('notFoundHandler');
        return $notFoundHandler($request, $response);
    }
}