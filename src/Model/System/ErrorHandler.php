<?php
namespace TinyApp\Model\System;

class ErrorHandler
{
    private const CONTENT_TYPE_JSON = 'application/json';
    private const CONTENT_TYPE_HTML = 'text/html';
    private const LOGS_PATH = APP_ROOT_DIR . '/tmp/logs';
    private const PRODUCTION_ENVIRONMENT = 'prod';

    private $defaultContentType;

    public function __construct(string $environment, string $defaultContentType = null)
    {
        // Set default content type
        $this->defaultContentType = $defaultContentType;

        // Set custom error/exception/shutdown handling for production environment
        if (self::PRODUCTION_ENVIRONMENT === $environment) {
            // Address all errors/warnings/notices except E_USER_NOTICE which will be logged only
            error_reporting(E_ALL & ~E_USER_NOTICE);
            set_error_handler([$this, 'handleError']);
            set_exception_handler([$this, 'handleException']);
            register_shutdown_function([$this, 'handleShutDown']);
            return;
        }

        // Other environments should report everything
        error_reporting(E_ALL);
    }

    private function log(int $type, string $message, string $file, int $line, string $reason) : void
    {
        // Sanitize message variables to prevent log injections
        $message = json_encode($message);
        $message = preg_replace(['/[^a-zA-Z0-9 ]/', '/_{1,}/'], '_', $message);

        // Create separate log file per day
        if (!file_exists(self::LOGS_PATH)) {
            mkdir(self::LOGS_PATH, 0775, true);
        }
        file_put_contents(
            self::LOGS_PATH . '/' . 'php-' . date('Y-m-d') . '.log',
            date('Y-m-d H:i:s') . ' | ' . $reason .  ' | code: ' . $type . ' | file: ' . $file . ' | line: ' . $line .
            ' | with message: ' . $message . PHP_EOL . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }

    public function handleShutDown() : void
    {
        $error = error_get_last();
        if ($error) {
            $this->log($error["type"], $error["message"], $error["file"], $error["line"], 'Shutdown Error');
        }
    }

    public function handleError(int $type = null, string $message = null, string $file = null, int $line = null, array $context = []) : void
    {
        if (!(error_reporting() & $type)) {
            // This error code is not included in error_reporting so just log it
            $this->log($type, $message, $file, $line, 'Info');
            return;
        }

        $this->log($type, $message, $file, $line, 'Error');
        $this->displayErorPage($type);
    }

    public function handleException(\Throwable $exception) : void
    {
        $this->log(
            $exception->getCode(),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            get_class($exception)
        );
        $this->displayErorPage($exception->getCode());
    }

    private function displayErorPage(int $code = null) : void
    {
        // Display error page according to default content type
        switch ($this->defaultContentType) {
            case self::CONTENT_TYPE_JSON:
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'code' => $code]);
                break;
            case self::CONTENT_TYPE_HTML:
                echo
                    '<!Doctype html>' .
                    '<html>' .
                    '<head><meta charset="utf-8"><meta name="robots" content="noindex, nofollow"></head>' .
                    '<body><p>Status: error</p><p>Code: ' . $code . '</p><p><a href="/">Go to home page</a></p></body>' .
                    '</html>'
                ;
                break;
            default:
                echo 'Could not finish because of error code ' . $code . ', see logs for details' . PHP_EOL;
        }
        exit;
    }
}
