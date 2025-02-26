<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Controllers\ProdiController;

use App\Http\Controllers\NeosiaController;
use App\Http\Controllers\SikolaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\FakultasController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UniversitasController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

Route::get('/', [AuthenticatedSessionController::class, 'create']);


Route::get('/course-details', [NeosiaController::class, 'getCourseDetails'])->name('getCourseDetails');
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/statistik', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/presensi', [DashboardController::class, 'presensi'])->name('presensi');
    Route::get('/nilai', [DashboardController::class, 'nilai'])->name('nilai');
    Route::get('/log-mahasiswa', [DashboardController::class, 'log_mahasiswa'])->name('log-mahasiswa');
    Route::get('/redirect-out', [SikolaController::class, 'redirect'])->name('redirect');
    Route::get('/getProdi', [NeosiaController::class, 'getProdi'])->name('getProdi');
    Route::get('/getMatkul', [NeosiaController::class, 'getMatkul'])->name('getMatkul');
    Route::get('/getCourses', [NeosiaController::class, 'getCourses'])->name('getCourses');

});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:universitas'])->group(function () {
    Route::get('/universitas', [UniversitasController::class, 'index'])->name('universitas.dashboard');
});

Route::middleware(['auth', 'role:fakultas'])->group(function () {
    Route::get('/fakultas', [FakultasController::class, 'index'])->name('fakultas.dashboard');
});

Route::middleware(['auth', 'role:prodi'])->group(function () {
    Route::get('/prodi', [ProdiController::class, 'index'])->name('prodi.dashboard');
});

require __DIR__.'/auth.php';
require __DIR__.'/api.php';
