<?php

declare(strict_types=1);

namespace App\Core;

use ErrorException;
use Throwable;

class ErrorHandler
{
    private string $logDirectory;
    private bool $hasHandled = false;
    private bool $debug;

    public function __construct(?string $logDirectory = null, ?bool $debug = null)
    {
        $this->logDirectory = $logDirectory ?? dirname(__DIR__) . '/../storage/logs';
        $this->debug = $debug ?? $this->detectDebugMode();
    }

    public function register(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', $this->debug ? '1' : '0');

        if (!is_dir($this->logDirectory)) {
            mkdir($this->logDirectory, 0775, true);
        }

        ini_set('log_errors', '1');
        ini_set('error_log', $this->getLogFilePath());

        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public function handleError(int $severity, string $message, string $file, int $line): bool
    {
        $this->log(sprintf('Error [%d]: %s in %s on line %d', $severity, $message, $file, $line));
        throw new ErrorException($message, 0, $severity, $file, $line);
    }

    public function handleException(Throwable $throwable): void
    {
        $reference = $this->generateReference();
        $this->hasHandled = true;
        $this->log(sprintf(
            '[%s] %s: %s in %s on line %d%s',
            $reference,
            $throwable::class,
            $throwable->getMessage(),
            $throwable->getFile(),
            $throwable->getLine(),
            $throwable->getTraceAsString() !== '' ? PHP_EOL . $throwable->getTraceAsString() : ''
        ));

        http_response_code(500);

        if ($this->debug) {
            if (!headers_sent()) {
                header('Content-Type: text/html; charset=utf-8');
            }

            echo $this->renderDebugException($throwable, $reference);
            return;
        }

        if (!headers_sent()) {
            header('Content-Type: text/plain; charset=utf-8');
        }

        echo 'Ha ocurrido un error inesperado. Código de referencia: ' . $reference;
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error === null || $this->hasHandled) {
            return;
        }

        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if (!in_array($error['type'], $fatalTypes, true)) {
            return;
        }

        $reference = $this->generateReference();
        $this->log(sprintf(
            '[%s] Shutdown error [%d]: %s in %s on line %d',
            $reference,
            $error['type'],
            $error['message'],
            $error['file'],
            $error['line']
        ));

        if ($this->debug) {
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: text/html; charset=utf-8');
            }

            echo $this->renderShutdownDebug($error, $reference);
            return;
        }

        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
        }

        echo 'Ha ocurrido un error inesperado. Código de referencia: ' . $reference;
    }

    private function log(string $message): void
    {
        $logFile = $this->getLogFilePath();
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, sprintf("[%s] %s%s", $timestamp, $message, PHP_EOL), FILE_APPEND);
    }

    private function generateReference(): string
    {
        return date('YmdHis') . '-' . bin2hex(random_bytes(3));
    }

    private function getLogFilePath(): string
    {
        return rtrim($this->logDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'app.log';
    }

    private function detectDebugMode(): bool
    {
        $appEnv = strtolower((string)($_ENV['APP_ENV'] ?? getenv('APP_ENV') ?? ''));
        if ($appEnv !== '' && in_array($appEnv, ['development', 'local', 'dev'], true)) {
            return true;
        }

        $debugValue = $_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG');
        if ($debugValue === false || $debugValue === null) {
            return false;
        }

        return filter_var((string)$debugValue, FILTER_VALIDATE_BOOLEAN);
    }

    private function renderDebugException(Throwable $throwable, string $reference): string
    {
        $message = htmlspecialchars($throwable->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $file = htmlspecialchars($throwable->getFile(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $line = $throwable->getLine();
        $trace = htmlspecialchars($throwable->getTraceAsString(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Error de aplicación</title>
    <style>
        body { font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background: #f8f9fa; color: #212529; padding: 2rem; }
        .panel { background: #fff; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); padding: 2rem; max-width: 960px; margin: 0 auto; }
        pre { background: #1e1e1e; color: #f8f8f2; padding: 1rem; border-radius: 6px; overflow-x: auto; }
        h1 { margin-top: 0; font-size: 1.75rem; }
        .meta { margin-bottom: 1rem; }
        .meta strong { display: inline-block; width: 110px; }
    </style>
</head>
<body>
    <div class="panel">
        <h1>Excepción no controlada</h1>
        <div class="meta"><strong>Referencia:</strong> {$reference}</div>
        <div class="meta"><strong>Mensaje:</strong> {$message}</div>
        <div class="meta"><strong>Archivo:</strong> {$file}</div>
        <div class="meta"><strong>Línea:</strong> {$line}</div>
        <h2>Stack trace</h2>
        <pre>{$trace}</pre>
    </div>
</body>
</html>
HTML;
    }

    /**
     * @param array{type:int,message:string,file:string,line:int} $error
     */
    private function renderShutdownDebug(array $error, string $reference): string
    {
        $type = $error['type'];
        $message = htmlspecialchars($error['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $file = htmlspecialchars($error['file'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $line = $error['line'];

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Error fatal</title>
    <style>
        body { font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background: #f8f9fa; color: #212529; padding: 2rem; }
        .panel { background: #fff; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); padding: 2rem; max-width: 960px; margin: 0 auto; }
        h1 { margin-top: 0; font-size: 1.75rem; }
        .meta { margin-bottom: .75rem; }
        .meta strong { display: inline-block; width: 140px; }
    </style>
</head>
<body>
    <div class="panel">
        <h1>Error fatal detectado</h1>
        <div class="meta"><strong>Referencia:</strong> {$reference}</div>
        <div class="meta"><strong>Tipo:</strong> {$type}</div>
        <div class="meta"><strong>Mensaje:</strong> {$message}</div>
        <div class="meta"><strong>Archivo:</strong> {$file}</div>
        <div class="meta"><strong>Línea:</strong> {$line}</div>
    </div>
</body>
</html>
HTML;
    }
}
