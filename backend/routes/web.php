<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeployController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});
