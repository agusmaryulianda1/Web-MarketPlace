<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderVendorGroup;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DevelopmentOrderSeeder extends Seeder
{
    private const PREFIX = 'ORD-DEV-202609-';

    public function run(): void
    {
        DB::transaction(function (): void {
            [$vendorA, $vendorB] = $this->vendors();
            $buyer = $this->buyer();
            $category = $this->category();
            $productsA = $this->products($vendorA, $category, 'A');
            $productsB = $this->products($vendorB, $category, 'B');

            $statuses = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];
            $payments = ['pending', 'paid', 'failed'];
            $methods = ['bank_transfer', 'cod'];

            for ($number = 1; $number <= 18; $number++) {
                $isMultiVendor = $number > 12;
                $vendorProducts = $number % 2 === 0
                    ? [$vendorA, $productsA]
                    : [$vendorB, $productsB];

                if ($isMultiVendor) {
                    $lines = [
                        [$vendorA, $productsA[$number % 3], ($number % 2) + 1],
                        [$vendorB, $productsB[($number + 1) % 3], 1 + ($number % 3)],
                    ];
                } else {
                    $lines = [[$vendorProducts[0], $vendorProducts[1][$number % 3], 1 + ($number % 3)]];
                }

                $total = collect($lines)->sum(fn (array $line): float => (float) $line[1]->price * $line[2]);
                $order = Order::firstOrCreate(
                    ['order_number' => self::PREFIX.str_pad((string) $number, 4, '0', STR_PAD_LEFT)],
                    [
                        'user_id' => $buyer->id,
                        'address_id' => null,
                        'total_amount' => number_format($total, 2, '.', ''),
                        'payment_method' => $methods[$number % 2],
                        'payment_status' => $payments[$number % 3],
                        'order_status' => $statuses[$number % 5],
                        'shipping_address' => 'Jl. Development No. '.$number.', Jakarta Selatan',
                        'created_at' => now()->subDays(21 - $number)->subHours($number),
                        'updated_at' => now()->subDays(21 - $number)->subHours($number),
                    ],
                );

                foreach (collect($lines)->groupBy(fn (array $line): int => $line[0]->id) as $vendorId => $vendorLines) {
                    $group = OrderVendorGroup::firstOrCreate(
                        ['order_id' => $order->id, 'vendor_id' => $vendorId],
                        ['status' => $order->order_status, 'subtotal' => '0.00'],
                    );
                    $group->update(['subtotal' => number_format($vendorLines->sum(fn (array $line): float => (float) $line[1]->price * $line[2]), 2, '.', '')]);
                    foreach ($vendorLines as [$vendor, $product, $quantity]) {
                        OrderItem::firstOrCreate(
                        ['order_id' => $order->id, 'product_id' => $product->id, 'vendor_id' => $vendor->id],
                        [
                            'order_vendor_group_id' => $group->id,
                            'product_name' => $product->name,
                            'price' => $product->price,
                            'quantity' => $quantity,
                            'subtotal' => number_format((float) $product->price * $quantity, 2, '.', ''),
                        ],
                        );
                    }
                }
            }
        });
    }

    private function vendors(): array
    {
        $vendors = Vendor::query()->where('status', 'active')->with('user')->get()->all();
        while (count($vendors) < 2) {
            $suffix = count($vendors) + 1;
            $email = "vendor-orders-{$suffix}@development.test";
            $user = User::firstOrCreate(
                ['email' => $email],
                ['name' => "Development Orders Vendor {$suffix}", 'role' => 'vendor', 'password' => Hash::make('password')],
            );
            $vendors[] = Vendor::firstOrCreate(['user_id' => $user->id], ['status' => 'active']);
        }

        return [$vendors[0], $vendors[1]];
    }

    private function buyer(): User
    {
        return User::firstOrCreate(
            ['email' => 'buyer@example.test'],
            ['name' => 'Development Buyer', 'role' => 'buyer', 'password' => Hash::make('password')],
        );
    }

    private function category(): Category
    {
        return Category::firstOrCreate(
            ['slug' => 'vendor-orders-development'],
            ['name' => 'Vendor Orders Development', 'description' => 'Development fixture category.', 'status' => 'active'],
        );
    }

    private function products(Vendor $vendor, Category $category, string $label): array
    {
        return collect([
            ['Wireless Headphones', 899000, 48],
            ['Mechanical Keyboard', 1250000, 35],
            ['USB-C Hub', 349000, 72],
        ])->map(function (array $data, int $index) use ($vendor, $category, $label): Product {
            [$name, $price, $stock] = $data;
            return $vendor->products()->firstOrCreate(
                ['slug' => Str::slug("vendor-orders-{$label}-{$name}")],
                [
                    'vendor_id' => $vendor->id,
                    'category_id' => $category->id,
                    'name' => "{$name} {$label}",
                    'description' => 'Development fixture product for Vendor Orders.',
                    'price' => $price,
                    'stock' => $stock,
                    'status' => 'active',
                ],
            );
        })->all();
    }
}

// ponytail: fixture scope is intentionally limited to vendor-order visual verification.
// Upgrade to factories only if broader reusable development datasets are required.
