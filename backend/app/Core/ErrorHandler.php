<?php

namespace App\Core;

use Throwable;

class ErrorHandler
{
    public static function register(): void
    {
        // 1. Convert standard PHP errors (warnings, notices) into Exceptions
        set_error_handler(function ($severity, $message, $file, $line) {
            if (!(error_reporting() & $severity)) return;
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        // 2. Handle all uncaught Exceptions
        set_exception_handler([self::class, 'handleException']);

        // 3. Handle Fatal Shutdown Errors
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleException(Throwable $e): void
    {
        $requestId = uniqid('req_', false);
        $isDebug = Helpers::config('app.debug', false);

        // Log everything to the file
        Logger::channel()->error($e->getMessage(), [
            'type'       => get_class($e),
            'file'       => $e->getFile(),
            'line'       => $e->getLine(),
            'path'       => Request::uri(),
            'method'     => Request::method(),
            'user_id'    => Request::context('user_id'),
            'request_id' => $requestId,
            'trace'      => $isDebug ? $e->getTrace() : 'Redacted',
        ]);

        // Clear any previous output buffers to ensure a clean JSON response
        if (ob_get_length()) ob_clean();

        $response = [
            'error'      => true,
            'message'    => $isDebug ? $e->getMessage() : 'Internal Server Error',
            'request_id' => $requestId
        ];

        if ($isDebug) {
            $response['debug'] = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => array_slice($e->getTrace(), 0, 5) // First 5 steps
            ];
        }

        Response::json($response, 500);
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();
        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];

        if ($error && in_array($error['type'], $fatalTypes)) {
            // Manually trigger the exception handler logic for fatal
            $exception = new \ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            );
            self::handleException($exception);
        }
    }
}