import { useForm } from '@inertiajs/react';
import { ProductFormPage } from './Create';

export default function Edit({ product, categories = [] }) {
    const form = useForm({ category_id: product.category_id ?? '', name: product.name ?? '', description: product.description ?? '', price: product.price ?? '', stock: product.stock ?? '', status: product.status ?? 'active', image: null });
    const image = product.images?.find((item) => item.is_primary)?.url ?? null;
    return <ProductFormPage title="Edit Produk" description="Perbarui informasi produk dan inventaris toko Anda." form={form} categories={categories} submitLabel="Simpan Perubahan" submitUrl={`/vendor/products/${product.id}`} method="put" existingImage={image} />;
}