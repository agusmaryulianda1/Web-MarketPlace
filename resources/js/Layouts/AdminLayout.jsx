import { Link, useForm, usePage } from '@inertiajs/react';
import { ClipboardList, LayoutDashboard, Menu, Package, PanelLeftClose, Store, Tags, X } from 'lucide-react';
import { useState } from 'react';

const navigation = [
    { label: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard },
    { label: 'Categories', href: '/admin/categories', icon: Tags },
    { label: 'Products', href: '/admin/products', icon: Package },
    { label: 'Pesanan', href: '/admin/orders', icon: ClipboardList },
];

export default function AdminLayout({ children }) {
    const [mobileOpen, setMobileOpen] = useState(false);
    const { props } = usePage();
    const logout = useForm();
    const currentPath = window.location.pathname;
    const submitLogout = (event) => { event.preventDefault(); logout.post('/logout'); };

    return <div className="min-h-screen bg-background text-foreground">
        <aside className="fixed inset-y-0 left-0 z-40 hidden w-72 flex-col border-r border-surface-container-high bg-surface shadow-sm lg:flex"><Sidebar user={props.auth?.user} currentPath={currentPath} onLogout={submitLogout} /></aside>
        {mobileOpen && <div className="fixed inset-0 z-50 lg:hidden"><button className="absolute inset-0 bg-black/30" onClick={() => setMobileOpen(false)} aria-label="Close navigation" /><aside className="relative flex h-full w-72 flex-col bg-surface shadow-xl"><div className="flex justify-end p-4"><button className="rounded-lg p-2 text-secondary" onClick={() => setMobileOpen(false)} aria-label="Close navigation"><X size={20} /></button></div><Sidebar user={props.auth?.user} currentPath={currentPath} onLogout={submitLogout} onNavigate={() => setMobileOpen(false)} /></aside></div>}
        <div className="lg:pl-72"><header className="sticky top-0 z-30 flex min-h-20 items-center justify-between gap-4 border-b border-surface-container-high bg-surface px-5 shadow-sm sm:px-8 lg:px-10"><div className="flex min-w-0 items-center gap-3"><button className="rounded-lg p-2 text-secondary lg:hidden" onClick={() => setMobileOpen(true)} aria-label="Open navigation"><Menu size={22} /></button><div className="hidden items-center gap-2 text-sm text-secondary sm:flex"><span>Admin Portal</span><span>/</span><strong className="text-foreground">Ringkasan Sistem</strong></div></div><div className="flex items-center gap-2 rounded-full border border-surface-container-high bg-surface-container-low px-3 py-1.5 text-xs font-semibold"><span className="size-2 rounded-full bg-primary-container" /><span className="hidden sm:inline">Sistem Normal (Online)</span><span className="sm:hidden">Online</span></div></header>{children}</div>
    </div>;
}

function Sidebar({ user, currentPath, onLogout, onNavigate }) {
    return <div className="flex h-full flex-col justify-between p-4"><div><div className="flex items-center gap-3 px-2 pt-1"><span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary-container text-white"><Store size={21} /></span><div className="min-w-0"><div className="flex items-center gap-2"><span className="font-heading text-lg font-bold">MarketPlace</span><span className="rounded border border-surface-container-high bg-surface-container px-1.5 py-0.5 text-[11px] text-secondary">Admin</span></div><p className="text-xs text-secondary">Seller &amp; Admin Central</p></div></div><nav className="mt-8 space-y-1" aria-label="Admin navigation">{navigation.map(({ label, href, icon: Icon }) => <Link key={href} href={href} onClick={onNavigate} className={`flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold ${currentPath === href || currentPath.startsWith(`${href}/`) ? 'bg-primary-container/10 text-primary' : 'text-secondary hover:bg-surface-container-low hover:text-foreground'}`}><Icon size={18} />{label}</Link>)}</nav></div><div className="border-t border-surface-container-high pt-4"><div className="mb-3 flex items-center gap-3 px-2"><span className="flex size-9 items-center justify-center rounded-full bg-primary-container/15 font-heading font-bold text-primary">{user?.name?.charAt(0)?.toUpperCase() ?? 'A'}</span><div className="min-w-0"><p className="truncate text-sm font-semibold">{user?.name ?? 'Administrator'}</p><p className="truncate text-xs text-secondary">{user?.email ?? 'Admin account'}</p></div></div><form onSubmit={onLogout}><button type="submit" className="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold text-secondary hover:bg-surface-container-low"><PanelLeftClose size={18} />Keluar</button></form></div></div>;
}