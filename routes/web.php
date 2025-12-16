<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return [
        'message' => 'Pre-Order System API',
        'version' => '1.0.0',
        'status' => 'running'
    ];
});