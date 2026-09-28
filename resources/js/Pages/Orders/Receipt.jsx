import { Head } from '@inertiajs/react';
import { CalendarDays, CreditCard, MapPin, Printer, ReceiptText, Store, UserRound } from 'lucide-react';
import { PaymentStatusBadge } from '@/Components/Order/StatusBadge';

const methods = { bank_transfer: 'Transfer Bank', cod: 'Bayar di Tempat' };
const text = (value, fallback) => typeof value === 'string' && value.trim() ? value : fallback;
const method = (value) => value ? methods[value] ?? String(value) : 'Tidak tersedia';

function money(value) {
    if (!['number', 'string'].includes(typeof value) || (typeof value === 'string' && !value.trim()) || !Number.isFinite(Number(value))) return '-';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(Number(value));
}

function date(value, fallback = 'Tidak tersedia') {
    if (!value) return fallback;
    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime()) ? fallback : new Intl.DateTimeFormat('id-ID', { dateStyle: 'long', timeStyle: 'short' }).format(parsed);
}

export default function Receipt({ receipt = {} }) {
    const groups = Array.isArray(receipt.vendor_groups) ? receipt.vendor_groups : [];
    const storeNames = [...new Set(groups.map((group) => text(group?.store_name, text(group?.vendor_name, ''))).filter(Boolean))];
    const storeHeading = storeNames.join(', ') || 'Toko Marketplace';
    const orderNumber = text(receipt.order_number, 'Tidak tersedia');

    return <>
        <Head title={`Bukti Pembayaran ${orderNumber}`} />
        <main className="payment-receipt min-h-screen bg-background px-4 py-6 text-foreground print:bg-white print:p-0 sm:px-6 sm:py-10">
            <article className="payment-receipt__document mx-auto w-full max-w-4xl overflow-hidden rounded-2xl border border-surface-container-high bg-surface shadow-sm print:max-w-none print:rounded-none print:border-0 print:shadow-none">
                <header className="border-b border-surface-container-high bg-surface-container-low px-5 py-6 print:bg-white sm:px-8 sm:py-8">
                    <div className="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                        <div className="min-w-0"><p className="flex items-start gap-2 break-words text-sm font-bold text-primary"><ReceiptText size={18} className="mt-0.5 shrink-0" aria-hidden="true" /><span>{storeHeading}</span></p><h1 className="mt-3 font-heading text-3xl font-bold tracking-tight sm:text-4xl">Bukti Pembayaran</h1><p className="mt-2 break-words text-sm text-secondary">Nomor pesanan: <span className="font-semibold text-foreground">{orderNumber}</span></p></div>
                        <button type="button" onClick={() => window.print()} aria-label={`Cetak struk pesanan ${orderNumber}`} className="inline-flex min-h-11 items-center justify-center gap-2 self-start rounded-lg bg-primary px-4 py-2.5 font-semibold text-white hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 print:hidden"><Printer size={17} aria-hidden="true" />Cetak Struk</button>
                    </div>
                </header>
                <div className="space-y-8 px-5 py-6 sm:px-8 sm:py-8">
                    <section aria-labelledby="payment-details-title"><h2 id="payment-details-title" className="font-heading text-lg font-bold">Informasi Pembayaran</h2><dl className="payment-receipt__metadata mt-4 grid gap-4 rounded-xl border border-surface-container-high bg-surface-container-low p-4 text-sm print:bg-white sm:grid-cols-2 sm:p-5"><Detail icon={CalendarDays} label="Tanggal Pesanan" value={date(receipt.ordered_at)} /><Detail icon={CalendarDays} label="Tanggal Pembayaran" value={date(receipt.paid_at)} /><Detail icon={CreditCard} label="Metode Pembayaran" value={method(receipt.payment_method)} /><Detail label="Status Pembayaran" value={<PaymentStatusBadge status={receipt.payment_status} />} /></dl></section>
                    <div className="grid gap-6 sm:grid-cols-2"><Info id="buyer-title" icon={UserRound} title="Pembeli"><p className="break-words font-semibold">{text(receipt.buyer?.name, 'Pembeli tidak tersedia')}</p><p className="mt-1 break-all text-sm text-secondary">{text(receipt.buyer?.email, 'Email tidak tersedia')}</p></Info><Info id="shipping-title" icon={MapPin} title="Alamat Pengiriman"><p className="whitespace-pre-wrap break-words text-sm leading-6 text-secondary">{text(receipt.shipping_address, 'Alamat tidak tersedia')}</p></Info></div>
                    <section aria-labelledby="transaction-details-title"><h2 id="transaction-details-title" className="font-heading text-lg font-bold">Rincian Transaksi</h2>{groups.length === 0 ? <p className="payment-receipt__section-card mt-4 rounded-xl border border-dashed border-surface-container-high p-6 text-center text-sm text-secondary">Rincian item transaksi tidak tersedia.</p> : <div className="mt-4 space-y-6">{groups.map((group, index) => <Group key={`${group?.vendor_name ?? 'vendor'}-${index}`} group={group} index={index} />)}</div>}</section>
                    <footer className="payment-receipt__total flex flex-col gap-2 border-t-2 border-foreground pt-5 sm:flex-row sm:items-end sm:justify-between"><div><p className="text-sm text-secondary">Total Pembayaran</p><p className="mt-1 text-xs text-secondary">Total seluruh vendor dalam pesanan ini</p></div><p className="break-words font-heading text-2xl font-bold text-primary sm:text-3xl">{money(receipt.total_amount)}</p></footer>
                </div>
            </article>
        </main>
    </>;
}

function Detail({ icon: Icon, label, value }) {
    return <div className="min-w-0"><dt className="flex items-center gap-2 text-secondary">{Icon && <Icon size={16} aria-hidden="true" />}{label}</dt><dd className="mt-1.5 break-words font-semibold">{value}</dd></div>;
}

function Info({ id, icon: Icon, title, children }) {
    return <section aria-labelledby={id} className="payment-receipt__info rounded-xl border border-surface-container-high p-5"><h2 id={id} className="flex items-center gap-2 font-heading text-lg font-bold"><Icon size={19} className="text-primary" aria-hidden="true" />{title}</h2><div className="mt-4">{children}</div></section>;
}

function Group({ group = {}, index }) {
    const items = Array.isArray(group.items) ? group.items : [];
    const headingId = `vendor-group-${index}`;

    return <section aria-labelledby={headingId} className="payment-receipt__vendor-group overflow-hidden rounded-xl border border-surface-container-high">
        <header className="payment-receipt__vendor-header flex flex-col gap-3 border-b border-surface-container-high bg-surface-container-low px-4 py-4 print:bg-white sm:flex-row sm:items-center sm:justify-between sm:px-5"><h3 id={headingId} className="flex min-w-0 items-center gap-2 font-heading font-bold"><Store size={18} className="shrink-0 text-primary" aria-hidden="true" /><span className="break-words">{text(group.vendor_name, 'Toko tidak tersedia')}</span></h3><p className="text-sm text-secondary">Subtotal toko: <strong className="text-foreground">{money(group.subtotal)}</strong></p></header>
        {items.length === 0 ? <p className="p-5 text-sm text-secondary">Tidak ada rincian item untuk toko ini.</p> : <>
            <div className="payment-receipt__mobile-items divide-y divide-surface-container-high md:hidden">{items.map((item, itemIndex) => <MobileItem key={itemIndex} item={item} />)}</div>
            <div className="payment-receipt__table-wrapper hidden overflow-x-auto md:block"><table className="payment-receipt__table w-full table-fixed text-sm"><caption className="sr-only">Rincian item dari {text(group.vendor_name, 'toko ini')}</caption><thead className="bg-surface-container-low text-left text-xs uppercase tracking-wide text-secondary print:bg-white"><tr><th scope="col" className="w-[40%] px-5 py-3 font-semibold">Produk</th><th scope="col" className="w-[22%] px-3 py-3 font-semibold">Harga</th><th scope="col" className="w-[12%] px-3 py-3 text-center font-semibold">Jumlah</th><th scope="col" className="w-[26%] px-5 py-3 text-right font-semibold">Subtotal</th></tr></thead><tbody className="divide-y divide-surface-container-high">{items.map((item, itemIndex) => <tr key={itemIndex}><td className="break-words px-5 py-4 font-medium">{text(item?.product_name, 'Produk tidak tersedia')}</td><td className="break-words px-3 py-4">{money(item?.price)}</td><td className="px-3 py-4 text-center">{item?.quantity ?? '-'}</td><td className="break-words px-5 py-4 text-right font-semibold">{money(item?.subtotal)}</td></tr>)}</tbody></table></div>
        </>}
    </section>;
}

function MobileItem({ item = {} }) {
    return <dl className="grid grid-cols-2 gap-x-4 gap-y-3 p-4 text-sm"><div className="col-span-2"><dt className="text-xs uppercase tracking-wide text-secondary">Produk</dt><dd className="mt-1 break-words font-semibold">{text(item.product_name, 'Produk tidak tersedia')}</dd></div><Detail label="Harga" value={money(item.price)} /><Detail label="Jumlah" value={item.quantity ?? '-'} /><div className="col-span-2 flex items-start justify-between gap-4 border-t border-surface-container-high pt-3"><dt className="text-secondary">Subtotal</dt><dd className="break-words text-right font-semibold">{money(item.subtotal)}</dd></div></dl>;
}
