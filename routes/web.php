<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/register', [AuthController::class, 'showRegisterForm']);
Route::post('/register', [AuthController::class, 'register']);

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::get('/2fa', [AuthController::class, 'show2faForm']);
Route::post('/2fa', [AuthController::class, 'verify2fa']);

Route::get('/dashboard', [AuthController::class, 'dashboard'])->middleware('auth');
Route::get('/logout', [AuthController::class, 'logout']);

Route::post('/enable-2fa', [AuthController::class, 'enable2fa'])->middleware('auth');