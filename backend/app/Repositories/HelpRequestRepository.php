<?php

namespace App\Repositories;

use App\Models\HelpRequest;

class HelpRequestRepository
{
    public static function all(?string $search = null): array
    {
        $q = HelpRequest::with('author:id,email');
        if ($search) {
            $q->where(function ($q) use ($search) {
                $q->where('title', 'like', "%$search%")
                  ->orWhere('module_code', 'like', "%$search%");
            });
        }
        return $q->latest()->get()->toArray();
    }

    public static function create(array $data): HelpRequest
    {
        return HelpRequest::create($data);
    }
}