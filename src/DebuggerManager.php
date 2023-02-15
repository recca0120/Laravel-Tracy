<?php

namespace Recca0120\LaravelTracy;

use ErrorException;
use Exception;
use Illuminate\Support\Arr;
use Recca0120\LaravelTracy\Session\DeferredContent;
use Recca0120\LaravelTracy\Session\Session;
use Throwable;
use Tracy\Bar;
use Tracy\BlueScreen;
use Tracy\Debugger;
use Tracy\Helpers;

class DebuggerManager
{
    /**
     * @var array
     */
    private $config;

    /**
     * $bar.
     *
     * @var Bar
     */
    private $bar;

    /**
     * @var BlueScreen
     */
    private $blueScreen;

    /**
     * $session.
     *
     * @var Session
     */
    private $session;

    /**
     * @var null
     */
    private $url;

    /**
     * @var DeferredContent
     */
    private $defer;

    /**
     * __construct.
     *
     * @param  array  $config
     * @param  Bar  $bar
     * @param  BlueScreen  $blueScreen
     * @param  DeferredContent  $defer
     * @param  null  $url
     */
    public function __construct($config, BlueScreen $blueScreen, Bar $bar, $defer, $url = null)
    {
        $this->config = $config;
        $this->blueScreen = $blueScreen;
        $this->bar = $bar;
        $this->defer = $defer;
        $this->url = $url;
    }

    /**
     * init.
     *
     * @param  array  $config
     * @return array
     */
    public static function init($config = [])
    {
        $config = array_merge([
            'accepts' => [],
            'appendTo' => 'body',
            'showBar' => false,
            'editor' => Debugger::$editor,
            'maxDepth' => Debugger::$maxDepth,
            'maxLength' => Debugger::$maxLength,
            'scream' => true,
            'showLocation' => true,
            'strictMode' => true,
            'currentTime' => array_key_exists('REQUEST_TIME_FLOAT', $_SERVER) ? $_SERVER['REQUEST_TIME_FLOAT'] : microtime(true),
            'editorMapping' => isset(Debugger::$editorMapping) === true ? Debugger::$editorMapping : [],
        ], $config);

        Debugger::$editor = $config['editor'];
        Debugger::$maxDepth = $config['maxDepth'];
        Debugger::$maxLength = $config['maxLength'];
        Debugger::$scream = $config['scream'];
        Debugger::$showLocation = $config['showLocation'];
        Debugger::$strictMode = $config['strictMode'];
        Debugger::$time = $config['currentTime'];

        if (isset(Debugger::$editorMapping) === true) {
            Debugger::$editorMapping = $config['editorMapping'];
        }

        return $config;
    }

    /**
     * enabled.
     *
     * @return bool
     */
    public function enabled()
    {
        return Arr::get($this->config, 'enabled', true) === true;
    }

    /**
     * showBar.
     *
     * @return bool
     */
    public function showBar()
    {
        return Arr::get($this->config, 'showBar', true) === true;
    }

    /**
     * accepts.
     *
     * @return array
     */
    public function accepts()
    {
        return Arr::get($this->config, 'accepts', []);
    }

    /**
     * dispatchAssets.
     *
     * @param  string  $type
     * @return array
     */
    public function dispatchAssets($type)
    {
        switch ($type) {
            case 'css':
            case 'js':
                $headers = [
                    'Content-Type' => $type === 'css' ? 'text/css; charset=utf-8' : 'text/javascript; charset=utf-8',
                    'Cache-Control' => 'max-age=86400',
                ];
                $content = $this->renderBuffer(function () {
                    $this->defer->sendAssets();
                });
                break;
            default:
                $headers = ['Content-Type' => 'text/javascript; charset=utf-8'];
                $content = $this->dispatch();
        }

        return [array_merge($headers, ['Content-Length' => strlen($content)]), $content];
    }

    /**
     * dispatch.
     *
     * @return string
     */
    public function dispatch()
    {
        $this->defer->isAvailable();

        return $this->renderBuffer(function () {
            $this->defer->sendAssets();
        });
    }

    /**
     * shutdownHandler.
     *
     * @param  string  $content
     * @param  bool  $ajax
     * @param  int  $error
     * @return string
     */
    public function shutdownHandler($content, $ajax = false, $error = null)
    {
        $error = $error ?: error_get_last();
        if (is_array($error) && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE, E_RECOVERABLE_ERROR, E_USER_ERROR], true)) {
            return $this->exceptionHandler(
                Helpers::fixStack(new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']))
            );
        }

        return array_reduce(['renderLoader', 'renderBar'], function ($content, $method) use ($ajax) {
            return $this->$method($content, $ajax);
        }, $content);
    }

    /**
     * exceptionHandler.
     *
     * @param  Exception  $exception
     * @return string
     */
    public function exceptionHandler(Throwable $exception)
    {
        return $this->renderBuffer(function () use ($exception) {
            Helpers::improveException($exception);
            $this->blueScreen->render($exception);
        });
    }

    /**
     * renderLoader.
     *
     * @param  string  $content
     * @param  bool  $ajax
     * @return string
     */
    private function renderLoader($content, $ajax = false)
    {
        if ($ajax === true || $this->defer->isAvailable() === false) {
            return $content;
        }

        return $this->render($content, 'renderLoader', ['head', 'body']);
    }

    /**
     * renderBar.
     *
     * @param  string  $content
     * @return string
     */
    private function renderBar($content)
    {
        $tag = Arr::get($this->config, 'appendTo', 'body');

        return $this->render($content, 'render', [$tag, 'body']);
    }

    /**
     * render.
     *
     * @param  string  $content
     * @param  string  $method
     * @param  string[]  $appendTags
     * @return string
     */
    private function render($content, $method, $appendTags = ['body'])
    {
        $appendHtml = $this->renderBuffer(function () use ($method) {
            $requestUri = Arr::get($_SERVER, 'REQUEST_URI');
            Arr::set($_SERVER, 'REQUEST_URI', '');
            call_user_func([$this->bar, $method], $this->defer);
            Arr::set($_SERVER, 'REQUEST_URI', $requestUri);
        });

        $appendTags = array_unique($appendTags);

        foreach ($appendTags as $appendTag) {
            $pos = strripos($content, '</'.$appendTag.'>');

            if ($pos !== false) {
                return substr_replace($content, $appendHtml, $pos, 0);
            }
        }

        return $content.$appendHtml;
    }

    /**
     * renderBuffer.
     *
     * @param  callable  $callback
     * @return string
     */
    private function renderBuffer(callable $callback)
    {
        ob_start();
        $callback();

        return $this->replacePath(ob_get_clean());
    }

    /**
     * replacePath.
     *
     * @param  string  $content
     * @return string
     */
    private function replacePath($content)
    {
        return $this->url
            ? str_replace('?_tracy_bar', $this->url.'?_tracy_bar', $content)
            : $content;
    }
}
