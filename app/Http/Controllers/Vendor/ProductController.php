<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ChangeProductStatusRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Requests\Product\UpdateProductStockRequest;
use App\Http\Requests\VendorProductIndexRequest;
use App\Exports\VendorProductsExport;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $productService) {}

    public function index(VendorProductIndexRequest $request): Response
    {
        Gate::authorize('viewAny', Product::class);

        $vendorProducts = $request->user()->vendor->products();
        $search = $request->effectiveSearch();
        $status = $request->validated('status');
        $stats = [
            'total' => (clone $vendorProducts)->count(),
            'active' => (clone $vendorProducts)->where('status', 'active')->count(),
            'critical' => (clone $vendorProducts)->where('stock', '>', 0)->where('stock', '<', 5)->count(),
            'out_of_stock' => (clone $vendorProducts)->where('stock', 0)->count(),
        ];
        $products = $vendorProducts->with(['category', 'images'])->when($search, fn ($query) => $query->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($search).'%']))
            ->when($status === 'active', fn ($query) => $query->where('status', 'active'))
            ->when($status === 'out_of_stock', fn ($query) => $query->where('stock', 0))
            ->latest()->paginate(5)->withQueryString();
        $products->getCollection()->transform(function (Product $product) {
            $image = $product->images->firstWhere('is_primary', true);
            $product->image_url = $image ? Storage::disk('product_images')->url($image->path) : null;
            return $product;
        });
        return Inertia::render('Vendor/Products/Index', compact('products', 'stats', 'search', 'status'));
    }

    public function create(): Response
    {
        Gate::authorize('create', Product::class);

        return Inertia::render('Vendor/Products/Create', [
            'categories' => Category::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        $this->productService->create($request->user(), $request->validated());

        return redirect()->route('vendor.products.index')->with('success', 'Product created.');
    }

    public function edit(Product $product): Response
    {
        Gate::authorize('view', $product);
        $product->load(['category', 'images']);
        $product->images->each(fn ($image) => $image->url = Storage::disk('product_images')->url($image->path));

        return Inertia::render('Vendor/Products/Edit', [
            'product' => $product,
            'categories' => Category::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        Gate::authorize('update', $product);
        $this->productService->update($request->user(), $product, $request->validated());

        return redirect()->route('vendor.products.index')->with('success', 'Product updated.');
    }

    public function changeStatus(ChangeProductStatusRequest $request, Product $product)
    {
        Gate::authorize('changeStatus', $product);
        $this->productService->changeStatus($request->user(), $product, $request->validated('status'));

        return redirect()->back()->with('success', 'Product status updated.');
    }

    public function updateStock(UpdateProductStockRequest $request, Product $product)
    {
        Gate::authorize('updateStock', $product);
        $this->productService->updateStock($request->user(), $product, $request->validated('stock'));

        return redirect()->back()->with('success', 'Product stock updated.');
    }

    public function destroy(Product $product)
    {
        Gate::authorize('delete', $product);
        $this->productService->delete(request()->user(), $product);
        return redirect()->route('vendor.products.index')->with('success', 'Produk berhasil dihapus.');
    }

    public function export(VendorProductIndexRequest $request)
    {
        return Excel::download(new VendorProductsExport($request->user()->vendor->id, $request->effectiveSearch(), $request->validated('status')), 'produk-vendor.xlsx');
    }
}