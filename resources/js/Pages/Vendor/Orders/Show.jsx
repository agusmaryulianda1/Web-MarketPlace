import { Head, Link, useForm, usePage } from "@inertiajs/react";
import { useState } from "react";
import { ArrowLeft, ClipboardList, Printer } from "lucide-react";
import VendorLayout from "../../../Layouts/VendorLayout";
import { OrderStatusBadge, PaymentStatusBadge } from "@/Components/Order/StatusBadge";

const money = (value) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(Number(value));
const dateTime = (value) =>
    new Intl.DateTimeFormat("id-ID", {
        dateStyle: "full",
        timeStyle: "short",
    }).format(new Date(value));
const paymentMethods = { bank_transfer: "Transfer Bank", cod: "Bayar di Tempat" };
const paymentMethod = (value) => paymentMethods[value] ?? value ?? "-";

export default function Show({ order }) {
    const group = order.vendor_group;
    const form = useForm({ status: "", cancellation_reason: "" });
    const [cancelOpen, setCancelOpen] = useState(false);
    const { flash = {} } = usePage().props;
    const actions = group?.available_statuses || [];
    const progressActions = actions.filter((status) => status !== "cancelled");
    const canCancel = actions.includes("cancelled");
    const actionLabels = {
        processing: "Proses Pesanan",
        shipped: "Kirim Pesanan",
        completed: "Selesaikan Pesanan",
        cancelled: "Batalkan Pesanan",
    };
    const updateStatus = (status) => {
        if (status === "cancelled") {
            form.setData("status", status);
            setCancelOpen(true);
            return;
        }
        form.transform(() => ({ status }));
        form.patch(`/vendor/orders/${order.id}/groups/${group.id}/status`, { preserveScroll: true });
    };
    const cancel = (event) => {
        event.preventDefault();
        form.transform((data) => ({ status: "cancelled", cancellation_reason: data.cancellation_reason }));
        form.patch(`/vendor/orders/${order.id}/groups/${group.id}/status`, { preserveScroll: true, onSuccess: () => setCancelOpen(false) });
    };
    const closeCancel = () => {
        setCancelOpen(false);
        form.resetAndClearErrors("cancellation_reason");
    };
    return (
        <VendorLayout>
            <Head title={`Pesanan ${order.order_number}`} />
            <main className="min-h-screen bg-background px-5 py-8 text-foreground sm:px-8 lg:px-10">
                <div className="mx-auto max-w-[1400px]">
                    <Link
                        href="/vendor/orders"
                        className="inline-flex items-center gap-2 text-sm font-semibold text-primary hover:text-primary-container focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                    >
                        <ArrowLeft size={17} aria-hidden="true" />
                        Kembali ke Pesanan
                    </Link>
                    <header className="mt-5 flex flex-col gap-5 border-b border-surface-container-high pb-7 sm:flex-row sm:items-end sm:justify-between">
                        <div className="min-w-0">
                            <p className="text-sm font-semibold tracking-wide text-primary">
                                Detail Pesanan
                            </p>
                            <h1 className="mt-2 break-words font-heading text-3xl font-bold tracking-tight sm:text-4xl">
                                {order.order_number}
                            </h1>
                            <p className="mt-2 text-base text-secondary">
                                Dibuat {dateTime(order.created_at)}
                            </p>
                        </div>
                        <a
                            href={`/vendor/orders/${order.id}/note`}
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label={`Cetak nota pesanan ${order.order_number}`}
                            className="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 self-start rounded-lg border border-primary px-4 py-2.5 text-sm font-semibold text-primary transition-colors hover:bg-primary hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 sm:self-auto"
                        >
                            <Printer size={17} aria-hidden="true" />
                            Cetak Nota
                        </a>
                    </header>
                    <section className="mt-8 grid gap-5 lg:grid-cols-3">
                        <InfoCard title="Informasi Pembeli">
                            <Info
                                label="Nama pembeli"
                                value={order.buyer_name ?? "-"}
                            />
                        </InfoCard>
                        <InfoCard title="Pembayaran">
                            <Info
                                label="Metode pembayaran"
                                value={paymentMethod(order.payment_method)}
                            />
                            <Info
                                label="Status pembayaran"
                                value={
                                    <PaymentStatusBadge status={order.payment_status} />
                                }
                            />
                        </InfoCard>
                        <InfoCard title="Pesanan">
                            <Info
                                label="Status pesanan"
                                value={
                                    <OrderStatusBadge status={group?.status} />
                                }
                            />
                            {flash.success && <p className="mt-3 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700">{flash.success}</p>}
                        </InfoCard>
                    </section>
                    {actions.length > 0 && <section className="mt-5 rounded-xl border border-surface-container-high bg-surface p-5 shadow-sm">
                        <h2 className="font-heading text-base font-bold">Tindakan Pesanan</h2>
                        <div className="mt-4 flex flex-wrap gap-3">
                            {progressActions.map((status) => <button key={status} type="button" disabled={form.processing} onClick={() => updateStatus(status)} className="inline-flex min-h-10 items-center justify-center rounded-lg bg-primary-container px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary disabled:cursor-not-allowed disabled:opacity-50">{actionLabels[status] ?? status}</button>)}
                            {canCancel && <button type="button" disabled={form.processing} onClick={() => updateStatus("cancelled")} className="inline-flex min-h-10 items-center justify-center rounded-lg border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 transition-colors hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-400 disabled:cursor-not-allowed disabled:opacity-50">{actionLabels.cancelled}</button>}
                        </div>
                        {form.errors.status && <p className="mt-3 text-sm text-red-600">{form.errors.status}</p>}
                    </section>}
                    {cancelOpen && <section className="mt-5 rounded-xl border border-red-200 bg-red-50 p-5">
                        <h2 className="font-heading text-base font-bold text-red-900">Batalkan Pesanan</h2>
                        <form onSubmit={cancel} className="mt-4 space-y-3">
                            <label htmlFor="cancellation_reason" className="block text-sm font-semibold text-red-900">Alasan pembatalan</label>
                            <textarea id="cancellation_reason" value={form.data.cancellation_reason} onChange={(event) => form.setData("cancellation_reason", event.target.value)} minLength={5} maxLength={1000} required rows={4} className="w-full rounded-lg border border-red-200 bg-white px-3 py-2.5 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-400" />
                            {form.errors.cancellation_reason && <p className="text-sm text-red-600">{form.errors.cancellation_reason}</p>}
                            <div className="flex flex-wrap gap-3"><button type="submit" disabled={form.processing} className="inline-flex min-h-10 items-center justify-center rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-400 disabled:cursor-not-allowed disabled:opacity-50">{form.processing ? "Membatalkan..." : "Batalkan Pesanan"}</button><button type="button" disabled={form.processing} onClick={closeCancel} className="inline-flex min-h-10 items-center justify-center rounded-lg border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-800 transition-colors hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-400 disabled:cursor-not-allowed disabled:opacity-50">Batal</button></div>
                        </form>
                    </section>}
                    <section className="mt-8 overflow-hidden rounded-xl border border-surface-container-high bg-surface shadow-sm">
                        <div className="flex items-center gap-3 border-b border-surface-container-high px-6 py-5">
                            <ClipboardList
                                className="text-primary"
                                size={21}
                                aria-hidden="true"
                            />
                            <h2 className="font-heading text-xl font-bold">
                                Produk Pesanan
                            </h2>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[640px] text-left text-sm">
                                <thead className="bg-surface-container-low text-xs uppercase tracking-wide text-secondary">
                                    <tr>
                                        <th className="px-6 py-4 font-semibold">
                                            Produk
                                        </th>
                                        <th className="px-6 py-4 font-semibold">
                                            Harga
                                        </th>
                                        <th className="px-6 py-4 font-semibold">
                                            Qty
                                        </th>
                                        <th className="px-25 py-4 text-right font-semibold">
                                            Subtotal
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-surface-container-high">
                                    {(group?.items || []).map((item) => (
                                        <tr key={item.id}>
                                            <td className="px-6 py-5 font-semibold">
                                                {item.product_name}
                                            </td>
                                            <td className="px-6 py-5 whitespace-nowrap">
                                                {money(item.price)}
                                            </td>
                                            <td className="px-6 py-5">
                                                {item.quantity}
                                            </td>
                                            <td className="px-25 py-5 text-right font-semibold whitespace-nowrap">
                                                {money(item.subtotal)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                                <tfoot>
                                    <tr className="bg-surface-container-low">
                                        <th
                                            colSpan="3"
                                            className="px-6 py-5 text-right font-heading text-base"
                                        >
                                            Total Produk Anda
                                        </th>
                                        <td className="px-20 py-5 text-right font-heading text-lg font-bold text-primary whitespace-nowrap">
                                            {money(group?.subtotal)}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </section>
                </div>
            </main>
        </VendorLayout>
    );
}

function InfoCard({ title, children }) {
    return (
        <section className="rounded-xl border border-surface-container-high bg-surface p-5 shadow-sm">
            <h2 className="font-heading text-base font-bold">{title}</h2>
            <dl className="mt-4 space-y-3">{children}</dl>
        </section>
    );
}
function Info({ label: title, value }) {
    return (
        <div>
            <dt className="text-xs font-semibold uppercase tracking-wide text-secondary">
                {title}
            </dt>
            <dd className="mt-1 text-sm font-medium">{value}</dd>
        </div>
    );
}
