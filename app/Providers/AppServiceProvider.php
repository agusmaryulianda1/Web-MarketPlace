<?php

namespace App\Providers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Models\OrderVendorGroup;
use App\Policies\OrderPolicy;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(OrderVendorGroup::class, OrderPolicy::class);

        if (! app()->environment('local')) {
            return;
        }

        DB::listen(function (QueryExecuted $query): void {
            if (! app()->bound('request')) {
                return;
            }

            $request = request();
            $state = $request->attributes->get('perf_diagnostic');

            if (! is_array($state)) {
                return;
            }

            $durationMs = (float) $query->time;
            $state['query_count']++;
            $state['query_total_ms'] += $durationMs;
            $state['query_max_ms'] = max($state['query_max_ms'], $durationMs);
            $request->attributes->set('perf_diagnostic', $state);

            logger()->info('[PERF_DIAGNOSTIC] query', [
                'request_id' => $state['request_id'],
                'query_index' => $state['query_count'],
                'connection' => $query->connectionName,
                'duration_ms' => round($durationMs, 2),
                'statement' => strtolower(strtok(ltrim($query->sql), " \t\r\n")),
                'query_family' => $this->queryFamily($query->sql),
                'sql_hash' => hash('sha256', $query->sql),
            ]);
        });
    }

    private function queryFamily(string $sql): string
    {
        $sql = strtolower($sql);
        $tables = [];

        foreach ([
            'sessions',
            'users',
            'categories',
            'products',
            'vendors',
            'orders',
            'order_items',
            'product_images',
            'cache',
        ] as $table) {
            if (preg_match('/(?:from|join|update|into|delete\s+from)\s+["`]?'.$table.'["`]?\b/', $sql)) {
                $tables[] = $table;
            }
        }

        if (in_array('sessions', $tables, true)) {
            return 'session';
        }

        if (in_array('cache', $tables, true)) {
            return 'cache';
        }

        if (in_array('orders', $tables, true) || in_array('order_items', $tables, true)) {
            return count($tables) > 1 ? 'orders_relations' : 'orders';
        }

        if (in_array('products', $tables, true) || in_array('product_images', $tables, true)) {
            return count($tables) > 1 ? 'products_relations' : 'products';
        }

        if (in_array('vendors', $tables, true)) {
            return count($tables) > 1 ? 'vendors_relations' : 'vendors';
        }

        if (in_array('categories', $tables, true)) {
            return 'categories';
        }

        if (in_array('users', $tables, true)) {
            return 'auth_users';
        }

        return 'other';
    }
}
