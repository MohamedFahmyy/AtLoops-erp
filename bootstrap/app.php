<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$app = new App\Core\Application(dirname(__DIR__));

// Load defined routes
require_once __DIR__ . '/../routes/web.php';

return $app;
