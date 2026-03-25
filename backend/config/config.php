<?php

return array (
  'app' => 
  array (
    'version' => '1.0.0',
    'url' => 'http://localhost',
    'installed' => true,
  ),
  'cors' => 
  array (
    'allowed_origins' => '',
  ),
  'database' => 
  array (
    'driver' => 'mysql',
    'host' => 'db',
    'name' => 'inf1005_local',
    'username' => 'sitizen',
    'password' => 'changeme',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
  ),
  'jwt' => 
  array (
    'keys' => 
    array (
      'v1' => 'changeme_jwt_secret_v1_replace_me',
      'v2' => 'changeme_jwt_secret_v2_replace_me',
    ),
    'current_kid' => 'v1',
    'expire' => 3600,
    'access_ttl' => 900,
    'refresh_ttl' => 604800,
  ),
  'mail' => 
  array (
    'host' => 'smtp.mailtrap.io',
    'port' => 587,
    'username' => '',
    'password' => '',
    'encryption' => 'tls',
    'from_email' => 'noreply@example.com',
    'from_name' => 'SITizen',
    'verify_ttl' => 86400,
  ),
);
