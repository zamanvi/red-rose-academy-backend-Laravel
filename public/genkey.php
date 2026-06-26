<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\Crypt;
$value = 'ThisisanencryptedpublickeyforRedRoseWebApplication';
$encrypted = Crypt::encryptString($value);
// Remove trailing = signs for URL safety
echo json_encode(['key' => $encrypted, 'url_safe' => rtrim($encrypted, '=')]);
