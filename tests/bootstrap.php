<?php

// Use SQLite as in-memory for testing.
$_ENV['DB_DATABASE'] = ':memory:';
$_SERVER['DB_DATABASE'] = ':memory:';
putenv('DB_DATABASE=:memory:');

require_once __DIR__ . '/../vendor/autoload.php';
