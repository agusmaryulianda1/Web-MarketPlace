<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\ChangeCategoryStatusRequest;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categoryService) {}

    public function index(): Response
    {
        Gate::authorize('viewAny', Category::class);

        return Inertia::render('Admin/Categories/Index', [
            'categories' => Category::query()->latest()->paginate(15),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Category::class);

        return Inertia::render('Admin/Categories/Create');
    }

    public function store(StoreCategoryRequest $request)
    {
        $this->categoryService->create($request->validated());

        return redirect()->route('admin.categories.index')->with('success', 'Category created.');
    }

    public function edit(Category $category): Response
    {
        Gate::authorize('view', $category);

        return Inertia::render('Admin/Categories/Edit', ['category' => $category]);
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        Gate::authorize('update', $category);
        $this->categoryService->update($category, $request->validated());

        return redirect()->route('admin.categories.index')->with('success', 'Category updated.');
    }

    public function changeStatus(ChangeCategoryStatusRequest $request, Category $category)
    {
        Gate::authorize('changeStatus', $category);
        $this->categoryService->changeStatus($category, $request->validated('status'));

        return redirect()->back()->with('success', 'Category status updated.');
    }
}