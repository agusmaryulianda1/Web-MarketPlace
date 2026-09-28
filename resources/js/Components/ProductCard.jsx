import { Link } from '@inertiajs/react';
import { Package } from 'lucide-react';

const money = (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value));

export default function ProductCard({ product, animated = false, animationDelay }) {
    const image = product.images?.find((item) => item.is_primary) ?? product.images?.[0];
    const animationProps = animated ? { 'data-aos': 'fade-up', ...(animationDelay ? { 'data-aos-delay': animationDelay } : {}) } : {};

    return <article className="group flex min-w-0 flex-col overflow-hidden rounded-xl border border-surface-container-high bg-surface shadow-sm transition-shadow hover:shadow-md" {...animationProps}>
        <Link href={`/products/${product.slug}`} className="aspect-square overflow-hidden bg-surface-container-low focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">
            {image?.url ? <img src={image.url} alt={product.name} className="size-full object-cover transition-transform duration-300 group-hover:scale-105" /> : <span className="flex size-full items-center justify-center text-secondary" aria-label="Gambar produk tidak tersedia"><Package size={40} strokeWidth={1} /></span>}
        </Link>
        <div className="flex flex-1 flex-col p-4">
            <p className="truncate text-xs text-secondary">{product.store?.name ?? '—'}</p>
            {product.category?.name && <p className="mt-1 truncate text-xs text-secondary">{product.category.name}</p>}
            <h2 className="mt-2 line-clamp-2 min-h-10 font-heading text-sm font-semibold transition-colors group-hover:text-primary sm:text-base">{product.name}</h2>
            <p className="mt-auto pt-4 font-heading text-lg font-bold text-primary">{money(product.price)}</p>
            <Link href={`/products/${product.slug}`} className="mt-4 inline-flex min-h-11 items-center justify-center rounded-lg bg-surface-container-low px-4 text-sm font-semibold transition-colors hover:bg-primary-container hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">Lihat detail</Link>
        </div>
    </article>;
}
