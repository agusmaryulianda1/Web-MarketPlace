<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PublicCatalogController extends Controller
{
    private const PUBLIC_CATEGORY_SLUGS = [
        'electronic',
        'fashion-busana',
        'gadget-aksesoris',
        'kecantikan',
        'rumah-tangga',
        'vendor-orders-development',
    ];

    public function home(): Response
    {
        return Inertia::render('Welcome', [
            'categories' => $this->categories(),
            'products' => $this->query()->latest()->limit(8)->get()->map($this->serialize(...))->values()->all(),
        ]);
    }

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $category = trim((string) $request->query('category', ''));

        $products = $this->query()
            ->when($search !== '', fn (Builder $query) => $query->whereRaw('lower(name) like lower(?)', ["%{$search}%"]))
            ->when($category !== '', function (Builder $query) use ($category): void {
                if (! in_array($category, self::PUBLIC_CATEGORY_SLUGS, true)) {
                    $query->whereRaw('1 = 0');
                    return;
                }

                $query->whereHas('category', fn (Builder $query) => $query->where('slug', $category));
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $products->getCollection()->transform($this->serialize(...));

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => $this->categories(),
            'filters' => ['search' => $search, 'category' => $category],
        ]);
    }

    public function show(Product $product): Response
    {
        abort_unless($this->query()->whereKey($product->id)->exists(), 404);

        return Inertia::render('Products/Show', ['product' => $this->serialize($product->loadMissing(['category', 'vendor.store', 'images']))]);
    }

    private function query(): Builder
    {
        return Product::publiclyVisible()
            ->with([
                'category:id,name,slug',
                'vendor.store:id,vendor_id,name,slug',
                'images:id,product_id,path,is_primary',
            ]);
    }

    private function categories()
    {
        return Category::query()
            ->where('status', 'active')
            ->whereIn('slug', self::PUBLIC_CATEGORY_SLUGS)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }

    private function serialize(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => $product->description,
            'price' => $product->price,
            'category' => $product->category?->only(['name', 'slug']),
            'store' => $product->vendor?->store?->only(['name', 'slug']),
            'images' => $product->images->map(fn ($image) => [
                'id' => $image->id,
                'url' => Storage::disk('product_images')->url($image->path),
                'is_primary' => $image->is_primary,
            ])->values()->all(),
        ];
    }
}
