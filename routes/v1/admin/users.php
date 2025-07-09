<?php

use Illuminate\Support\Facades\Route;

use Modules\V1\User\Controllers\UserController;


Route::get('', [UserController::class, 'index'])->name('index');
Route::get('/{user}', [UserController::class, 'show'])->name('show');


