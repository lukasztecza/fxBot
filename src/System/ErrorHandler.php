<?php
namespace TinyApp\System;

class ErrorHandler
{
    public function __construct(string $environment)
    {
        error_reporting(E_ALL & ~E_USER_NOTICE);
        if ('prod' === $environment) {
            set_error_handler([$this, 'handleError']);
            set_exception_handler([$this, 'handleException']);
            register_shutdown_function([$this, 'handleShutDown']);
        }
    }

    public function log(int $type, string $message, string $file, int $line, string $reason, array $context)
    {
        //@TODO log this in tmp/logs
        var_export([$type, $message, $file, $line, $reason, $context]);
    }

    public function handleShutDown()
    {
        $error = error_get_last();
        if ($error) {
            $this->log($error["type"], $error["message"], $error["file"], $error["line"], 'Error', ['Got on shutdown']);
        }
    }

    public function handleErrorm(int $type = null, string $message = null, string $file = null, int $line = null, array $context = [])
    {
        if (!(error_reporting() & $type)) {
            // This error code is not included in error_reporting shutting down
            $this->log($type, $message, $file, $line, 'Error-ignored', $context);
            return;
        }

        $this->log($type, $message, $file, $line, 'Error', $context);
        exit;
    }

    public function handleException(\Throwable $exception)
    {
        $this->log(
            $exception->getCode(),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            get_class($exception),
            $exception->getTrace()
        );
        exit;
    }
}
