<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\MastermindController;
use App\Http\Controllers\ProfileController;


/* AUTHENTIKASI & REGISTER */
Route::middleware('guest')->group(function () {
    Route::get('login', [DashboardController::class, 'login'])->name('login');
    Route::post('login', [DashboardController::class, 'doLogin'])->name('doLogin');

    Route::get('register', [DashboardController::class, 'register'])->name('register');
    Route::post('register', [DashboardController::class, 'doRegister'])->name('doRegister');
});
Route::post('logout', [DashboardController::class, 'logout'])->name('logout')->middleware('auth');

/* ROUTE UNTUK SEMUA USER YANG TERAUTENTIKASI */
Route::middleware('auth')->group(function () {

    /* DASHBOARD */
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    /* PROFILE PENGGUNA (EDIT PROFIL PRIBADI) */
    Route::get('profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('profile/update', [ProfileController::class, 'update'])->name('profile.update');

     /* ABSENSI */
    Route::prefix('absensi')->group(function () {
        Route::get('/', [AbsensiController::class, 'index'])->name('absensi.index');
        Route::get('/create', [AbsensiController::class, 'create'])->name('absensi.create')->middleware('role:masteradmin,penerobos');
        Route::post('/', [AbsensiController::class, 'store'])->name('absensi.store')->middleware('role:masteradmin,penerobos');
        Route::get('/{user}/edit', [AbsensiController::class, 'edit'])->name('absensi.edit')->middleware('role:masteradmin,penerobos');
        Route::put('/{user}', [AbsensiController::class, 'update'])->name('absensi.update')->middleware('role:masteradmin,penerobos');
        Route::delete('/delete-daily/{user}', [AbsensiController::class, 'deleteDailyAbsensi'])->name('absensi.delete_daily')->middleware('role:masteradmin,penerobos');
        Route::delete('/{absensi}', [AbsensiController::class, 'destroy'])->name('absensi.destroy')->middleware('role:masteradmin,penerobos');
    });

    /* DATA SANTRI/PENEROBOS/DEWAN GURU (MANAGEMENT USER) */
    Route::middleware('role:masteradmin,penerobos,dewanguru')->group(function () {
        Route::get('santri', [ProfileController::class, 'index'])->name('santri'); // Menampilkan daftar user
        Route::get('user/{user}/edit', [ProfileController::class, 'editUser'])->name('profile.editUser');
        Route::post('user/{user}/update', [ProfileController::class, 'updateUser'])->name('profile.updateUser');
        Route::delete('santri/delete/{user}', [ProfileController::class, 'destroyUser'])->name('santri.delete');
    });


    /* MASTERMIND - ADMINISTRASI SUPERADMIN */
    Route::middleware('role:masteradmin')->group(function () {
        Route::get('mastermind/approve', [MastermindController::class, 'approveRole'])->name('mastermind.approve');
        Route::post('mastermind/approve/{id}', [MastermindController::class, 'approveRolePost'])->name('mastermind.approve.post');
        Route::put('/mastermind/izinabsen/batch', [MastermindController::class, 'updateIzinAbsenBatch'])->name('mastermind.izinabsen.batchUpdate');
        Route::get('mastermind/izinabsen', [MastermindController::class, 'izinAbsen'])->name('mastermind.izinabsen');
        Route::post('mastermind/izinabsen/batch', [MastermindController::class, 'izinAbsenPostBatch'])->name('mastermind.izinabsen.post.batch');
        Route::post('mastermind/izinabsen/{id}', [MastermindController::class, 'izinAbsenPost'])->name('mastermind.izinabsen.post');
    });
});
