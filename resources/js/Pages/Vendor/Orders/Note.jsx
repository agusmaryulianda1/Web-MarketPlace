import { Head } from '@inertiajs/react';
import { CalendarDays, MapPin, Printer, Store } from 'lucide-react';
import { OrderStatusBadge } from '@/Components/Order/StatusBadge';

const text = (value, fallback) => typeof value === 'string' && value.trim() ? value : fallback;

function money(value) {
    if (!['number', 'string'].includes(typeof value) || !Number.isFinite(Number(value))) return '-';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(value));
}

function date(value) {
    if (!value) return 'Tidak tersedia';
    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime()) ? 'Tidak tersedia' : new Intl.DateTimeFormat('id-ID', { dateStyle: 'long', timeStyle: 'short' }).format(parsed);
}

export default function Note({ note = {} }) {
    const items = Array.isArray(note.items) ? note.items : [];
    const orderNumber = text(note.order_number, 'Tidak tersedia');

    return <>
        <Head title={`Nota Pesanan Vendor ${orderNumber}`} />
        <main className="vendor-order-note min-h-screen bg-background px-4 py-6 text-foreground print:bg-white print:p-0 sm:px-6 sm:py-10">
            <article className="vendor-order-note__document mx-auto w-full max-w-4xl overflow-hidden rounded-2xl border border-surface-container-high bg-surface shadow-sm print:max-w-none print:rounded-none print:border-0 print:shadow-none">
                <header className="border-b border-surface-container-high bg-surface-container-low px-5 py-6 print:bg-white sm:px-8 sm:py-8">
                    <div className="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                        <div className="min-w-0">
                            <p className="flex items-start gap-2 break-words text-sm font-bold text-primary"><Store size={18} className="mt-0.5 shrink-0" aria-hidden="true" /><span>{text(note.store_name, 'Toko tidak tersedia')}</span></p>
                            <h1 className="mt-3 font-heading text-3xl font-bold tracking-tight sm:text-4xl">Nota Pesanan Vendor</h1>
                            <p className="mt-2 max-w-2xl text-sm text-secondary">Dokumen operasional pemenuhan pesanan. Bukan bukti pembayaran atau invoice marketplace.</p>
                        </div>
                        <button type="button" onClick={() => window.print()} aria-label={`Cetak nota pesanan ${orderNumber}`} className="vendor-order-note__print-button inline-flex min-h-11 items-center justify-center gap-2 self-start rounded-lg bg-primary-container px-4 py-2.5 font-semibold text-white hover:bg-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"><Printer size={17} aria-hidden="true" />Cetak Nota</button>
                    </div>
                </header>

                <div className="space-y-7 px-5 py-6 sm:px-8 sm:py-8">
                    <section aria-labelledby="order-note-details" className="vendor-order-note__metadata grid gap-4 rounded-xl border border-surface-container-high bg-surface-container-low p-4 text-sm print:bg-white sm:grid-cols-2 sm:p-5">
                        <h2 id="order-note-details" className="sr-only">Informasi pesanan vendor</h2>
                        <Detail icon={CalendarDays} label="Nomor Pesanan" value={orderNumber} />
                        <Detail icon={CalendarDays} label="Tanggal Pesanan" value={date(note.ordered_at)} />
                        <div className="min-w-0"><dt className="text-secondary">Status Fulfillment</dt><dd className="mt-1.5"><OrderStatusBadge status={note.fulfillment_status} /></dd></div>
                    </section>

                    <section aria-labelledby="shipping-title" className="vendor-order-note__shipping rounded-xl border border-surface-container-high p-5">
                        <h2 id="shipping-title" className="flex items-center gap-2 font-heading text-lg font-bold"><MapPin size={19} className="shrink-0 text-primary" aria-hidden="true" />Penerima &amp; Alamat Pengiriman</h2>
                        <p className="mt-4 whitespace-pre-wrap break-words text-sm leading-6">{text(note.shipping_address, 'Tidak tersedia')}</p>
                    </section>

                    <section aria-labelledby="items-title" className="vendor-order-note__items overflow-hidden rounded-xl border border-surface-container-high">
                        <h2 id="items-title" className="border-b border-surface-container-high px-5 py-4 font-heading text-lg font-bold">Item Pesanan</h2>
                        {items.length === 0 ? <p className="p-5 text-sm text-secondary">Tidak ada item pesanan.</p> : <>
                            <div className="vendor-order-note__mobile-items divide-y divide-surface-container-high md:hidden">{items.map((item, index) => <MobileItem key={index} item={item} />)}</div>
                            <div className="vendor-order-note__table-wrapper hidden overflow-x-auto md:block">
                                <table className="vendor-order-note__table w-full table-fixed text-sm">
                                    <caption className="sr-only">Rincian item vendor untuk pesanan {orderNumber}</caption>
                                    <thead className="bg-surface-container-low text-left text-xs uppercase tracking-wide text-secondary print:bg-white"><tr><th scope="col" className="w-[42%] px-5 py-3 font-semibold">Produk</th><th scope="col" className="w-[22%] px-3 py-3 font-semibold">Harga</th><th scope="col" className="w-[12%] px-3 py-3 text-center font-semibold">Kuantitas</th><th scope="col" className="w-[24%] px-5 py-3 text-right font-semibold">Subtotal</th></tr></thead>
                                    <tbody className="divide-y divide-surface-container-high">{items.map((item, index) => <tr key={index}><td className="break-words px-5 py-4 font-medium">{text(item?.product_name, 'Produk tidak tersedia')}</td><td className="break-words px-3 py-4">{money(item?.price)}</td><td className="px-3 py-4 text-center">{item?.quantity ?? '-'}</td><td className="break-words px-5 py-4 text-right font-semibold">{money(item?.subtotal)}</td></tr>)}</tbody>
                                </table>
                            </div>
                        </>}
                        <dl className="vendor-order-note__subtotal flex items-start justify-between gap-4 border-t border-surface-container-high bg-surface-container-low px-5 py-4 print:bg-white"><dt className="font-heading font-bold">Subtotal Vendor</dt><dd className="break-words text-right font-heading text-lg font-bold text-primary">{money(note.vendor_subtotal)}</dd></dl>
                    </section>
                </div>
            </article>
        </main>
    </>;
}

function Detail({ icon: Icon, label, value }) {
    return <div className="min-w-0"><dt className="flex items-center gap-2 text-secondary">{Icon && <Icon size={16} aria-hidden="true" />}{label}</dt><dd className="mt-1.5 break-words font-semibold">{value}</dd></div>;
}

function MobileItem({ item = {} }) {
    return <dl className="grid grid-cols-2 gap-x-4 gap-y-3 p-4 text-sm"><div className="col-span-2"><dt className="text-xs uppercase tracking-wide text-secondary">Produk</dt><dd className="mt-1 break-words font-semibold">{text(item.product_name, 'Produk tidak tersedia')}</dd></div><Detail label="Harga" value={money(item.price)} /><Detail label="Kuantitas" value={item.quantity ?? '-'} /><div className="col-span-2 flex items-start justify-between gap-4 border-t border-surface-container-high pt-3"><dt className="text-secondary">Subtotal</dt><dd className="break-words text-right font-semibold">{money(item.subtotal)}</dd></div></dl>;
}
