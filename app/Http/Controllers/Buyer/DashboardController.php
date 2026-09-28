<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $category = trim((string) $request->query('category', ''));
        $officialCategorySlugs = [
            'electronic',
            'vendor',
            'rumah-tangga',
            'fashion-busana',
            'gadget-aksesoris',
            'kecantikan',
        ];
        $categories = Category::query()->where('status', 'active')->whereIn('slug', $officialCategorySlugs)->orderBy('name')->get(['id', 'name', 'slug']);
        // ProductService owns mutations; catalog visibility matches Buyer ProductController.
        $products = Product::query()->where('status', 'active')
            ->whereHas('category', fn ($query) => $query->where('status', 'active'))
            ->whereHas('vendor', fn ($query) => $query->where('status', 'active'))
            ->when($category !== '', fn ($query) => $query->whereHas('category', fn ($query) => $query->where('slug', $category)))
            ->with(['category:id,name,slug', 'vendor:id,user_id', 'vendor.user:id,name', 'vendor.store:id,vendor_id,name', 'images:id,product_id,path,is_primary'])
            ->latest()->limit(8)->get()->map(fn (Product $product) => [
                'id' => $product->id, 'name' => $product->name, 'slug' => $product->slug, 'price' => $product->price,
                'category' => $product->category?->only(['id', 'name', 'slug']),
                'vendor' => $product->vendor ? ['id' => $product->vendor->id, 'name' => $product->vendor->user?->name, 'store_name' => $product->vendor->store?->name] : null,
                'images' => $product->images->map(fn ($image) => ['id' => $image->id, 'url' => Storage::disk('product_images')->url($image->path), 'is_primary' => $image->is_primary])->values()->all(),
            ])->values()->all();
        return Inertia::render('Buyer/Dashboard', ['categories' => $categories, 'products' => $products, 'filters' => ['category' => $category ?: null]]);
    }
}