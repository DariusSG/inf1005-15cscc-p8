<?php

namespace App\Middleware;

use App\Core\Response;

class RateLimitMiddleware
{
    public static function handle()
    {
        $ip = $_SERVER['REMOTE_ADDR'];

        $file = sys_get_temp_dir() . "/rate_$ip";

        $limit = 100;
        $window = 3600; // 1 hour
        $data = ['count' => 0, 'time' => time()];

        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if ($data['time'] + $window < time()) {
                $data = ['count' => 0, 'time' => time()];
            }
        }

        $data['count']++;
        file_put_contents($file, json_encode($data));


        header("X-RateLimit-Limit: $limit");
        header("X-RateLimit-Remaining: ".max(0, $limit - min($data['count'], $limit)));
        header("X-RateLimit-Reset: ".($data['time']+$window));

        if ($data['count'] > $limit) {
            Response::json(["error"=>"Too many requests"],429);
        }
    }
}