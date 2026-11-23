<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoriController;
use App\Http\Controllers\KeuanganController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\PosController;

// Redirect root ke halaman login
Route::get('/', function () {
    return redirect()->route('login');
});

// Rute Tamu (Guest)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Rute Terautentikasi (Auth)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('role:Admin,Kasir,Gudang,Keuangan')
        ->name('dashboard');

    Route::middleware('role:Admin,Kasir')->group(function () {
        Route::get('/kasir', [PosController::class, 'index'])->name('kasir.index');
        Route::post('/kasir', [PosController::class, 'store'])->name('kasir.store');
        Route::get('/kasir/riwayat', [PosController::class, 'riwayat'])->name('kasir.riwayat');
    });

    Route::middleware('role:Admin,Gudang')->group(function () {
        Route::get('/inventori', [InventoriController::class, 'index'])->name('stok.index');
        Route::get('/inventori/{produk}/kartu-stok', [InventoriController::class, 'kartuStok'])->name('stok.kartu');
        Route::get('/pembelian', [PembelianController::class, 'index'])->name('pembelian.index');
        Route::post('/pembelian', [PembelianController::class, 'store'])->name('pembelian.store');
        Route::post('/pembelian/{pembelian}/setujui', [PembelianController::class, 'setujui'])
            ->middleware('role:Admin')
            ->name('pembelian.setujui');
        Route::post('/pembelian/{pembelian}/terima', [PembelianController::class, 'terima'])->name('pembelian.terima');
    });

    Route::middleware('role:Admin,Keuangan')->group(function () {
        Route::get('/keuangan', [KeuanganController::class, 'index'])->name('keuangan.index');
        Route::post('/keuangan/biaya', [KeuanganController::class, 'storeBiaya'])->name('keuangan.biaya.store');
        Route::post('/keuangan/utang/{pembelian}', [KeuanganController::class, 'bayarUtang'])->name('keuangan.utang.bayar');
        Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
        Route::get('/laporan/csv', [LaporanController::class, 'csv'])->name('laporan.csv');
    });
});