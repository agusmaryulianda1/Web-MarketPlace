import { Head, Link, router, usePage } from "@inertiajs/react";
import { useEffect, useRef, useState } from "react";
import {
    ChevronLeft,
    ChevronRight,
    Download,
    LoaderCircle,
    Package,
    Pencil,
    PlusCircle,
    Search,
    Trash2,
} from "lucide-react";
import VendorLayout from "../../../Layouts/VendorLayout";

const money = (value) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(Number(value));

export default function Index({
    products,
    stats = {},
    search = "",
    status = "",
}) {
    const { flash = {}, errors = {} } = usePage().props;
    const activeSearch = search ?? "";
    const [query, setQuery] = useState(activeSearch);
    const [draftStatus, setDraftStatus] = useState(status ?? "");
    const [isSearching, setIsSearching] = useState(false);
    const cancelSearchRef = useRef(null);
    const searchRequestRef = useRef(0);
    const effectiveQuery = query.trim().length >= 2 ? query.trim() : "";

    useEffect(() => {
        if (effectiveQuery === activeSearch) return;

        const timer = window.setTimeout(() => {
            cancelSearchRef.current?.cancel();
            const requestId = ++searchRequestRef.current;

            router.get(
                "/vendor/products",
                {
                    ...(effectiveQuery ? { search: effectiveQuery } : {}),
                    ...(status ? { status } : {}),
                },
                {
                    async: true,
                    preserveState: true,
                    replace: true,
                    onCancelToken: (token) => {
                        cancelSearchRef.current = token;
                    },
                    onStart: () => setIsSearching(true),
                    onFinish: () => {
                        if (searchRequestRef.current === requestId) {
                            cancelSearchRef.current = null;
                            setIsSearching(false);
                        }
                    },
                },
            );
        }, 400);

        return () => {
            window.clearTimeout(timer);
            cancelSearchRef.current?.cancel();
        };
    }, [activeSearch, effectiveQuery, status]);

    const submitSearch = (event) => {
        event.preventDefault();
        cancelSearchRef.current?.cancel();
        router.get(
            "/vendor/products",
            {
                ...(effectiveQuery ? { search: effectiveQuery } : {}),
                ...(draftStatus ? { status: draftStatus } : {}),
            },
            { preserveState: true, replace: true },
        );
    };
    const clearSearch = () => setQuery("");
    const clearStatus = () => {
        setDraftStatus("");
        router.get(
            "/vendor/products",
            { ...(effectiveQuery ? { search: effectiveQuery } : {}) },
            { preserveState: true, replace: true },
        );
    };
    const destroy = (product) => {
        if (window.confirm(`Hapus produk "${product.name}"?`))
            router.delete(`/vendor/products/${product.id}`, {
                preserveScroll: true,
            });
    };
    return (
        <VendorLayout>
            <Head title="Daftar Produk Toko" />
            <main className="min-h-screen bg-background px-5 py-8 text-foreground sm:px-8 lg:px-10">
                <div className="mx-auto max-w-[1400px]">
                    <header className="flex flex-col gap-5 border-b border-surface-container-high pb-7 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p className="text-sm font-semibold tracking-wide text-primary">
                                Manajemen Produk
                            </p>
                            <h1 className="mt-2 font-heading text-3xl font-bold tracking-tight sm:text-4xl">
                                Daftar Produk Toko
                            </h1>
                            <p className="mt-2 max-w-2xl text-base leading-7 text-secondary">
                                Kelola katalog produk, stok inventaris, dan
                                harga jual toko Anda.
                            </p>
                        </div>
                        <div className="flex flex-col gap-3 sm:flex-row">
                            <a
                                href={`/vendor/products/export?${new URLSearchParams({ ...(search ? { search } : {}), ...(status ? { status } : {}) })}`}
                                className="inline-flex items-center justify-center gap-2 rounded-lg border border-primary px-4 py-3 text-sm font-bold text-primary transition hover:bg-primary-container/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                            >
                                <Download size={18} aria-hidden="true" />
                                Export Excel
                            </a>
                            <Link
                                href="/vendor/products/create"
                                className="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-container px-4 py-3 text-sm font-bold text-white transition hover:bg-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                            >
                                <PlusCircle size={18} aria-hidden="true" />
                                Tambah Produk Baru
                            </Link>
                        </div>
                    </header>
                    {flash.success && (
                        <p
                            className="mt-5 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700"
                            role="status"
                        >
                            {flash.success}
                        </p>
                    )}
                    {errors.product && (
                        <p
                            className="mt-5 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700"
                            role="alert"
                        >
                            {errors.product}
                        </p>
                    )}
                    <section className="mt-8 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                        {[
                            [
                                "Total Katalog",
                                stats.total,
                                "Produk terdaftar di toko",
                            ],
                            [
                                "Produk Aktif",
                                stats.active,
                                "Sedang tampil di toko",
                            ],
                            [
                                "Stok Kritis / Menipis",
                                stats.critical,
                                "Stok 1 sampai 4 unit",
                            ],
                            [
                                "Stok Habis",
                                stats.out_of_stock,
                                "Perlu segera diisi",
                            ],
                        ].map(([label, value, note]) => (
                            <div
                                key={label}
                                className="rounded-xl border border-surface-container-high bg-white p-5 shadow-sm"
                            >
                                <p className="text-sm font-semibold text-secondary">
                                    {label}
                                </p>
                                <p className="mt-2 font-heading text-3xl font-bold">
                                    {value ?? 0}
                                </p>
                                <p className="mt-1 text-xs text-secondary">
                                    {note}
                                </p>
                            </div>
                        ))}
                    </section>
                    <section className="mt-8 overflow-hidden rounded-xl border border-surface-container-high bg-white shadow-sm">
                        <div className="flex flex-col gap-4 border-b border-surface-container-high px-5 py-4 sm:px-6 md:flex-row md:items-end md:justify-between">
                            <div>
                                <h2 className="font-heading text-lg font-bold">
                                    Katalog Produk
                                </h2>
                                <p className="mt-1 text-sm text-secondary">
                                    Daftar produk yang Anda kelola.
                                </p>
                            </div>
                            <form
                                onSubmit={submitSearch}
                                className="grid gap-2 sm:grid-cols-[minmax(0,1fr)_auto_auto]"
                                role="search"
                            >
                                <label className="relative">
                                    <span className="sr-only">
                                        Cari nama produk
                                    </span>
                                    <Search
                                        size={17}
                                        className="pointer-events-none absolute left-3 top-3 text-secondary"
                                        aria-hidden="true"
                                    />
                                    <input
                                        name="search"
                                        value={query}
                                        onChange={(event) => setQuery(event.target.value)}
                                        placeholder="Cari nama produk"
                                        className="w-full rounded-lg border border-surface-container-high py-2.5 pl-9 pr-9 text-sm outline-none focus:border-primary focus-visible:ring-2 focus-visible:ring-primary"
                                    />
                                    {isSearching && (
                                        <>
                                            <LoaderCircle
                                                size={17}
                                                className="pointer-events-none absolute right-3 top-3 animate-spin text-secondary"
                                                aria-hidden="true"
                                            />
                                            <span className="sr-only" role="status">
                                                Mencari produk
                                            </span>
                                        </>
                                    )}
                                </label>
                                <label>
                                    <span className="sr-only">
                                        Filter status produk
                                    </span>
                                    <select
                                        name="status"
                                        value={draftStatus}
                                        onChange={(event) =>
                                            setDraftStatus(event.target.value)
                                        }
                                        className="w-full rounded-lg border border-surface-container-high px-3 py-2.5 text-sm outline-none focus:border-primary focus-visible:ring-2 focus-visible:ring-primary"
                                    >
                                        <option value="">Semua status</option>
                                        <option value="active">Aktif</option>
                                        <option value="out_of_stock">
                                            Stok Habis
                                        </option>
                                    </select>
                                </label>
                                <button
                                    type="submit"
                                    className="rounded-lg bg-primary-container px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                                >
                                    Terapkan
                                </button>
                            </form>
                        </div>
                        {products.data.length === 0 ? (
                            stats.total === 0 ? (
                                <EmptyState />
                            ) : (
                                <FilteredEmptyState
                                    search={activeSearch}
                                    status={status}
                                    onClearSearch={clearSearch}
                                    onClearStatus={clearStatus}
                                />
                            )
                        ) : (
                            <div className="max-w-full overflow-x-auto">
                                <table className="w-full min-w-[900px] table-fixed text-left">
                                    <colgroup>
                                        <col className="w-[30%]" />
                                        <col className="w-[18%]" />
                                        <col className="w-[17%]" />
                                        <col className="w-[10%]" />
                                        <col className="w-[13%]" />
                                        <col className="w-[12%]" />
                                    </colgroup>
                                    <thead className="bg-surface-container-low text-xs uppercase tracking-wide text-secondary">
                                        <tr>
                                            <th
                                                scope="col"
                                                className="px-10 py-3 text-left font-semibold"
                                            >
                                                Produk
                                            </th>
                                            <th
                                                scope="col"
                                                className="px-6 py-3 text-left font-semibold"
                                            >
                                                Kategori
                                            </th>
                                            <th
                                                scope="col"
                                                className="px-10 py-3 text-right font-semibold"
                                            >
                                                Harga
                                            </th>
                                            <th
                                                scope="col"
                                                className="px-6 py-3 text-center font-semibold"
                                            >
                                                Stok
                                            </th>
                                            <th
                                                scope="col"
                                                className="px-6 py-3 text-center font-semibold"
                                            >
                                                Status
                                            </th>
                                            <th
                                                scope="col"
                                                className="px-6 py-3 text-center font-semibold"
                                            >
                                                Aksi
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-surface-container-high">
                                        {products.data.map((product) => (
                                            <ProductRow
                                                key={product.id}
                                                product={product}
                                                onDelete={destroy}
                                            />
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                        {products.total > 0 && (
                            <Pagination products={products} />
                        )}
                    </section>
                </div>
            </main>
        </VendorLayout>
    );
}

function ProductRow({ product, onDelete }) {
    const stock = Number(product.stock);
    const label =
        stock === 0
            ? "Habis"
            : product.status === "active"
              ? "Aktif"
              : "Nonaktif";
    const color =
        stock === 0
            ? "border-red-200 bg-red-50 text-red-700"
            : product.status === "active"
              ? "border-green-200 bg-green-50 text-green-700"
              : "border-surface-container-high bg-surface-container text-secondary";
    return (
        <tr className="hover:bg-surface-container-low/60">
            <td className="px-6 py-4 text-left">
                <div className="flex min-w-0 items-center gap-3">
                    <div className="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-surface-container-high bg-surface-container text-secondary">
                        {product.image_url ? (
                            <img
                                src={product.image_url}
                                alt={product.name}
                                className="size-full object-cover"
                            />
                        ) : (
                            <Package size={22} aria-hidden="true" />
                        )}
                    </div>
                    <span className="min-w-0 max-w-[260px] truncate font-semibold">
                        {product.name}
                    </span>
                </div>
            </td>
            <td className="px-6 py-4 text-left text-sm text-secondary">
                <span className="block truncate">
                    {product.category?.name ?? "Tanpa kategori"}
                </span>
            </td>
            <td className="px-6 py-4 text-right font-semibold tabular-nums">
                {money(product.price)}
            </td>
            <td
                className={`px-6 py-4 text-center font-semibold tabular-nums ${stock === 0 ? "text-red-700" : stock < 5 ? "text-amber-600" : ""}`}
            >
                {stock} unit
            </td>
            <td className="px-6 py-4 text-center">
                <span
                    className={`inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold ${color}`}
                >
                    {label}
                </span>
            </td>
            <td className="px-6 py-4 text-center">
                <div className="flex items-center justify-center gap-1">
                    <Link
                        href={`/vendor/products/${product.id}/edit`}
                        className="rounded p-2 text-primary transition hover:bg-primary-container/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                        title={`Edit ${product.name}`}
                        aria-label={`Edit produk ${product.name}`}
                    >
                        <Pencil size={18} aria-hidden="true" />
                    </Link>
                    <button
                        type="button"
                        onClick={() => onDelete(product)}
                        className="rounded p-2 text-red-700 transition hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-700"
                        title={`Hapus ${product.name}`}
                        aria-label={`Hapus produk ${product.name}`}
                    >
                        <Trash2 size={18} aria-hidden="true" />
                    </button>
                </div>
            </td>
        </tr>
    );
}
function FilteredEmptyState({
    search,
    status,
    onClearSearch,
    onClearStatus,
}) {
    const message = search
        ? `Tidak ada produk yang cocok dengan pencarian "${search}"${status ? " dan filter status ini" : ""}.`
        : "Tidak ada produk yang cocok dengan filter status ini.";

    return (
        <div className="flex flex-col items-center px-6 py-10 text-center">
            <Search className="text-secondary" size={36} aria-hidden="true" />
            <h2 className="mt-3 font-heading text-lg font-bold">
                Produk tidak tersedia
            </h2>
            <p className="mt-1 text-sm text-secondary">{message}</p>
            <button
                type="button"
                onClick={search ? onClearSearch : onClearStatus}
                className="mt-4 rounded-lg border border-primary px-4 py-2 text-sm font-semibold text-primary transition hover:bg-primary-container/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
            >
                {search ? "Hapus pencarian" : "Reset filter status"}
            </button>
        </div>
    );
}

function EmptyState() {
    return (
        <div className="flex flex-col items-center px-6 py-16 text-center">
            <Package className="text-secondary" size={42} aria-hidden="true" />
            <h2 className="mt-4 font-heading text-lg font-bold">
                Belum ada produk
            </h2>
            <p className="mt-1 text-sm text-secondary">
                Tambahkan produk pertama untuk mulai mengelola katalog toko.
            </p>
            <Link
                href="/vendor/products/create"
                className="mt-5 inline-flex items-center gap-2 rounded-lg bg-primary-container px-4 py-2.5 text-sm font-bold text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
            >
                <PlusCircle size={17} aria-hidden="true" />
                Tambah Produk Baru
            </Link>
        </div>
    );
}
function Pagination({ products }) {
    return (
        <nav
            className="flex flex-col gap-3 border-t border-surface-container-high px-6 py-4 text-sm text-secondary sm:flex-row sm:items-center sm:justify-between"
            aria-label="Paginasi produk"
        >
            <span>
                Menampilkan {products.from}–{products.to} dari {products.total}{" "}
                produk
            </span>
            <div className="flex flex-wrap items-center gap-1">
                {products.links.map((link, index) => {
                    const isPrevious = index === 0;
                    const isNext = index === products.links.length - 1;
                    const label = isPrevious
                        ? "Halaman sebelumnya"
                        : isNext
                          ? "Halaman berikutnya"
                          : `Halaman ${link.label}`;
                    return (
                        <Link
                            key={`${link.label}-${index}`}
                            href={link.url || "#"}
                            preserveScroll
                            aria-label={label}
                            aria-current={link.active ? "page" : undefined}
                            aria-disabled={!link.url ? "true" : undefined}
                            tabIndex={!link.url ? -1 : undefined}
                            className={`inline-flex size-9 items-center justify-center rounded border text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary ${link.active ? "border-primary bg-primary text-white" : "border-surface-container-high hover:bg-surface-container-low"} ${!link.url ? "pointer-events-none opacity-40" : ""}`}
                        >
                            {isPrevious ? (
                                <ChevronLeft size={17} aria-hidden="true" />
                            ) : isNext ? (
                                <ChevronRight size={17} aria-hidden="true" />
                            ) : (
                                link.label
                            )}
                        </Link>
                    );
                })}
            </div>
        </nav>
    );
}
