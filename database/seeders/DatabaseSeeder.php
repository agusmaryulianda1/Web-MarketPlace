<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CategorySeeder::class);

        User::updateOrCreate(
            ['email' => 'admin@example.test'],
            ['name' => 'Development Admin', 'role' => 'admin', 'password' => Hash::make('password')],
        );

        $vendorUser = User::updateOrCreate(
            ['email' => 'vendor@example.test'],
            ['name' => 'Development Vendor', 'role' => 'vendor', 'password' => Hash::make('password')],
        );

        $vendor = Vendor::updateOrCreate(
            ['user_id' => $vendorUser->id],
            ['status' => 'active'],
        );

        $buyer = User::updateOrCreate(
            ['email' => 'buyer@example.test'],
            ['name' => 'Development Buyer', 'role' => 'buyer', 'password' => Hash::make('password')],
        );

        $category = Category::updateOrCreate(
            ['slug' => 'electronics'],
            ['name' => 'Electronics', 'status' => 'active'],
        );

        $product = Product::updateOrCreate(
            ['slug' => 'development-product'],
            [
                'vendor_id' => $vendor->id,
                'category_id' => $category->id,
                'name' => 'Development Product',
                'description' => 'Seed data for local development.',
                'price' => 100000,
                'stock' => 10,
                'status' => 'active',
            ],
        );

        ProductImage::updateOrCreate(
            ['product_id' => $product->id, 'path' => 'products/development-product.jpg'],
            ['is_primary' => true],
        );

        Cart::firstOrCreate(['user_id' => $buyer->id]);
    }
}
