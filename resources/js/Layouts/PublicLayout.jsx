import { Link, router } from "@inertiajs/react";
import { Menu, Search, Store, X } from "lucide-react";
import { useState } from "react";

const navItems = [
    { label: "Beranda", href: "/" },
    { label: "Produk", href: "/products" },
];

const applicationName = import.meta.env.VITE_APP_NAME ?? "MarketPlace";
const footerLinks = [
    { label: "Beranda", href: "/" },
    { label: "Produk", href: "/products" },
    { label: "Login", href: "/login" },
    { label: "Register", href: "/register" },
];

export default function PublicLayout({ children }) {
    const [mobileOpen, setMobileOpen] = useState(false);
    const [search, setSearch] = useState("");
    const currentPath = window.location.pathname;
    const submitSearch = (event) => {
        event.preventDefault();
        router.get("/products", search.trim() ? { search: search.trim() } : {});
    };
    const navLinks = (onClick) =>
        navItems.map(({ label, href }) => {
            const active =
                href === "/"
                    ? currentPath === "/"
                    : currentPath.startsWith("/products");
            return (
                <Link
                    key={href}
                    href={href}
                    onClick={onClick}
                    aria-current={active ? "page" : undefined}
                    className={`min-h-11 px-3 py-2 text-sm font-semibold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary ${active ? "text-primary" : "text-secondary hover:text-foreground"}`}
                >
                    {label}
                </Link>
            );
        });

    return (
        <div className="min-h-screen bg-background text-foreground">
            <header className="sticky top-0 z-40 border-b border-surface-container-high bg-surface/95 shadow-sm backdrop-blur">
                <div className="mx-auto flex min-h-20 max-w-7xl items-center gap-4 px-5 sm:px-8 lg:px-10">
                    <Link
                        href="/"
                        className="flex shrink-0 items-center gap-2 font-heading text-lg font-bold tracking-tight"
                    >
                        <span className="flex size-8 items-center justify-center rounded-lg bg-primary-container text-white">
                            <Store size={18} aria-hidden="true" />
                        </span>
                        <span>{applicationName}</span>
                    </Link>
                    <form
                        onSubmit={submitSearch}
                        className="relative hidden max-w-md flex-1 sm:block"
                    >
                        <label htmlFor="public-search" className="sr-only">
                            Cari produk
                        </label>
                        <Search
                            size={18}
                            className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-secondary"
                            aria-hidden="true"
                        />
                        <input
                            id="public-search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Cari produk..."
                            className="min-h-11 w-full rounded-lg border border-surface-container-high bg-surface-container-low py-2 pl-10 pr-4 text-sm outline-none transition focus:border-primary-container focus:ring-1 focus:ring-primary-container"
                        />
                    </form>
                    <nav
                        className="ml-auto hidden items-center gap-1 md:flex"
                        aria-label="Navigasi utama"
                    >
                        {navLinks()}
                    </nav>
                    <div className="hidden items-center gap-2 sm:flex">
                        <Link
                            href="/login"
                            className="inline-flex min-h-11 items-center rounded-lg px-4 text-sm font-semibold hover:bg-surface-container-low focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                        >
                            Login
                        </Link>
                        <Link
                            href="/register"
                            className="inline-flex min-h-11 items-center rounded-lg bg-primary-container px-4 text-sm font-semibold text-white hover:bg-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                        >
                            Register
                        </Link>
                    </div>
                    <button
                        type="button"
                        onClick={() => setMobileOpen(true)}
                        className="ml-auto rounded-lg p-2 text-secondary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary md:hidden"
                        aria-label="Buka navigasi"
                        aria-expanded={mobileOpen}
                        aria-controls="public-mobile-navigation"
                    >
                        <Menu size={22} />
                    </button>
                </div>
                <form
                    onSubmit={submitSearch}
                    className="border-t border-surface-container-high px-5 py-3 sm:hidden"
                >
                    <label htmlFor="public-search-mobile" className="sr-only">
                        Cari produk
                    </label>
                    <div className="relative">
                        <Search
                            size={17}
                            className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-secondary"
                            aria-hidden="true"
                        />
                        <input
                            id="public-search-mobile"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Cari produk..."
                            className="min-h-11 w-full rounded-lg border border-surface-container-high bg-surface-container-low py-2 pl-10 pr-4 text-sm outline-none focus:border-primary-container focus:ring-1 focus:ring-primary-container"
                        />
                    </div>
                </form>
            </header>
            {mobileOpen && (
                <div className="fixed inset-0 z-50 md:hidden">
                    <button
                        type="button"
                        className="absolute inset-0 bg-black/30"
                        onClick={() => setMobileOpen(false)}
                        aria-label="Tutup navigasi"
                    />
                    <aside
                        id="public-mobile-navigation"
                        className="relative flex h-full w-72 flex-col bg-surface p-5 shadow-xl"
                    >
                        <div className="flex items-center justify-between">
                            <span className="font-heading text-lg font-bold">
                                {applicationName}
                            </span>
                            <button
                                type="button"
                                onClick={() => setMobileOpen(false)}
                                className="rounded-lg p-2 text-secondary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                                aria-label="Tutup navigasi"
                            >
                                <X size={20} />
                            </button>
                        </div>
                        <nav
                            className="mt-7 space-y-1"
                            aria-label="Navigasi mobile"
                        >
                            {navLinks(() => setMobileOpen(false))}
                        </nav>
                        <div className="mt-auto space-y-2 border-t border-surface-container-high pt-5">
                            <Link
                                href="/login"
                                onClick={() => setMobileOpen(false)}
                                className="flex min-h-11 items-center rounded-lg px-3 text-sm font-semibold hover:bg-surface-container-low"
                            >
                                Login
                            </Link>
                            <Link
                                href="/register"
                                onClick={() => setMobileOpen(false)}
                                className="flex min-h-11 items-center justify-center rounded-lg bg-primary-container px-4 text-sm font-semibold text-white hover:bg-primary"
                            >
                                Register
                            </Link>
                        </div>
                    </aside>
                </div>
            )}
            {children}
            <footer
                className="mt-8 border-t border-[#2a2d33] bg-[#101114] text-white"
                aria-label="Footer"
            >
                <div className="mx-auto max-w-7xl px-5 py-6 sm:px-8 lg:px-10">
                    <div className="grid gap-5 md:grid-cols-2">
                        <div className="max-w-md">
                            <Link
                                href="/"
                                className="inline-flex items-center gap-2 font-heading text-lg font-bold tracking-tight text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                            >
                                <span className="flex size-8 items-center justify-center rounded-lg bg-primary-container text-white">
                                    <Store size={18} aria-hidden="true" />
                                </span>
                                <span>{applicationName}</span>
                            </Link>
                            <p className="mt-2 max-w-md text-sm leading-6 text-gray-400">
                                Platform multi-vendor untuk menjelajahi produk
                                dari berbagai toko.
                            </p>
                        </div>
                        <nav aria-label="Tautan footer">
                            <p className="text-sm font-semibold text-white">
                                Navigasi
                            </p>
                            <ul className="mt-2 grid grid-cols-1 gap-y-1 sm:grid-cols-2 sm:gap-x-6">
                                {footerLinks.map(({ label, href }) => (
                                    <li key={href}>
                                        <Link
                                            href={href}
                                            className="inline-flex min-h-9 items-center text-sm text-gray-300 transition-colors hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                                        >
                                            {label}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </nav>
                    </div>
                    <div className="mt-5 border-t border-[#2a2d33] pt-4">
                        <p className="text-center text-sm text-gray-400 sm:text-left">
                            © {new Date().getFullYear()} {applicationName}. Hak
                            cipta dilindungi.
                        </p>
                    </div>
                </div>
            </footer>
        </div>
    );
}
