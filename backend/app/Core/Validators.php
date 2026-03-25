<?php

namespace App\Core;

class Validators
{
    /**
     * Validate and sanitize module code (e.g., "INF1005")
     */
    public static function moduleCode(?string $code): ?string
    {
        if ($code === null || $code === '') {
            return null;
        }
        
        // Must be alphanumeric, 2-10 characters
        if (!preg_match('/^[A-Za-z0-9]{2,10}$/', $code)) {
            throw new \InvalidArgumentException('Invalid module code format');
        }
        
        return strtoupper($code);
    }

    /**
     * Validate email format
     */
    public static function email(?string $email): ?string
    {
        if ($email === null || $email === '') {
            return null;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }
        
        return strtolower(trim($email));
    }

    /**
     * Validate bounty amount (must be positive number)
     */
    public static function bountyAmount($amount): ?float
    {
        if ($amount === null || $amount === '') {
            return null;
        }
        
        $amount = (float) $amount;
        
        if ($amount < 0) {
            throw new \InvalidArgumentException('Bounty amount cannot be negative');
        }
        
        if ($amount > 10000) {
            throw new \InvalidArgumentException('Bounty amount exceeds maximum limit');
        }
        
        return $amount;
    }

    /**
     * Validate rate (tutor hourly rate)
     */
    public static function rate($rate): ?float
    {
        if ($rate === null || $rate === '') {
            return null;
        }
        
        $rate = (float) $rate;
        
        if ($rate < 0) {
            throw new \InvalidArgumentException('Rate cannot be negative');
        }
        
        if ($rate > 1000) {
            throw new \InvalidArgumentException('Rate exceeds maximum limit');
        }
        
        return $rate;
    }

    /**
     * Validate rating (1-5)
     */
    public static function rating($rating): int
    {
        $rating = (int) $rating;
        
        if ($rating < 1 || $rating > 5) {
            throw new \InvalidArgumentException('Rating must be between 1 and 5');
        }
        
        return $rating;
    }

    /**
     * Validate title (non-empty, max 200 chars)
     */
    public static function title(?string $title, int $maxLength = 200): string
    {
        $title = trim($title ?? '');
        
        if ($title === '') {
            throw new \InvalidArgumentException('Title is required');
        }
        
        if (strlen($title) > $maxLength) {
            throw new \InvalidArgumentException("Title cannot exceed {$maxLength} characters");
        }
        
        return $title;
    }

    /**
     * Validate content/text (non-empty, max 10000 chars)
     */
    public static function text(?string $text, int $maxLength = 10000): string
    {
        $text = trim($text ?? '');
        
        if ($text === '') {
            throw new \InvalidArgumentException('Content is required');
        }
        
        if (strlen($text) > $maxLength) {
            throw new \InvalidArgumentException("Content cannot exceed {$maxLength} characters");
        }
        
        return $text;
    }

    /**
     * Sanitize search input
     */
    public static function search(?string $search): ?string
    {
        if ($search === null || $search === '') {
            return null;
        }
        
        // Remove potentially dangerous characters but allow letters, numbers, spaces
        $search = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $search);
        
        // Limit length
        if (strlen($search) > 100) {
            $search = substr($search, 0, 100);
        }
        
        return trim($search);
    }
}