<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Middleware\JwtMiddleware;
use App\Repositories\ModuleRepository;
use App\Repositories\ReviewRepository;

class ModuleController
{
    /**
     * @OA\Get(path="/modules", summary="List all modules",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Array of modules")
     * )
     */
    public function index()
    {
        $modules = ModuleRepository::all();
        Response::json($modules);
    }

    /**
     * @OA\Get(path="/modules/{code}", summary="Get module with reviews",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="code", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Module with reviews"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(string $code)
    {
        $module = ModuleRepository::findByCode(strtoupper($code));
        if (!$module) {
            Response::json(['error' => 'Module not found'], 404);
        }

        $userId  = JwtMiddleware::userId() ?? 0;
        $reviews = ReviewRepository::forModule($module->code, $userId);

        Response::json(array_merge($module->toArray(), ['reviews' => $reviews]));
    }
}