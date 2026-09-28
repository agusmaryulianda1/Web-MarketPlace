<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DatabasePerformanceProbe extends Command
{
    protected $signature = 'diagnostic:database-performance
        {--mode=warm : warm or cold}
        {--benchmark=all : benchmark name or all}
        {--runs=5 : warm-process repetitions}';

    protected $description = 'Run read-only database performance diagnostics';

    public function handle(): int
    {
        $mode = (string) $this->option('mode');
        $benchmark = (string) $this->option('benchmark');
        $benchmarks = $this->benchmarks();

        if (! in_array($mode, ['warm', 'cold'], true) || ($benchmark !== 'all' && ! isset($benchmarks[$benchmark]))) {
            $this->error('Invalid mode or benchmark.');

            return self::INVALID;
        }

        $selected = $benchmark === 'all' ? $benchmarks : [$benchmark => $benchmarks[$benchmark]];
        $runs = $mode === 'cold' ? 1 : max(1, min((int) $this->option('runs'), 20));
        $results = [];

        for ($run = 1; $run <= $runs; $run++) {
            foreach ($selected as $name => $operation) {
                $results[$name][] = $this->measure($name, $run, $operation);
            }
        }

        if ($mode === 'warm' || $benchmark !== 'all') {
            $this->line('summary|min_ms|median_ms|avg_ms|max_ms');
            foreach ($results as $name => $measurements) {
                $values = array_column($measurements, 'elapsed_ms');
                sort($values);
                $count = count($values);
                $this->line(sprintf(
                    '%s|%.2f|%.2f|%.2f|%.2f',
                    $name,
                    $values[0],
                    $this->median($values),
                    array_sum($values) / $count,
                    $values[$count - 1],
                ));
            }
        }

        return self::SUCCESS;
    }

    /** @return array<string, callable> */
    private function benchmarks(): array
    {
        return [
            'pdo_connection' => fn () => DB::connection()->getPdo(),
            'select_1' => fn () => DB::select('SELECT 1'),
            'sessions_simple' => fn () => DB::select('SELECT id FROM sessions LIMIT 1'),
            'users_simple' => fn () => DB::select('SELECT id FROM users ORDER BY id LIMIT 1'),
            'products_simple' => fn () => DB::select('SELECT id FROM products ORDER BY id LIMIT 1'),
            'categories_simple' => fn () => DB::select('SELECT id, name FROM categories ORDER BY name'),
            'vendors_simple' => fn () => DB::select('SELECT id, user_id FROM vendors ORDER BY id'),
            'count_users' => fn () => [DB::table('users')->count()],
            'count_products' => fn () => [DB::table('products')->count()],
            'count_categories' => fn () => [DB::table('categories')->count()],
            'count_vendors' => fn () => [DB::table('vendors')->count()],
            'count_orders' => fn () => [DB::table('orders')->count()],
            'sessions_count' => fn () => [DB::table('sessions')->count()],
        ];
    }

    /** @return array{elapsed_ms: float, row_count: int} */
    private function measure(string $benchmark, int $run, callable $operation): array
    {
        $started = hrtime(true);
        $result = $operation();
        $elapsedMs = (hrtime(true) - $started) / 1_000_000;
        $rowCount = is_countable($result) ? count($result) : 0;

        $this->line(sprintf(
            'measurement|%s|%d|%.2f|%d',
            $benchmark,
            $run,
            $elapsedMs,
            $rowCount,
        ));

        return ['elapsed_ms' => $elapsedMs, 'row_count' => $rowCount];
    }

    /** @param list<float> $values */
    private function median(array $values): float
    {
        $middle = intdiv(count($values), 2);

        return count($values) % 2 === 0
            ? ($values[$middle - 1] + $values[$middle]) / 2
            : $values[$middle];
    }
}