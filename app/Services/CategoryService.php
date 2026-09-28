<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Str;

class CategoryService
{
    public function create(array $attributes): Category
    {
        $attributes['slug'] = $this->uniqueSlug($attributes['name']);

        return Category::create($attributes);
    }

    public function update(Category $category, array $attributes): Category
    {
        if ($attributes['name'] !== $category->name) {
            $attributes['slug'] = $this->uniqueSlug($attributes['name'], $category);
        }

        $category->update($attributes);

        return $category->refresh();
    }

    public function changeStatus(Category $category, string $status): Category
    {
        $category->update(['status' => $status]);

        return $category->refresh();
    }

    private function uniqueSlug(string $name, ?Category $ignore = null): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $suffix = 2;

        while (Category::where('slug', $slug)->when($ignore, fn ($query) => $query->whereKey('!=', $ignore->getKey()))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}