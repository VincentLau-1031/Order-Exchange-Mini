<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Http\Request;


Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
