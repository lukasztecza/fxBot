<?php
namespace TinyApp\Model\System;

class ErrorHandler
{
    const DEFAULT_CONTENT_TYPE = 'application/json';

    const CONTENT_TYPE_JSON = 'application_/json';
    const CONTENT_TYPE_HTML = 'text/html';

    const LOGS_PATH = APP_ROOT_DIR . '/tmp/logs/';

    public function __construct(string $environment)
    {
        // Set custom error/exception/shutdown handling for production environment
        if ('prod' === $environment) {
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

    private function log(int $type, string $message, string $file, int $line, string $reason, array $context) : void
    {
        // Sanitize context and message variables to prevent log injections
        $context = json_encode($context);
        $message = json_encode($message);
        list($context, $message) = preg_replace(['/[^a-zA-Z0-9 ]/', '/_{1,}/'], '_', [$context, $message]);

        // Create separate log file per day
        if (!file_exists(self::LOGS_PATH)) {
            mkdir(self::LOGS_PATH, 0775, true);
        }
        file_put_contents(
            self::LOGS_PATH . 'php-' . date('Y-m-d') . '.log',
            date('Y-m-d H:i:s') . ' | ' . $reason .  ' | code: ' . $type . ' | file: ' . $file . ' | line: ' . $line .
            ' | with message: ' . $message . ' | with context: ' . $context . PHP_EOL . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }

    public function handleShutDown() : void
    {
        $error = error_get_last();
        if ($error) {
            $this->log($error["type"], $error["message"], $error["file"], $error["line"], 'Error', ['Got on shutdown']);
        }
    }

    public function handleError(int $type = null, string $message = null, string $file = null, int $line = null, array $context = []) : void
    {
        if (!(error_reporting() & $type)) {
            // This error code is not included in error_reporting so just log it
            $this->log($type, $message, $file, $line, 'Ignored', $context);
            return;
        }

        $this->log($type, $message, $file, $line, 'Error', $context);
        $this->displayErorPage($type);
    }

    public function handleException(\Throwable $exception) : void
    {
        $this->log(
            $exception->getCode(),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            get_class($exception),
            $exception->getTrace()
        );
        $this->displayErorPage($exception->getCode());
    }

    private function displayErorPage(int $type = null) : void
    {
        // Display error page according to default content type
        switch (self::DEFAULT_CONTENT_TYPE) {
            case self::CONTENT_TYPE_JSON:
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'code' => $type]);
                break;
            case self::CONTENT_TYPE_HTML:
            default:
                echo
                    '<!Doctype html>' .
                    '<html>' .
                    '<head><meta charset="utf-8"><meta name="robots" content="noindex, nofollow"></head>' .
                    '<body><p>Status: error</p><p>Code: ' . $type . '</p></body>' .
                    '</html>'
                ;
                break;
        }
        exit;
    }
}
