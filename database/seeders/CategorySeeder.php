<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Electronic', 'slug' => 'electronic'],
            ['name' => 'Vendor', 'slug' => 'vendor'],
            ['name' => 'Rumah Tangga', 'slug' => 'rumah-tangga'],
            ['name' => 'Fashion & Busana', 'slug' => 'fashion-busana'],
            ['name' => 'Gadget & Aksesoris', 'slug' => 'gadget-aksesoris'],
            ['name' => 'Kecantikan', 'slug' => 'kecantikan'],
        ] as $category) {
            Category::firstOrCreate(
                ['slug' => $category['slug']],
                [...$category, 'status' => 'active'],
            );
        }
    }
}