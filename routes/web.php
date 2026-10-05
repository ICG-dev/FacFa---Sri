<?php

use App\Livewire\DashboardView;
use App\Livewire\Empresas\EmpresaForm;
use App\Livewire\Empresas\EmpresaIndex;
use App\Livewire\Facturas\FacturaForm;
use App\Livewire\Facturas\FacturaIndex;
use App\Livewire\Facturas\FacturaShow;
use App\Livewire\Productos\ProductoForm;
use App\Livewire\Productos\ProductoIndex;
use App\Livewire\Usuarios\UsuarioForm;
use App\Livewire\Usuarios\UsuarioIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
})->name('home');

Route::get('dashboard', DashboardView::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth', 'verified'])->prefix('facturas')->name('facturas.')->group(function () {
    Route::get('/', FacturaIndex::class)->name('index');
    Route::get('/crear', FacturaForm::class)->name('create');
    Route::get('/{factura}/editar', FacturaForm::class)->name('edit');
    Route::get('/{factura}', FacturaShow::class)->name('show');
});

Route::middleware(['auth', 'verified'])->prefix('admin/empresas')->name('empresas.')->group(function () {
    Route::get('/', EmpresaIndex::class)->name('index');
    Route::get('/crear', EmpresaForm::class)->name('create');
    Route::get('/{empresa}/editar', EmpresaForm::class)->name('edit');
});

Route::middleware(['auth', 'verified'])->prefix('productos')->name('productos.')->group(function () {
    Route::get('/', ProductoIndex::class)->name('index');
    Route::get('/crear', ProductoForm::class)->name('create');
    Route::get('/{producto}/editar', ProductoForm::class)->name('edit');
});

Route::middleware(['auth', 'verified'])->prefix('usuarios')->name('usuarios.')->group(function () {
    Route::get('/', UsuarioIndex::class)->name('index');
    Route::get('/crear', UsuarioForm::class)->name('create');
    Route::get('/{usuario}/editar', UsuarioForm::class)->name('edit');
});

require __DIR__.'/auth.php';
