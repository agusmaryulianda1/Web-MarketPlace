<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')->orderBy('id')->eachById(function (object $order): void {
            $items = DB::table('order_items')
                ->where('order_id', $order->id)
                ->get();

            foreach ($items->groupBy('vendor_id') as $vendorId => $vendorItems) {
                $subtotal = $vendorItems->sum(fn (object $item): string => (string) $item->subtotal);
                $group = DB::table('order_vendor_groups')
                    ->where('order_id', $order->id)
                    ->where('vendor_id', $vendorId)
                    ->first();

                if ($group) {
                    DB::table('order_vendor_groups')->where('id', $group->id)->update([
                        'subtotal' => number_format((float) $subtotal, 2, '.', ''),
                        'updated_at' => now(),
                    ]);
                    $groupId = $group->id;
                } else {
                    $groupId = DB::table('order_vendor_groups')->insertGetId([
                        'order_id' => $order->id,
                        'vendor_id' => $vendorId,
                        'status' => $order->order_status,
                        'subtotal' => number_format((float) $subtotal, 2, '.', ''),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('order_items')
                    ->whereIn('id', $vendorItems->pluck('id'))
                    ->update(['order_vendor_group_id' => $groupId]);
            }
        });
    }

    public function down(): void
    {
        DB::table('order_items')->update(['order_vendor_group_id' => null]);
        DB::table('order_vendor_groups')->delete();
    }
};