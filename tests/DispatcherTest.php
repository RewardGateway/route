<?php

namespace League\Route\Test;

use League\Container\Container;
use League\Route;
use League\Route\Http\Exception as HttpException;
use League\Route\Strategy\MethodArgumentStrategy;
use League\Route\Strategy\RestfulStrategy;
use League\Route\Strategy\RequestResponseStrategy;
use League\Route\Strategy\UriStrategy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use League\Route\Http\Exception\NotFoundException;
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Strategy\StrategyInterface;
use League\Container\ContainerInterface;
use League\Container\ServiceProvider;

class DispatcherTest extends TestCase
{
    /**
     * Assert that a route using the Restful Strategy returns a json response
     * when a http exception is thrown
     */
    public function testRestfulStrategyReturnsJsonResponseWhenHttpExceptionIsThrown(): void
    {
        $controller = $this->createMock(Route\Test\Fixture\SomeClass::class);

        $controller->expects($this->once())
                   ->method('someMethod')
                   ->will($this->throwException(new HttpException\ConflictException));

        $container = $this->createMock(Container::class);
        $request   = $this->createMock(Request::class);

        $container->method('isRegistered')->willReturn(false);
        $container->method('isInServiceProvider')->willReturn(true);

        $container->method('get')
            ->withConsecutive(['Symfony\Component\HttpFoundation\Request'], [$this->equalTo('SomeClass')])
            ->willReturnOnConsecutiveCalls($request, $controller);

        $collection = new Route\RouteCollection($container);
        $collection->setStrategy(new RestfulStrategy);

        $collection->get('/route', 'SomeClass::someMethod');
        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame('{"status_code":409,"message":"Conflict"}', $response->getContent());
    }

    /**
     * Assert that a route using Restful Strategy throws exception for wrong response type
     */
    public function testRestfulStrategyRouteThrowsExceptionWhenWrongResponseReturned(): void
    {
        $this->expectException('RuntimeException');

        $collection = new Route\RouteCollection;
        $collection->setStrategy(new RestfulStrategy);

        $collection->get('/route', function ($request) {
            return new \stdClass;
        });

        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route');
    }

    /**
     * Assert that a route using the Restful Strategy gets passed the correct arguments
     *
     * @return void
     */
    public function testRestfulStrategyReceivesCorrectArguments()
    {
        $collection = new Route\RouteCollection;
        $collection->setStrategy(new RestfulStrategy);

        $collection->get('/route', function ($request) {
            $this->assertInstanceOf('Symfony\Component\HttpFoundation\Request', $request);
            return new \ArrayObject;
        });

        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route');

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
    }

    /**
     * Assert that a route using the Restful Strategy returns response when controller does
     *
     * @return void
     */
    public function testRestfulStrategyRouteReturnsResponseWhenControllerDoes()
    {
        $mockResponse = $this->createMock('Symfony\Component\HttpFoundation\JsonResponse');

        $collection = new Route\RouteCollection;

        $collection->setStrategy(new RestfulStrategy);

        $collection->get('/route/{id}/{name}', function ($request) use ($mockResponse) {
            $this->assertInstanceOf('Symfony\Component\HttpFoundation\Request', $request);
            return $mockResponse;
        });

        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route/2/phil');

        $this->assertSame($mockResponse, $response);
    }

    /**
     * Asserts that the correct method is invoked on a class based controller
     *
     * @return void
     */
    public function testClassBasedControllerInvokesCorrectMethod()
    {
        $controller = $this->createMock(Route\Test\Fixture\SomeClass::class);

        $controller->expects($this->once())
                   ->method('someMethod')
                   ->with($this->equalTo('2'), $this->equalTo('phil'))
                   ->willReturn('hello world');

        $container = $this->createMock(ContainerInterface::class);

        $container->expects($this->once())
                  ->method('get')
                  ->with($this->equalTo('SomeClass'))
                  ->willReturn($controller);

        $collection = new Route\RouteCollection($container);
        $collection->setStrategy(new UriStrategy);
        $collection->get('/route/{id}/{name}', 'SomeClass::someMethod');

        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route/2/phil');

        $this->assertEquals('hello world', $response->getContent());
    }

    /**
     * Assert that an exception is thrown when no controller method is specified
     *
     * @return void
     */
    public function testClassBasedControllerRouteThrowsExceptionWhenNoFunctionPresent()
    {
        $this->expectException('RuntimeException');

        $container = $this->createMock(ContainerInterface::class);

        $collection = new Route\RouteCollection($container);
        $collection->setStrategy(new UriStrategy);

        $collection->get('/route/', 'SomeClass');
        $dispatcher = $collection->getDispatcher();

        $response = $dispatcher->dispatch('GET', '/route/');
    }

    /**
     * Assert that a route using the URI Strategy gets passed the correct arguments
     *
     * @return void
     */
    public function testUriStrategyRouteReceivesCorrectArguments()
    {
        $collection = new Route\RouteCollection;
        $collection->setStrategy(new UriStrategy);

        $collection->get('/route/{id}/{name}', function ($id, $name) {
            $this->assertEquals('2', $id);
            $this->assertEquals('phil', $name);
        });

        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route/2/phil');
    }

    /**
     * Assert that a route using the URI Strategy returns response when controller does
     *
     * @return void
     */
    public function testUriStrategyRouteReturnsResponseWhenControllerDoes()
    {
        $mockResponse = $this->createMock('Symfony\Component\HttpFoundation\Response');

        $collection = new Route\RouteCollection;
        $collection->setStrategy(new UriStrategy);

        $collection->get('/route/{id}/{name}', function ($id, $name) use ($mockResponse) {
            $this->assertEquals('2', $id);
            $this->assertEquals('phil', $name);
            return $mockResponse;
        });

        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route/2/phil');

        $this->assertSame($mockResponse, $response);
    }

    /**
     * Assert that a route using the URI Strategy throws exception when Response
     * cannot be built
     *
     * @return void
     */
    public function testUriStrategyRouteThrowsExceptionWhenResponseCannotBeBuilt()
    {
        $this->expectException('RuntimeException');

        $collection = new Route\RouteCollection;
        $collection->setStrategy(new UriStrategy);

        $collection->get('/route/{id}/{name}', function ($id, $name) {
            $this->assertEquals('2', $id);
            $this->assertEquals('phil', $name);
            return new \stdClass;
        });

        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route/2/phil');
    }

    /**
     * Assert that a route using the Method Argument Strategy throws exception when Response
     * cannot be built
     *
     * @return void
     */
    public function testMethodArgumentStrategyRouteThrowsExceptionWhenResponseCannotBeBuilt()
    {
        $this->expectException('RuntimeException');

        $collection = new Route\RouteCollection;
        $collection->setStrategy(new MethodArgumentStrategy);

        $collection->get('/route', function () {
            return new \stdClass;
        });

        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route');
    }

    /**
     * Asserts that the correct method is invoked on a class based controller
     *
     * @return void
     */
    public function testClassBasedControllerInvokesCorrectMethodOnMethodArgumentStrategy()
    {
        $controller = $this->createMock(Route\Test\Fixture\SomeClass::class);

        $container = $this->createMock(ContainerInterface::class);

        $container->expects($this->once())
                  ->method('get')
                  ->with($this->equalTo('SomeClass'))
                  ->willReturn($controller);

        $container->expects($this->once())
                  ->method('call')
                  ->with($this->equalTo([$controller, 'someMethod']), $this->equalTo(['name' => 'world']))
                  ->willReturn('hello world');

        $collection = new Route\RouteCollection($container);
        $collection->setStrategy(new MethodArgumentStrategy);

        $collection->get('/route/{name}', 'SomeClass::someMethod');
        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route/world');

        $this->assertEquals('hello world', $response->getContent());
    }

    /**
     * Assert that a route using the Request -> Response Strategy gets passed the correct arguments
     *
     * @return void
     */
    public function testRequestResponseStrategyRouteReceivesCorrectArguments()
    {
        $collection = new Route\RouteCollection;
        $collection->setStrategy(new RequestResponseStrategy);

        $collection->get('/route', function ($request, $response) {
            $this->assertInstanceOf('Symfony\Component\HttpFoundation\Request', $request);
            $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
            return $response;
        });

        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route');

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
    }

    /**
     * Assert that a route using the Request -> Response Strategy throws exception
     * when correct response not returned
     *
     * @return void
     */
    public function testRequestResponseStrategyRouteThrowsExceptionWhenWrongResponseReturned()
    {
        $this->expectException('RuntimeException');

        $collection = new Route\RouteCollection;
        $collection->setStrategy(new RequestResponseStrategy);

        $collection->get('/route', function ($request, $response) {
            $this->assertInstanceOf(Request::class, $request);
            $this->assertInstanceOf(Response::class, $response);
            return [];
        });

        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route');
    }

    /**
     * Assert that the request object is created from globals if it was not
     * already registered to the container
     *
     * @return void
     */
    public function testRequestResponseStrategyRouteCreateFromGlobalsRequest()
    {

        $collection = new Route\RouteCollection();
        $collection->setStrategy(new RequestResponseStrategy);

        $_GET = ['test' => 1];

        $collection->get('/route', function ($request, $response) {
            $this->assertEquals(1, $request->query->get('test'));
            return $response;
        });

        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route');

    }

    /**
     * Assert that the request object is taken from the container if it was already registered
     *
     * @return void
     */
    public function testRequestResponseStrategyRouteRequestFromContainer()
    {

        $container = new Container();
        $container->add('Symfony\Component\HttpFoundation\Request')->withArguments([['get' => 2], ['post' => 3]]);
        $collection = new Route\RouteCollection($container);
        $collection->setStrategy(new RequestResponseStrategy);

        $collection->get('/route', function ($request, $response) {
            $this->assertEquals(2, $request->query->get('get'));
            $this->assertEquals(3, $request->request->get('post'));
            return $response;
        });

        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route');

    }

    /**
     * Assert that the request object is taken from the container if it was already registered as a singleton
     *
     * @return void
     */
    public function testRequestResponseStrategyRouteSingletonRequestFromContainer()
    {

        $container = new Container();
        $container->add('Symfony\Component\HttpFoundation\Request', new Request(['get' => 2], ['post' => 3]));
        $collection = new Route\RouteCollection($container);
        $collection->setStrategy(new RequestResponseStrategy);

        $collection->get('/route', function ($request, $response) {
            $this->assertEquals(2, $request->query->get('get'));
            $this->assertEquals(3, $request->request->get('post'));
            return $response;
        });

        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route');

    }

    /**
     * Assert that the request object is taken from the service provider
     *
     * @return void
     */
    public function testRequestResponseStrategyRouteRequestFromServiceProvider()
    {

        $container = new Container();

        //Build mock provider
        $provider = $this->createMock(ServiceProvider::class);

        $provider
                  ->method('provides')
                  ->willReturnCallback(function () {
                      $args = func_get_args();
                      return $args[0] === 'Symfony\Component\HttpFoundation\Request';
                  });

        $provider->expects($this->once())
                  ->method('register')
                  ->will($this->returnCallback(function() use ($container) {
                      $container->add(Request::class, null, true)->withArguments([['get' => 4], ['post' => 5]]);
                  }));

        $container->addServiceProvider($provider);

        $collection = new Route\RouteCollection($container);
        $collection->setStrategy(new RequestResponseStrategy);

        $collection->get('/route', function ($request, $response) {
            $this->assertEquals(4, $request->query->get('get'));
            $this->assertEquals(5, $request->request->get('post'));
            return $response;
        });

        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route');

    }

    /**
     * Asserts that a 404 response is returned whilst using restful strategy
     *
     * @return void
     */
    public function testDispatcherHandles404CorrectlyOnRestfulStrategy()
    {
        $collection = new Route\RouteCollection;
        $collection->setStrategy(new RestfulStrategy);

        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route');

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertSame('{"status_code":404,"message":"Not Found"}', $response->getContent());
        $this->assertSame(404, $response->getStatusCode());
    }

    /**
     * Asserts that a 404 exception is thrown whilst using standard strategies
     */
    public function testDispatcherHandles404CorrectlyOnStandardStrategies(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Not Found');
        $this->expectExceptionCode(0);

        $collection = new Route\RouteCollection;
        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route');
    }

    /**
     * Asserts that a 405 response is returned whilst using restful strategy
     *
     * @return void
     */
    public function testDispatcherHandles405CorrectlyOnRestfulStrategy()
    {
        $collection = new Route\RouteCollection;
        $collection->setStrategy(new RestfulStrategy);

        $collection->post('/route', 'handler');
        $collection->put('/route', 'handler');
        $collection->delete('/route', 'handler');

        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route');

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertSame('{"status_code":405,"message":"Method Not Allowed"}', $response->getContent());
        $this->assertSame(405, $response->getStatusCode());
        $this->assertTrue($response->headers->has('Allow'));
        $this->assertSame('POST, PUT, DELETE', $response->headers->get('Allow'));
    }

    /**
     * Asserts that a 405 exception is thrown whilst using standard strategies
     */
    public function testDispatcherHandles405CorrectlyOnStandardStrategies(): void
    {
        $this->expectException(MethodNotAllowedException::class);
        $this->expectExceptionMessage('Method Not Allowed');
        $this->expectExceptionCode(0);

        $collection = new Route\RouteCollection;

        $collection->post('/route', 'handler');
        $collection->put('/route', 'handler');
        $collection->delete('/route', 'handler');

        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route');
    }

    /**
     * Asserts that a custom strategy is dispatched correctly and the return of that
     * method bubbles out to the dispatcher
     */
    public function testCustomStrategyIsDispatchedCorrectly(): void
    {
        $mockStrategy = $this->createMock(StrategyInterface::class);

        $mockStrategy->expects($this->once())
                     ->method('dispatch')
                     ->with($this->equalTo(['Controller', 'method']), $this->equalTo(['id' => 2, 'name' => 'phil']))
                     ->willReturn(['id' => 2, 'name' => 'phil']);

        $collection = new Route\RouteCollection;
        $collection->get('/route/{id}/{name}', 'Controller::method', $mockStrategy);

        $dispatcher = $collection->getDispatcher();
        $response = $dispatcher->dispatch('GET', '/route/2/phil');

        $this->assertSame(['id' => 2, 'name' => 'phil'], $response);
    }
}
