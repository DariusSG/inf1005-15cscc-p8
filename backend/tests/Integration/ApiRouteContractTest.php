<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

class ApiRouteContractTest extends TestCase
{
    public function test_all_declared_api_routes_map_to_public_controller_actions(): void
    {
        $indexPath = __DIR__ . '/../../public/index.php';
        $source = file_get_contents($indexPath);

        $this->assertNotFalse($source, 'Failed to read backend/public/index.php');

        preg_match_all(
            '/\$router->(get|post)\(\'([^\']+)\',\s*\'([A-Za-z0-9_]+Controller@[A-Za-z0-9_]+)\'\);/',
            $source,
            $matches,
            PREG_SET_ORDER
        );

        $this->assertNotEmpty($matches, 'No API route declarations were found.');
        $this->assertCount(28, $matches, 'Expected 28 API routes from backend/public/index.php.');

        $signatures = [];

        foreach ($matches as $match) {
            $httpMethod = strtoupper($match[1]);
            $path = $match[2];
            $handler = $match[3];

            $signature = $httpMethod . ' /api/v1' . $path;
            $this->assertArrayNotHasKey($signature, $signatures, 'Duplicate route signature found: ' . $signature);
            $signatures[$signature] = true;

            [$controller, $action] = explode('@', $handler, 2);
            $fqcn = 'App\\Controllers\\' . $controller;

            $this->assertTrue(class_exists($fqcn), 'Controller class not found for route ' . $signature . ': ' . $fqcn);
            $this->assertTrue(method_exists($fqcn, $action), 'Controller action missing for route ' . $signature . ': ' . $handler);

            $reflection = new \ReflectionMethod($fqcn, $action);
            $this->assertTrue($reflection->isPublic(), 'Controller action must be public: ' . $handler);
        }
    }
}
