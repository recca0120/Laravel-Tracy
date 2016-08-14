<?php

use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mockery as m;
use Recca0120\LaravelTracy\Exceptions\Handler;
use Recca0120\LaravelTracy\Middleware\AppendDebugbar;
use Recca0120\LaravelTracy\ServiceProvider;
use Recca0120\LaravelTracy\Tracy;
use Recca0120\Terminal\ServiceProvider as TerminalServiceProvider;

class ServiceProviderTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function test_register()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */

        $configData = [
            'enabled'      => true,
            'showBar'      => true,
            'accepts'      => [
                'text/html',
            ],
            'editor'       => 'subl://open?url=file://%file&line=%line',
            'maxDepth'     => 4,
            'maxLength'    => 1000,
            'scream'       => true,
            'showLocation' => true,
            'strictMode'   => true,
            'panels'       => [
                'routing'  => true,
                'database' => true,
                'view'     => true,
                'event'    => false,
                'session'  => true,
                'request'  => true,
                'auth'     => true,
                'terminal' => true,
            ],
        ];

        $config = m::mock(ConfigContract::class);
        $app = m::mock(ApplicationContract::class.','.ArrayAccess::class);
        $provider = new ServiceProvider($app);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        $config
            ->shouldReceive('get')->with('tracy', [])->once()->andReturn($configData)
            ->shouldReceive('set')->with('tracy', $configData)->once()
            ->shouldReceive('get')->with('tracy')->once()->andReturn($configData)
            ->shouldReceive('get')->with('tracy.panels.terminal')->once()->andReturn(true);

        $app
            ->shouldReceive('offsetGet')->with('config')->times(4)->andReturn($config)
            ->shouldReceive('singleton')->with(Tracy::class, m::type(Closure::class))->once()->andReturnUsing(function ($className, $closure) {
                return $closure(m::self());
            })
            ->shouldReceive('register')->with(TerminalServiceProvider::class)->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */

        $provider->register();
        $provider->provides();
    }

    public function test_boot()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */

        $request = m::mock(Request::class);
        $response = m::mock(Response::class);
        $tracy = m::mock(Tracy::class);
        $exceptionHandler = m::mock(ExceptionHandlerContract::class);
        $kernel = m::mock(HttpKernelContract::class);
        $app = m::mock(ApplicationContract::class.','.ArrayAccess::class);
        $provider = new ServiceProvider($app);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        $tracy
            ->shouldReceive('initialize')->once()->andReturn(true);

        $kernel->shouldReceive('pushMiddleware')->with(AppendDebugbar::class)->once();

        $app
            ->shouldReceive('configPath')->once()->andReturn(__DIR__)
            ->shouldReceive('extend')->with(ExceptionHandlerContract::class, m::type(Closure::class))->once()->andReturnUsing(function ($className, $closure) use ($exceptionHandler) {
                return $closure($exceptionHandler, m::self());
            })
            ->shouldReceive('make')->with(Handler::class, [
                'exceptionHandler' => $exceptionHandler,
            ])->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */

        $provider->boot($tracy, $kernel);
    }
}
