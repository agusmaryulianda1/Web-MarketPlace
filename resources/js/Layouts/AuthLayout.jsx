import { Store } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function AuthLayout({ children, alternateHref, alternateLabel, alternateText }) {
    return (
        <div className="min-h-screen bg-background text-foreground">
            <header className="border-b border-surface-container-high bg-surface">
                <div className="mx-auto flex min-h-20 max-w-[1440px] items-center justify-between px-5 sm:px-8 lg:px-15">
                    <Link href="/" className="flex items-center gap-3" aria-label="MarketPlace home">
                        <span className="flex size-12 items-center justify-center rounded-xl bg-primary-container text-white">
                            <Store size={25} strokeWidth={2.5} aria-hidden="true" />
                        </span>
                        <span className="leading-none">
                            <span className="block font-heading text-[27px] font-extrabold tracking-tight">
                                Market<span className="text-primary-container">Place</span>
                            </span>
                            <span className="mt-1 block text-[11px] font-medium tracking-[0.08em] text-secondary">
                                ARCHITECTURAL COMMERCE
                            </span>
                        </span>
                    </Link>

                    <p className="hidden items-center gap-4 text-base text-secondary sm:flex">
                        {alternateText}
                        <Link
                            href={alternateHref}
                            className="rounded-lg border border-primary-container px-5 py-2 font-semibold text-primary-container transition-colors hover:bg-primary-container hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-container focus-visible:ring-offset-2"
                        >
                            {alternateLabel}
                        </Link>
                    </p>
                </div>
            </header>

            <main className="flex min-h-[calc(100vh-5rem)] items-start justify-center px-4 py-10 sm:px-6 sm:py-12 lg:py-16">
                {children}
            </main>

            <footer className="border-t border-surface-container-high bg-surface-container-low px-5 py-6 text-sm text-secondary sm:px-8 lg:px-15">
                <div className="mx-auto flex max-w-[1440px] flex-col items-center justify-between gap-4 sm:flex-row">
                    <span>© 2024 MarketPlace Inc. Hak cipta dilindungi undang-undang.</span>
                    <nav className="flex gap-6" aria-label="Footer">
                        <a href="#" className="hover:text-primary">Bantuan</a>
                        <a href="#" className="hover:text-primary">Kebijakan Privasi</a>
                        <a href="#" className="hover:text-primary">Hubungi Kami</a>
                    </nav>
                </div>
            </footer>
        </div>
    );
}