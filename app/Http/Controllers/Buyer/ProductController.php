<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $query = $this->query();
        $search = trim((string) $request->query('search', ''));
        $category = trim((string) $request->query('category', ''));

        $query
            ->when($search !== '', fn ($query) => $query->whereRaw('lower(name) like lower(?)', ["%{$search}%"]))
            ->when($category !== '', fn ($query) => $query->whereHas('category', fn ($query) => $query->where('slug', $category)));

        $products = $query->latest()->paginate(12)->withQueryString();
        $products->getCollection()->transform(fn (Product $product) => $this->serialize($product));
        return Inertia::render('Buyer/Products/Index', [
            'products' => $products,
            'categories' => Category::query()->where('status', 'active')->get(['id', 'name', 'slug']),
            'filters' => ['search' => $search, 'category' => $category],
        ]);
    }

    public function show(Product $product): Response
    {
        abort_unless($this->query()->whereKey($product->id)->exists(), 404);
        $product->load(['vendor.store', 'category', 'images']);
        return Inertia::render('Buyer/Products/Show', ['product' => $this->serialize($product)]);
    }

    private function query()
    {
        return Product::query()->where('status', 'active')
            ->whereHas('category', fn ($query) => $query->where('status', 'active'))
            ->whereHas('vendor', fn ($query) => $query->where('status', 'active'))
            ->with(['vendor.store', 'category', 'images']);
    }

    private function serialize(Product $product): array
    {
        return [
            'id' => $product->id, 'name' => $product->name, 'slug' => $product->slug,
            'description' => $product->description, 'price' => $product->price, 'stock' => $product->stock,
            'category' => $product->category?->only(['id', 'name', 'slug']),
            'vendor' => $product->vendor ? [
                ...$product->vendor->only(['id', 'status']),
                'store' => $product->vendor->store ? [
                    ...$product->vendor->store->only(['id', 'name', 'slug']),
                    'logo' => $product->vendor->store->logo ? Storage::disk('product_images')->url($product->vendor->store->logo) : null,
                ] : null,
            ] : null,
            'images' => $product->images->map(fn ($image) => ['id' => $image->id, 'url' => Storage::disk('product_images')->url($image->path), 'is_primary' => $image->is_primary])->values()->all(),
        ];
    }
}