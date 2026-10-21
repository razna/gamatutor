<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Customer;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

// ---- Portal publik ------------------------------------------------------------------------
Route::controller(PublicController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('/produk', 'products')->name('products');
    Route::get('/produk/{product:code}', 'product')->name('products.show');
    Route::get('/simulasi', 'simulation')->name('simulation');
    Route::match(['get', 'post'], '/cek-kelayakan', 'eligibility')->name('eligibility');
    Route::get('/faq', 'faq')->name('faq');
    Route::get('/kontak', 'contact')->name('contact');
    Route::get('/syarat-ketentuan', 'terms')->name('terms');
});

// ---- Autentikasi --------------------------------------------------------------------------
Route::middleware('guest')->controller(AuthController::class)->group(function () {
    Route::get('/masuk', 'showLogin')->name('login');
    Route::post('/masuk', 'login')->middleware('throttle:login');
    Route::get('/daftar', 'showRegister')->name('register');
    Route::post('/daftar', 'register')->middleware('throttle:10,1');
    Route::get('/lupa-sandi', 'showForgot')->name('password.request');
    Route::post('/lupa-sandi', 'sendResetLink')->middleware('throttle:5,1')->name('password.email');
    Route::get('/atur-ulang-sandi/{token}', 'showReset')->name('password.reset');
    Route::post('/atur-ulang-sandi', 'resetPassword')->name('password.update');
});

Route::middleware('auth')->controller(AuthController::class)->group(function () {
    Route::post('/keluar', 'logout')->name('logout');
    Route::get('/verifikasi-otp', 'showTwoFactor')->name('two-factor.show');
    Route::post('/verifikasi-otp', 'verifyTwoFactor')->middleware('throttle:10,1')->name('two-factor.verify');
});

// ---- Area terautentikasi (bersama) --------------------------------------------------------
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifikasi/{id}', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'readAll'])->name('notifications.read-all');

    Route::get('/dokumen/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::get('/survei/{application}/foto/{index}', [DocumentController::class, 'surveyPhoto'])->name('survey.photo');

    Route::prefix('cetak')->name('print.')->controller(PrintController::class)->group(function () {
        Route::get('/sp3k/{application}', 'sp3k')->name('sp3k');
        Route::get('/perjanjian/{application}', 'contract')->name('contract');
        Route::get('/jadwal/{application}', 'schedule')->name('schedule');
        Route::get('/kuitansi/{payment}', 'receipt')->name('receipt');
        Route::get('/memo/{application}', 'memo')->name('memo');
    });
});

// ---- Nasabah ------------------------------------------------------------------------------
Route::middleware(['auth', 'active', 'role:nasabah'])->prefix('nasabah')->name('customer.')->group(function () {
    Route::get('/', Customer\DashboardController::class)->name('dashboard');
    Route::get('/profil', [Customer\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [Customer\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/sandi', [Customer\ProfileController::class, 'password'])->name('profile.password');

    Route::controller(Customer\ApplicationController::class)->prefix('pengajuan')->name('applications.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/baru', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{application}', 'show')->name('show');
        Route::get('/{application}/ubah', 'edit')->name('edit');
        Route::put('/{application}', 'update')->name('update');
        Route::post('/{application}/kirim', 'submit')->name('submit');
        Route::post('/{application}/batal', 'cancel')->name('cancel');
        Route::post('/{application}/dokumen', 'uploadDocument')->middleware('throttle:uploads')->name('documents.store');
        Route::delete('/{application}/dokumen/{document}', 'deleteDocument')->name('documents.destroy');
    });

    Route::get('/pinjaman', [Customer\LoanController::class, 'index'])->name('loans.index');
    Route::get('/pinjaman/{loan}', [Customer\LoanController::class, 'show'])->name('loans.show');

    Route::controller(Customer\TicketController::class)->prefix('pengaduan')->name('tickets.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/baru', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{ticket}', 'show')->name('show');
        Route::post('/{ticket}/balas', 'reply')->name('reply');
    });
});

// ---- Petugas bank -------------------------------------------------------------------------
Route::middleware(['auth', 'active', 'staff'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::get('/pengajuan', [Admin\ApplicationController::class, 'index'])->name('applications.index');
    Route::get('/pengajuan/{application}', [Admin\ApplicationController::class, 'show'])->name('applications.show');

    Route::controller(Admin\ProcessController::class)->prefix('pengajuan/{application}')->name('process.')->group(function () {
        Route::middleware('role:cs')->group(function () {
            Route::post('/dokumen/{document}/verifikasi', 'verifyDocument')->name('document');
            Route::post('/verifikasi', 'verify')->name('verify');
            Route::post('/tolak', 'reject')->name('reject');
        });
        Route::post('/revisi', 'revision')->middleware('role:cs,analis')->name('revision');
        Route::middleware('role:analis')->group(function () {
            Route::post('/survei', 'survey')->middleware('throttle:uploads')->name('survey');
            Route::post('/agunan/{collateral}/appraisal', 'appraise')->name('appraise');
            Route::post('/analisis', 'analyze')->name('analyze');
            Route::post('/ajukan-persetujuan', 'submitApproval')->name('submit-approval');
        });
        Route::post('/keputusan', 'decide')->middleware('role:kepala_cabang,komite')->name('decide');
        Route::middleware('role:legal')->group(function () {
            Route::post('/legal', 'legal')->name('legal');
            Route::post('/akad-selesai', 'completeContract')->name('contract');
        });
        Route::post('/pencairan', 'disburse')->middleware('role:teller')->name('disburse');
    });

    Route::get('/pinjaman', [Admin\LoanController::class, 'index'])->name('loans.index');
    Route::get('/collection', [Admin\LoanController::class, 'collection'])->middleware('role:collection,kepala_cabang,komite')->name('collection');
    Route::get('/pinjaman/{loan}', [Admin\LoanController::class, 'show'])->name('loans.show');
    Route::post('/pinjaman/{loan}/bayar', [Admin\LoanController::class, 'pay'])->middleware('role:teller')->name('loans.pay');
    Route::post('/pinjaman/{loan}/pelunasan', [Admin\LoanController::class, 'payoff'])->middleware('role:teller')->name('loans.payoff');
    Route::post('/pinjaman/{loan}/restrukturisasi', [Admin\LoanController::class, 'restructure'])->middleware('role:kepala_cabang,komite')->name('loans.restructure');
    Route::post('/pinjaman/{loan}/catatan', [Admin\LoanController::class, 'note'])->middleware('role:collection')->name('loans.note');

    Route::get('/laporan', [Admin\ReportController::class, 'index'])->middleware('role:kepala_cabang,komite,collection')->name('reports');

    Route::middleware('role:cs')->group(function () {
        Route::get('/pengaduan', [Admin\TicketController::class, 'index'])->name('tickets.index');
        Route::get('/pengaduan/{ticket}', [Admin\TicketController::class, 'show'])->name('tickets.show');
        Route::post('/pengaduan/{ticket}/balas', [Admin\TicketController::class, 'reply'])->name('tickets.reply');
    });

    // Master data & administrasi sistem — khusus Super Admin
    Route::middleware('role:admin')->group(function () {
        Route::resource('produk', Admin\ProductController::class)->except('show')->parameters(['produk' => 'product'])->names('products');
        Route::get('/cabang', [Admin\BranchController::class, 'index'])->name('branches.index');
        Route::post('/cabang', [Admin\BranchController::class, 'store'])->name('branches.store');
        Route::put('/cabang/{branch}', [Admin\BranchController::class, 'update'])->name('branches.update');
        Route::get('/jenis-dokumen', [Admin\DocumentTypeController::class, 'index'])->name('document-types.index');
        Route::post('/jenis-dokumen', [Admin\DocumentTypeController::class, 'store'])->name('document-types.store');
        Route::put('/jenis-dokumen/{documentType}', [Admin\DocumentTypeController::class, 'update'])->name('document-types.update');
        Route::get('/hari-libur', [Admin\HolidayController::class, 'index'])->name('holidays.index');
        Route::post('/hari-libur', [Admin\HolidayController::class, 'store'])->name('holidays.store');
        Route::delete('/hari-libur/{holiday}', [Admin\HolidayController::class, 'destroy'])->name('holidays.destroy');
        Route::resource('pengguna', Admin\UserController::class)->except('show', 'destroy')->parameters(['pengguna' => 'user'])->names('users');
        Route::get('/pengaturan', [Admin\SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/pengaturan', [Admin\SettingController::class, 'update'])->name('settings.update');
        Route::put('/pengaturan/template', [Admin\SettingController::class, 'templates'])->name('settings.templates');
        Route::get('/audit-log', [Admin\AuditLogController::class, 'index'])->name('audit');
    });
});
