<?php

namespace Recca0120\LaravelTracy\Exceptions;

use Exception;
use Illuminate\Contracts\View\View;
use Recca0120\LaravelTracy\BlueScreen;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

class Handler implements ExceptionHandler
{
    /**
     * app exception handler.
     *
     * @var \Illuminate\Contracts\Debug\ExceptionHandler
     */
    protected $exceptionHandler;

    /**
     * $blueScreen.
     *
     * @var \Recca0120\LaravelTracy\BlueScreen
     */
    protected $blueScreen;

    /**
     * __construct.
     *
     * @method __construct
     *
     * @param \Illuminate\Contracts\Debug\ExceptionHandler $exceptionHandler
     * @param \Recca0120\LaravelTracy\BlueScreen           $blueScreen
     */
    public function __construct(ExceptionHandler $exceptionHandler, BlueScreen $blueScreen)
    {
        $this->exceptionHandler = $exceptionHandler;
        $this->blueScreen = $blueScreen;
    }

    /**
     * Report or log an exception.
     *
     * @param \Exception $e
     */
    public function report(Exception $e)
    {
        if (is_null($this->exceptionHandler) === false) {
            $this->exceptionHandler->report($e);
        }
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Exception               $e
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Exception $e)
    {
        $response = $this->exceptionHandler->render($request, $e);
        if ($response instanceof RedirectResponse || $response instanceof JsonResponse) {
            return $response;
        }

        $content = $response->getContent();
        if ($content instanceof View) {
            return $response;
        }

        $response->setContent($this->blueScreen->render($e));

        return $response;
    }

    /**
     * Render an exception to the console.
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param \Exception                                        $e
     */
    public function renderForConsole($output, Exception $e)
    {
        if (is_null($this->exceptionHandler) === false) {
            $this->exceptionHandler->renderForConsole($output, $e);
        }
    }
}
