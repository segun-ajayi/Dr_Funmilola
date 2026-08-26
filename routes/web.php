<?php

use Illuminate\Support\Facades\Route;

Route::view('/reset-password', 'app')->name('password.reset');
Route::view('/{path?}', 'app')->where('path', '^(?!api).*$');
