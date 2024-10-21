<?php

require __DIR__ . '/vendor/autoload.php';

$data = json_decode(file_get_contents('php://input'), true);

use App\Controllers\CodeController;

$codeController = new CodeController();
$output = $codeController->compileAndReturn($data['code']);