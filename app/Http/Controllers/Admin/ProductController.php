<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\AdminChangeProductStatusRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Vendor;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $productService) {}

    public function index(): Response
    {
        Gate::authorize('viewAny', Product::class);

        $filters = request()->only(['search', 'vendor_id', 'category_id', 'status']);
        $products = Product::query()
            ->with(['vendor.user', 'category'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when($filters['vendor_id'] ?? null, fn ($query, $value) => $query->where('vendor_id', $value))
            ->when($filters['category_id'] ?? null, fn ($query, $value) => $query->where('category_id', $value))
            ->when($filters['status'] ?? null, fn ($query, $value) => $query->where('status', $value))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'filters' => $filters,
            'vendors' => Vendor::query()->with('user:id,name')->orderBy('id')->get(['id', 'user_id']),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function changeStatus(AdminChangeProductStatusRequest $request, Product $product): RedirectResponse
    {
        Gate::authorize('changeStatus', $product);
        $this->productService->changeStatus($request->user(), $product, $request->validated('status'));

        return redirect()->back()->with('success', 'Product status updated.');
    }
}