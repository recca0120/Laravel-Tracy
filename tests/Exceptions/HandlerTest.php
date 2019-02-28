<?php

namespace Recca0120\LaravelTracy\Tests\Exceptions;

use Exception;
use Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\TestCase;
use Recca0120\LaravelTracy\Exceptions\Handler;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class HandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testRenderResponseWithViewReturnsView()
    {
        $handler = new Handler(
            $exceptionHandler = m::mock('Illuminate\Contracts\Debug\ExceptionHandler'),
            $debuggerManager = m::mock('Recca0120\LaravelTracy\DebuggerManager')
        );

        $view = m::mock('Illuminate\View\View');
        $view->shouldReceive('render')->once()->andReturn('Some rendered view string');

        $exceptionHandler->shouldReceive('render')
            ->once()
            ->with(
                $request = m::mock('Illuminate\Http\Request'),
                $exception = new Exception()
            )->andReturn(
                $response = new Response($view)
            );

        // Response returned from render,
        $this->assertSame($response, $handler->render($request, $exception));
    }

    public function testReport()
    {
        $handler = new Handler(
            $exceptionHandler = m::mock('Illuminate\Contracts\Debug\ExceptionHandler'),
            $debuggerManager = m::mock('Recca0120\LaravelTracy\DebuggerManager')
        );
        $exception = new Exception();
        $exceptionHandler->shouldReceive('report')->once()->with($exception);
        $this->assertNull($handler->report($exception));
    }
    
    public function testShouldReport()
    {
        $handler = new Handler(
            $exceptionHandler = m::spy('Illuminate\Contracts\Debug\ExceptionHandler'),
            $debuggerManager = m::mock('Recca0120\LaravelTracy\DebuggerManager')
        );
        
        $exceptionHandler->shouldReceive('shouldReport')->andReturn(true);
        
        $exception = new Exception();
        $this->assertTrue($handler->shouldReport($exception));
        
        $exceptionHandler->shoulHavedReceived('shouldReport')->once()->with($exception);
    }

    public function testRednerWithResponseIsRedirectResponse()
    {
        $handler = new Handler(
            $exceptionHandler = m::mock('Illuminate\Contracts\Debug\ExceptionHandler'),
            $debuggerManager = m::mock('Recca0120\LaravelTracy\DebuggerManager')
        );
        $exceptionHandler->shouldReceive('render')
            ->once()
            ->with(
                $request = m::mock('Illuminate\Http\Request'),
                $exception = new Exception()
            )->andReturn(
                $response = m::mock('Symfony\Component\HttpFoundation\RedirectResponse')
            );
        $this->assertSame($response, $handler->render($request, $exception));
    }

    public function testRednerWithResponseIsJsonResponse()
    {
        $handler = new Handler(
            $exceptionHandler = m::mock('Illuminate\Contracts\Debug\ExceptionHandler'),
            $debuggerManager = m::mock('Recca0120\LaravelTracy\DebuggerManager')
        );
        $exceptionHandler->shouldReceive('render')
            ->once()
            ->with(
                $request = m::mock('Illuminate\Http\Request'),
                $exception = new Exception()
            )->andReturn(
                $response = m::mock('Symfony\Component\HttpFoundation\JsonResponse')
            );
        $this->assertSame($response, $handler->render($request, $exception));
    }

    public function testRednerWithResponseContentIsView()
    {
        $handler = new Handler(
            $exceptionHandler = m::mock('Illuminate\Contracts\Debug\ExceptionHandler'),
            $debuggerManager = m::mock('Recca0120\LaravelTracy\DebuggerManager')
        );
        $exceptionHandler->shouldReceive('render')
            ->once()
            ->with(
                $request = m::mock('Illuminate\Http\Request'),
                $exception = new Exception()
            )->andReturn(
                $response = m::mock('Symfony\Component\HttpFoundation\Response')
            );
        $response->shouldReceive('getContent')->once()->andReturn(
            m::mock('Illuminate\Contracts\View\View')
        );
        $this->assertSame($response, $handler->render($request, $exception));
    }

    public function testRenderRedirectResponse()
    {
        $handler = new Handler(
            $exceptionHandler = m::mock('Illuminate\Contracts\Debug\ExceptionHandler'),
            $debuggerManager = m::mock('Recca0120\LaravelTracy\DebuggerManager')
        );
        $exceptionHandler->shouldReceive('render')
            ->once()
            ->with(
                $request = m::mock('Illuminate\Http\Request'),
                $exception = new Exception()
            )->andReturn(
                $response = m::mock('Symfony\Component\HttpFoundation\RedirectResponse')
            );

        $this->assertSame($response, $handler->render($request, $exception));
    }

    public function testRenderJsonResponse()
    {
        $handler = new Handler(
            $exceptionHandler = m::mock('Illuminate\Contracts\Debug\ExceptionHandler'),
            $debuggerManager = m::mock('Recca0120\LaravelTracy\DebuggerManager')
        );
        $exceptionHandler->shouldReceive('render')
            ->once()
            ->with(
                $request = m::mock('Illuminate\Http\Request'),
                $exception = new Exception()
            )->andReturn(
                $response = m::mock('Symfony\Component\HttpFoundation\JsonResponse')
            );

        $this->assertSame($response, $handler->render($request, $exception));
    }

    public function testRenderView()
    {
        $handler = new Handler(
            $exceptionHandler = m::mock('Illuminate\Contracts\Debug\ExceptionHandler'),
            $debuggerManager = m::mock('Recca0120\LaravelTracy\DebuggerManager')
        );
        $exceptionHandler->shouldReceive('render')
            ->once()
            ->with(
                $request = m::mock('Illuminate\Http\Request'),
                $exception = new Exception()
            )->andReturn(
                $response = m::mock('Symfony\Component\HttpFoundation\Response')
            );
        $response->shouldReceive('getContent')->once()->andReturn(
            $view = m::mock('Illuminate\Contracts\View\View')
        );

        $this->assertSame($response, $handler->render($request, $exception));
    }

    public function testRender()
    {
        $handler = new Handler(
            $exceptionHandler = m::mock('Illuminate\Contracts\Debug\ExceptionHandler'),
            $debuggerManager = m::mock('Recca0120\LaravelTracy\DebuggerManager')
        );
        $exceptionHandler->shouldReceive('render')
            ->once()
            ->with(
                $request = Request::capture(),
                $exception = new Exception()
            )->andReturn(
                $response = m::mock('Symfony\Component\HttpFoundation\Response')
            );
        $response->shouldReceive('getContent')->once()->andReturn(null);
        $debuggerManager->shouldReceive('exceptionHandler')->once()->with($exception)->andReturn($content = 'foo');
        $response->shouldReceive('setContent')->once()->with($content);

        $_SERVER['foo'] = 'bar';

        $this->assertSame($response, $handler->render($request, $exception));
        $this->assertSame($_SERVER, $request->server());
    }

    public function testRenderForConsoleMethod()
    {
        $handler = new Handler(
            $exceptionHandler = m::mock('Illuminate\Contracts\Debug\ExceptionHandler'),
            $debuggerManager = m::mock('Recca0120\LaravelTracy\DebuggerManager')
        );
        $exceptionHandler->shouldReceive('renderForConsole')->once()->with(
            $output = m::mock('Symfony\Component\Console\Output\OutputInterface'),
            $exception = new Exception()
        );
        $this->assertNull($handler->renderForConsole($output, $exception));
    }
}
