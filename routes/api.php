<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SikolaController;
use App\Http\Controllers\NeosiaController;

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::get('/by-courseid', [SikolaController::class, 'byCourseId']);
// Route::get('/semester', [NeosiaController::class, 'semester'])->name('semesters');
