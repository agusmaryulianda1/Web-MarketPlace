import { Head, Link } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { ArrowRight, BriefcaseBusiness, Check, Home as HomeIcon, Laptop, PackageCheck, ShieldCheck, ShoppingBag, Sparkles } from 'lucide-react';
import BuyerLayout from '../../Layouts/BuyerLayout';

const categoryIcons = [Laptop, BriefcaseBusiness, HomeIcon, ShoppingBag, Sparkles, PackageCheck];
export default function Dashboard({ categories = [], products = [], filters = {} }) {
    const activeCategory = filters.category;

    return <BuyerLayout><Head title="Home" /><main className="overflow-hidden bg-background"><section className="mx-auto max-w-7xl px-5 pt-8 sm:px-8 lg:px-10"><div className="grid gap-8 rounded-xl border border-surface-container-high bg-surface p-6 sm:p-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-center"><div><span className="inline-flex items-center gap-2 rounded-full border border-surface-container-high bg-surface-container-low px-3 py-1 text-xs font-semibold text-secondary"><span className="size-1.5 rounded-full bg-primary-container" />Platform multi-vendor</span><h1 className="mt-5 max-w-2xl font-heading text-4xl font-bold leading-tight tracking-tight sm:text-5xl">Temukan produk terbaik dari <span className="text-primary">vendor terpercaya</span> di Indonesia</h1><p className="mt-5 max-w-xl text-sm leading-6 text-secondary sm:text-base">Jelajahi produk dari berbagai toko dan temukan pilihan yang sesuai kebutuhanmu.</p><Link href="/buyer/products" className="mt-7 inline-flex min-h-11 items-center gap-2 rounded-lg bg-primary-container px-5 text-sm font-semibold text-white hover:bg-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">Jelajahi Katalog<ArrowRight size={17} /></Link></div><div className="hidden min-h-64 overflow-hidden rounded-xl bg-gradient-to-br from-surface-container-low to-surface-container-high lg:block"><img src="/images/buyer/hero.png" alt="Koleksi produk pilihan dari vendor terpercaya" className="size-full min-h-64 object-cover object-center" /></div></div></section>
        <section className="mx-auto max-w-7xl px-5 py-12 sm:px-8 lg:px-10" aria-labelledby="categories-heading"><div className="flex items-end justify-between gap-4"><div><h2 id="categories-heading" className="font-heading text-xl font-bold">Kategori pilihan</h2><p className="mt-1 text-sm text-secondary">Eksplorasi produk dari berbagai kategori.</p></div><Link href="/buyer/products" className="hidden items-center gap-1 text-sm font-semibold text-primary sm:inline-flex">Lihat katalog<ArrowRight size={15} /></Link></div>{categories.length ? <CategoryScroller categories={categories} activeCategory={activeCategory} /> : <p className="mt-6 rounded-xl border border-dashed border-surface-container-high p-8 text-center text-sm text-secondary">Kategori belum tersedia.</p>}</section>
        <section className="mx-auto max-w-7xl px-5 pb-12 sm:px-8 lg:px-10" aria-labelledby="products-heading"><div className="flex items-end justify-between gap-4"><div><h2 id="products-heading" className="font-heading text-xl font-bold">Produk pilihan vendor</h2><p className="mt-1 text-sm text-secondary">Pilihan produk aktif dari vendor.</p></div><Link href="/buyer/products" className="hidden items-center gap-1 text-sm font-semibold text-primary sm:inline-flex">Lihat semua<ArrowRight size={15} /></Link></div>{products.length ? <div className="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-4">{products.map((product) => <ProductCard key={product.id} product={product} />)}</div> : <p className="mt-6 rounded-xl border border-dashed border-surface-container-high p-8 text-center text-sm text-secondary">Produk belum tersedia.</p>}</section>
        <section className="mx-auto max-w-7xl px-5 pb-14 sm:px-8 lg:px-10" aria-label="Informasi platform"><div className="grid gap-4 rounded-xl border border-surface-container-high bg-surface p-6 sm:grid-cols-3"><Benefit icon={ShieldCheck} title="Pilihan terkurasi" text="Produk aktif dari vendor yang tersedia di platform." /><Benefit icon={PackageCheck} title="Informasi jelas" text="Lihat detail produk dan informasi toko sebelum membeli." /><Benefit icon={Check} title="Alur sederhana" text="Temukan produk dengan pengalaman belanja yang mudah." /></div></section>
    </main></BuyerLayout>;
}

function CategoryScroller({ categories, activeCategory }) {
    const containerRef = useRef(null);
    const animationFrameRef = useRef(null);
    const resumeTimeoutsRef = useRef(new Map());
    const directionRef = useRef(1);
    const previousTimestampRef = useRef(null);
    const pausedReasonsRef = useRef(new Set());

    useEffect(() => {
        const container = containerRef.current;
        if (!container) return undefined;

        const resumeTimeouts = resumeTimeoutsRef.current;
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        const pause = (reason) => {
            pausedReasonsRef.current.add(reason);
        };
        const resume = (reason) => {
            pausedReasonsRef.current.delete(reason);
        };
        const pauseThenResume = (reason) => {
            pause(reason);
            window.clearTimeout(resumeTimeoutsRef.current.get(reason));
            resumeTimeoutsRef.current.set(reason, window.setTimeout(() => {
                resume(reason);
                resumeTimeoutsRef.current.delete(reason);
            }, 700));
        };
        const handleVisibility = () => {
            if (document.hidden) pause('visibility');
            else resume('visibility');
        };
        const handleWheel = () => pauseThenResume('manual-scroll');
        const startAnimation = () => {
            if (!animationFrameRef.current && !reducedMotion.matches && !document.hidden) {
                previousTimestampRef.current = null;
                animationFrameRef.current = window.requestAnimationFrame(animate);
            }
        };
        const handleReducedMotion = (event) => {
            if (event.matches) {
                pause('reduced-motion');
                if (animationFrameRef.current) window.cancelAnimationFrame(animationFrameRef.current);
                animationFrameRef.current = null;
            } else {
                resume('reduced-motion');
                startAnimation();
            }
        };
        const animate = (timestamp) => {
            if (previousTimestampRef.current === null) previousTimestampRef.current = timestamp;
            const deltaSeconds = Math.min((timestamp - previousTimestampRef.current) / 1000, 0.1);
            previousTimestampRef.current = timestamp;
            const maxScroll = container.scrollWidth - container.clientWidth;

            if (maxScroll <= 0) {
                animationFrameRef.current = window.requestAnimationFrame(animate);
                return;
            }

            if (!pausedReasonsRef.current.size) {
                const nextScroll = container.scrollLeft + (directionRef.current * 30 * deltaSeconds);
                if (nextScroll >= maxScroll) {
                    container.scrollLeft = maxScroll;
                    directionRef.current = -1;
                } else if (nextScroll <= 0) {
                    container.scrollLeft = 0;
                    directionRef.current = 1;
                } else {
                    container.scrollLeft = nextScroll;
                }
            }
            animationFrameRef.current = window.requestAnimationFrame(animate);
        };

        const activeItem = activeCategory
            ? [...container.querySelectorAll('[data-category-id]')].find((item) => item.dataset.categoryId === activeCategory)
            : null;
        if (activeItem) {
            const targetScroll = activeItem.offsetLeft - ((container.clientWidth - activeItem.offsetWidth) / 2);
            const initialScroll = Math.max(0, Math.min(targetScroll, container.scrollWidth - container.clientWidth));
            container.scrollLeft = initialScroll;
        }
        if (reducedMotion.matches) pause('reduced-motion');
        if (document.hidden) pause('visibility');
        container.addEventListener('wheel', handleWheel, { passive: true });
        document.addEventListener('visibilitychange', handleVisibility);
        reducedMotion.addEventListener?.('change', handleReducedMotion);
        const startTimeout = window.setTimeout(startAnimation, 100);

        return () => {
            window.clearTimeout(startTimeout);
            resumeTimeouts.forEach((timeout) => window.clearTimeout(timeout));
            resumeTimeouts.clear();
            if (animationFrameRef.current) window.cancelAnimationFrame(animationFrameRef.current);
            container.removeEventListener('wheel', handleWheel);
            document.removeEventListener('visibilitychange', handleVisibility);
            reducedMotion.removeEventListener?.('change', handleReducedMotion);
        };
    }, [activeCategory, categories.length]);

    const pause = (reason) => pausedReasonsRef.current.add(reason);
    const delayedResume = (reason) => {
        pause(reason);
        window.clearTimeout(resumeTimeoutsRef.current.get(reason));
        resumeTimeoutsRef.current.set(reason, window.setTimeout(() => {
            pausedReasonsRef.current.delete(reason);
            resumeTimeoutsRef.current.delete(reason);
        }, 700));
    };

    const cardClass = 'group flex h-32 w-36 flex-none shrink-0 snap-start flex-col items-center justify-center rounded-xl border bg-surface p-4 text-center hover:border-primary/40 hover:shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary sm:w-40 lg:w-44';
    const handleKeyDown = (event) => {
        if (['ArrowLeft', 'ArrowRight', 'Home', 'End', 'PageUp', 'PageDown', ' '].includes(event.key)) {
            delayedResume('manual-scroll');
        }
    };
    return <div ref={containerRef} className="mt-6 flex flex-nowrap snap-none gap-3 overflow-x-auto pb-2" onPointerDown={() => pause('pointer-interaction')} onPointerUp={() => delayedResume('pointer-interaction')} onPointerCancel={() => delayedResume('pointer-interaction')} onTouchStart={() => pause('touch')} onTouchEnd={() => delayedResume('touch')} onTouchCancel={() => delayedResume('touch')} onFocusIn={() => pause('focus')} onFocusOut={() => delayedResume('focus')} onKeyDown={handleKeyDown}>
        <Link href="/buyer/dashboard" aria-current={!activeCategory ? 'page' : undefined} className={`${cardClass} ${!activeCategory ? 'border-primary' : 'border-surface-container-high'}`}><span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-surface-container-low text-secondary group-hover:bg-primary-container/10 group-hover:text-primary"><PackageCheck size={21} /></span><span className="mt-3 min-h-10 line-clamp-2 text-sm font-semibold group-hover:text-primary">Semua</span></Link>{categories.map((category, index) => { const Icon = categoryIcons[index % categoryIcons.length]; const isActive = activeCategory === category.slug; return <Link key={category.id} data-category-id={category.slug} href={`/buyer/dashboard?category=${encodeURIComponent(category.slug)}`} aria-current={isActive ? 'page' : undefined} className={`${cardClass} ${isActive ? 'border-primary' : 'border-surface-container-high'}`}><span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-surface-container-low text-secondary group-hover:bg-primary-container/10 group-hover:text-primary"><Icon size={21} /></span><span className="mt-3 min-h-10 line-clamp-2 text-sm font-semibold group-hover:text-primary">{category.name}</span></Link>; })}</div>;
}

function ProductCard({ product }) {
    const image = product.images?.find((item) => item.is_primary) ?? product.images?.[0];
    const storeName = product.vendor?.store_name ?? product.vendor?.name ?? 'Vendor';
    return <article className="group flex flex-col overflow-hidden rounded-xl border border-surface-container-high bg-surface shadow-sm"><div className="aspect-square bg-surface-container-low">{image?.url ? <img src={image.url} alt={product.name} className="size-full object-cover transition-transform duration-300 group-hover:scale-105" /> : <div className="flex size-full items-center justify-center text-secondary" aria-label="Gambar produk tidak tersedia"><PackageCheck size={42} strokeWidth={1} /></div>}</div><div className="flex flex-1 flex-col p-4"><p className="truncate text-xs text-secondary">{storeName}</p><h3 className="mt-2 line-clamp-2 font-heading text-base font-semibold group-hover:text-primary">{product.name}</h3><p className="mt-3 font-heading text-lg font-bold">Rp {Number(product.price).toLocaleString('id-ID')}</p><Link href={`/buyer/products/${product.slug}`} className="mt-4 inline-flex min-h-11 items-center justify-center rounded-lg bg-surface-container-low px-4 text-sm font-semibold hover:bg-primary-container hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">Lihat detail</Link></div></article>;
}

function Benefit({ icon: Icon, title, text }) { return <div className="flex gap-3"><span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary-container/10 text-primary"><Icon size={19} /></span><div><h3 className="text-sm font-bold">{title}</h3><p className="mt-1 text-sm leading-5 text-secondary">{text}</p></div></div>; }