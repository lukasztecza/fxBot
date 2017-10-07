<?php
namespace TinyApp\System;

class ErrorHandler
{
    public function __construct(string $environment)
    {
        error_reporting(E_ALL);
        if ('prod' === $environment) {
            set_error_handler([$this, 'handleError']);
            set_exception_handler([$this, 'handleException']);
            register_shutdown_function([$this, 'handleShutDown']);
        }
    }

    public function log(int $type, string $message, string $file, int $line, string $reason)
    {
        var_export([$type, $message, $file, $line, $reason]);

    }

    public function handleShutDown()
    {
        $error = error_get_last();
        if ($error) {
            $this->log($error["type"], $error["message"], $error["file"], $error["line"], 'shutdown');
        }
    }

    public function handleError(int $type = null, string $message = null, string $file = null, int $line = null)
    {
        if (!(error_reporting() & $type)) {
            // This error code is not included in error_reporting shutting down
            $this->log($type, $message, $file, $line, 'ignored_error_type');
            exit;
        }

        $this->log($type, $message, $file, $line, 'error');
        exit;
    }

    public function handleException(\Throwable $exception)
    {
        $this->log($exception->getCode(), $exception->getMessage(), $exception->getFile(), $exception->getLine(), get_class($exception));
        exit;
    }
}
