<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class VendorProductsExport implements FromQuery, WithHeadings, WithMapping
{
    private int $rowNumber = 0;

    public function __construct(private readonly int $vendorId, private readonly ?string $search, private readonly ?string $status) {}

    public function query(): Builder
    {
        return Product::query()->with('category')->where('vendor_id', $this->vendorId)
            ->when($this->search, fn (Builder $query) => $query->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($this->search).'%']))
            ->when($this->status === 'active', fn (Builder $query) => $query->where('status', 'active'))
            ->when($this->status === 'out_of_stock', fn (Builder $query) => $query->where('stock', 0))
            ->latest();
    }

    public function headings(): array
    {
        return ['No', 'Nama Produk', 'Kategori', 'Harga', 'Stok', 'Status', 'Dibuat'];
    }

    public function map($product): array
    {
        return [++$this->rowNumber, $product->name, $product->category?->name, $product->price, $product->stock, $product->status, $product->created_at?->format('Y-m-d H:i:s')];
    }
}