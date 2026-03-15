<?php

namespace App\Core;

class ErrorHandler
{
    public static function register()
    {
        set_exception_handler(function ($e) {
            // Log the error
            Log::channel()->error($e->getMessage(), [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'path'  => Request::uri(),
                'method' => Request::method(),
                'user_id' => Request::context('user_id') ?? 'null'
            ]);

            // Send generic JSON response
            Response::json([
                "error" => true,
                "message" => "Internal Server Error"
            ], 500);
        });

        // Catch fatal errors (optional, for shutdown)
        register_shutdown_function(function () {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                Log::channel()->error($error['message'], [
                    'file' => $error['file'],
                    'line' => $error['line']
                ]);

                http_response_code(500);
                header("Content-Type: application/json");
                echo json_encode([
                    "error" => true,
                    "message" => "Internal Server Error"
                ]);
                exit;
            }
        });
    }
}