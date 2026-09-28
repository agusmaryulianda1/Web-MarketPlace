<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Buyer\AddressController as BuyerAddressController;
use App\Http\Controllers\Buyer\CartController as BuyerCartController;
use App\Http\Controllers\Buyer\DashboardController as BuyerDashboardController;
use App\Http\Controllers\Buyer\OrderController as BuyerOrderController;
use App\Http\Controllers\Buyer\ProductController as BuyerProductController;
use App\Http\Controllers\Buyer\ProfileController as BuyerProfileController;
use App\Http\Controllers\OrderReceiptController;
use App\Http\Controllers\PublicCatalogController;
use App\Http\Controllers\Vendor\DashboardController as VendorDashboardController;
use App\Http\Controllers\Vendor\OrderController;
use App\Http\Controllers\Vendor\OrderNoteController;
use App\Http\Controllers\Vendor\ProductController;
use App\Http\Controllers\Vendor\SettingsController;
use App\Http\Controllers\Vendor\StoreController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [PublicCatalogController::class, 'home'])->name('home');
Route::get('/products', [PublicCatalogController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [PublicCatalogController::class, 'show'])->name('products.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
});

Route::post('/logout', LogoutController::class)
    ->middleware('auth')
    ->name('logout');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::patch('/categories/{category}/status', [CategoryController::class, 'changeStatus'])->name('categories.status');
    Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
    Route::patch('/products/{product}/status', [AdminProductController::class, 'changeStatus'])->name('products.status');
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}/receipt', [OrderReceiptController::class, 'show'])->name('orders.receipt');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/payment-status', [AdminOrderController::class, 'updatePaymentStatus'])->name('orders.payment-status');
});

Route::prefix('vendor')->name('vendor.')->middleware(['auth', 'role:vendor'])->group(function () {
    Route::get('/dashboard', VendorDashboardController::class)->name('dashboard');
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::get('/products/export', [ProductController::class, 'export'])->name('products.export');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::patch('/products/{product}/status', [ProductController::class, 'changeStatus'])->name('products.status');
    Route::patch('/products/{product}/stock', [ProductController::class, 'updateStock'])->name('products.stock');
    Route::get('/store', [StoreController::class, 'show'])->name('store.show');
    Route::post('/store', [StoreController::class, 'store'])->name('store.store');
    Route::put('/store', [StoreController::class, 'update'])->name('store.update');
    Route::patch('/store/status', [StoreController::class, 'updateStatus'])->name('store.status');
    Route::get('/settings', [SettingsController::class, 'show'])->name('settings.show');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}/note', OrderNoteController::class)->name('orders.note');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/groups/{group}/status', [OrderController::class, 'updateGroupStatus'])->name('orders.groups.status');
});

Route::prefix('buyer')->name('buyer.')->middleware(['auth', 'role:buyer'])->group(function () {
    Route::get('/dashboard', BuyerDashboardController::class)->name('dashboard');
    Route::get('/profile', [BuyerProfileController::class, 'show'])->name('profile.show');
    Route::match(['put', 'patch'], '/profile', [BuyerProfileController::class, 'update'])->name('profile.update');
    Route::get('/products', [BuyerProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product:slug}', [BuyerProductController::class, 'show'])->name('products.show');
    Route::get('/cart', [BuyerCartController::class, 'index'])->name('cart.index');
    Route::post('/cart/items', [BuyerCartController::class, 'store'])->name('cart.items.store');
    Route::match(['put', 'patch'], '/cart/items/{cartItem}', [BuyerCartController::class, 'update'])->name('cart.items.update');
    Route::delete('/cart/items/{cartItem}', [BuyerCartController::class, 'destroy'])->name('cart.items.destroy');
    Route::get('/checkout', [BuyerOrderController::class, 'checkout'])->name('checkout');
    Route::post('/checkout', [BuyerOrderController::class, 'store'])->name('checkout.store');
    Route::get('/addresses', [BuyerAddressController::class, 'index'])->name('addresses.index');
    Route::post('/addresses', [BuyerAddressController::class, 'store'])->name('addresses.store');
    Route::match(['put', 'patch'], '/addresses/{address}', [BuyerAddressController::class, 'update'])->name('addresses.update');
    Route::patch('/addresses/{address}/default', [BuyerAddressController::class, 'default'])->name('addresses.default');
    Route::get('/orders', [BuyerOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}/receipt', [OrderReceiptController::class, 'show'])->name('orders.receipt');
    Route::get('/orders/{order}', [BuyerOrderController::class, 'show'])->name('orders.show');
});
