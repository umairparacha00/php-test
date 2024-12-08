<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductsController;

Route::get('/', [ProductsController::class, 'index'])->name('products.index');
Route::prefix('products')->group(function () {
    Route::post('/store', [ProductsController::class, 'store'])->name('products.store');
    Route::post('/update', [ProductsController::class, 'update'])->name('products.update');
    Route::delete('/{index}', [ProductsController::class, 'destroy'])->name('products.destroy');
});
