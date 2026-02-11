<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('preferred-bonus', function () {
    return 'Ullu bnaya';
});
