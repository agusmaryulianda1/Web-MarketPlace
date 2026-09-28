import { cn } from '@/Lib/utils';

// Shared status presentation for Buyer, Vendor, and Admin order views.
const PILL = 'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold whitespace-nowrap';

const ORDER_STATUS_STYLES = {
    pending: { label: 'Menunggu Diproses', style: 'border-orange-200 bg-orange-50 text-orange-800', dot: 'bg-orange-500' },
    processing: { label: 'Sedang Diproses', style: 'border-blue-200 bg-blue-50 text-blue-800', dot: 'bg-blue-500' },
    shipped: { label: 'Dikirim', style: 'border-violet-200 bg-violet-50 text-violet-800', dot: 'bg-violet-500' },
    completed: { label: 'Selesai', style: 'border-emerald-200 bg-emerald-50 text-emerald-800', dot: 'bg-emerald-500' },
    cancelled: { label: 'Dibatalkan', style: 'border-red-200 bg-red-50 text-red-800', dot: 'bg-red-500' },
    partially_cancelled: { label: 'Sebagian Dibatalkan', style: 'border-amber-200 bg-amber-50 text-amber-800', dot: 'bg-amber-500' },
};

const PAYMENT_STATUS_STYLES = {
    pending: { label: 'Menunggu Pembayaran', style: 'border-amber-200 bg-amber-50 text-amber-800', dot: 'bg-amber-500' },
    paid: { label: 'Sudah Dibayar', style: 'border-emerald-200 bg-emerald-50 text-emerald-800', dot: 'bg-emerald-500' },
    failed: { label: 'Gagal', style: 'border-red-200 bg-red-50 text-red-800', dot: 'bg-red-500' },
};

const UNKNOWN_STYLE = {
    style: 'border-surface-container-high bg-surface-container-low text-secondary',
    dot: 'bg-secondary',
};

function StatusPill({ status, styles, className }) {
    const item = styles[status] ?? UNKNOWN_STYLE;
    return (
        <span className={cn(PILL, item.style, className)}>
            <span className={cn('size-1.5 shrink-0 rounded-full', item.dot)} aria-hidden="true" />
            {item.label ?? status ?? '-'}
        </span>
    );
}

export function OrderStatusBadge({ status, className }) {
    return <StatusPill status={status} styles={ORDER_STATUS_STYLES} className={className} />;
}

export function PaymentStatusBadge({ status, className }) {
    return <StatusPill status={status} styles={PAYMENT_STATUS_STYLES} className={className} />;
}
