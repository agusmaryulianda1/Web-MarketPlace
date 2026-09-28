import { Head, useForm, usePage } from '@inertiajs/react';
import { Camera, Image, Pencil, Save, Store as StoreIcon, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import VendorLayout from '../../Layouts/VendorLayout';

const fields = (store) => ({ name: store?.name ?? '', slug: store?.slug ?? '', description: store?.description ?? '', phone: store?.phone ?? '', logo: null, banner: null });

export default function StorePage({ store = null }) {
    const isCreate = !store;
    const profile = useForm(fields(store));
    const status = useForm({ is_open: Boolean(store?.is_open) });
    const [editMode, setEditMode] = useState(isCreate);
    const [logoPreview, setLogoPreview] = useState(store?.logo_url ?? null);
    const [bannerPreview, setBannerPreview] = useState(store?.banner_url ?? null);
    const { flash = {} } = usePage().props;

    useEffect(() => () => [logoPreview, bannerPreview].filter((preview) => preview?.startsWith('blob:')).forEach((preview) => URL.revokeObjectURL(preview)), [logoPreview, bannerPreview]);

    const resetProfile = () => {
        if (logoPreview?.startsWith('blob:')) URL.revokeObjectURL(logoPreview);
        if (bannerPreview?.startsWith('blob:')) URL.revokeObjectURL(bannerPreview);
        profile.setData(fields(store));
        profile.clearErrors();
        setLogoPreview(store?.logo_url ?? null);
        setBannerPreview(store?.banner_url ?? null);
    };

    const chooseImage = (event, type) => {
        const file = event.target.files[0] ?? null;
        const preview = type === 'logo' ? logoPreview : bannerPreview;
        if (preview?.startsWith('blob:')) URL.revokeObjectURL(preview);
        const nextPreview = file ? URL.createObjectURL(file) : (type === 'logo' ? store?.logo_url : store?.banner_url) ?? null;
        type === 'logo' ? setLogoPreview(nextPreview) : setBannerPreview(nextPreview);
        profile.setData(type, file);
    };

    const submitProfile = (event) => {
        event.preventDefault();
        const options = { forceFormData: true, onSuccess: (page) => { if (logoPreview?.startsWith('blob:')) URL.revokeObjectURL(logoPreview); if (bannerPreview?.startsWith('blob:')) URL.revokeObjectURL(bannerPreview); setLogoPreview(page.props.store?.logo_url ?? null); setBannerPreview(page.props.store?.banner_url ?? null); setEditMode(false); } };
        if (isCreate) {
            profile.post('/vendor/store', options);
            return;
        }

        profile.transform((data) => ({ ...data, _method: 'PUT' }));
        profile.post('/vendor/store', options);
    };

    const cancelEdit = () => { resetProfile(); setEditMode(false); };
    const updateStatus = () => { status.setData('is_open', !status.data.is_open); status.patch('/vendor/store/status', { preserveScroll: true }); };

    return <VendorLayout>
        <Head title={isCreate ? 'Buat Toko' : 'Profil & Identitas Toko'} />
        <main className="min-h-screen bg-background px-5 py-8 text-foreground sm:px-8 lg:px-10"><div className="mx-auto max-w-6xl">
            <header className="flex flex-col gap-5 border-b border-surface-container-high pb-7 sm:flex-row sm:items-end sm:justify-between"><div><p className="text-sm font-semibold uppercase tracking-[0.18em] text-primary">Vendor Portal / Toko</p><h1 className="mt-3 font-heading text-3xl font-bold sm:text-4xl">{isCreate ? 'Buat Toko' : 'Profil & Identitas Toko'}</h1><p className="mt-2 max-w-2xl text-base leading-7 text-secondary">Kelola informasi etalase toko Anda.</p></div>{!isCreate && <div className="flex flex-wrap items-center gap-3"><StatusBadge isOpen={status.data.is_open} />{!editMode && <button type="button" onClick={() => setEditMode(true)} className="inline-flex min-h-11 items-center gap-2 rounded-lg bg-primary-container px-4 py-2.5 font-semibold text-white hover:bg-primary focus-visible:ring-2 focus-visible:ring-primary"><Pencil size={18} aria-hidden="true" />Edit Informasi Toko</button>}</div>}</header>
            {flash.success && <p className="mt-5 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700" role="status">{flash.success}</p>}{(profile.errors.store || status.errors.is_open) && <p className="mt-5 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-error" role="alert">{profile.errors.store || status.errors.is_open}</p>}
            {isCreate || editMode ? <EditForm {...{ profile, logoPreview, bannerPreview, chooseImage, submitProfile, cancelEdit, isCreate }} /> : <ViewProfile {...{ store, logoPreview, bannerPreview, status, updateStatus }} />}
        </div></main>
    </VendorLayout>;
}

function ViewProfile({ store, logoPreview, bannerPreview, status, updateStatus }) { return <div className="mt-8 space-y-6"><section className="overflow-hidden rounded-xl border border-surface-container-high bg-surface shadow-sm"><div className="relative h-48 bg-surface-container-low sm:h-64">{bannerPreview ? <img src={bannerPreview} alt={`Banner ${store.name}`} className="size-full object-cover" /> : <div className="flex size-full flex-col items-center justify-center gap-2 text-secondary"><Image size={42} aria-hidden="true" /><span>Banner toko belum tersedia</span></div>}<div className="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent" /></div><div className="relative -mt-12 flex flex-col gap-5 px-6 pb-6 sm:px-8 md:flex-row md:items-end"><div className="flex min-w-0 flex-col items-start gap-4 sm:flex-row sm:items-end"><div className="rounded-2xl border border-surface-container-high bg-surface p-1.5 shadow-md"><div className="flex size-24 items-center justify-center overflow-hidden rounded-xl bg-primary-container/10 text-primary sm:size-28">{logoPreview ? <img src={logoPreview} alt={`Logo ${store.name}`} className="size-full object-cover" /> : <StoreIcon size={44} aria-hidden="true" />}</div></div><div className="min-w-0 pb-1"><h2 className="truncate text-2xl font-bold">{store.name}</h2><p className="mt-1 truncate text-sm text-secondary">marketplace.com/{store.slug}</p></div></div></div></section><section className="grid gap-6 md:grid-cols-2"><InfoCard label="Deskripsi Toko" value={store.description || 'Belum ada deskripsi toko.'} /><InfoCard label="Nomor Telepon" value={store.phone || 'Belum ada nomor telepon.'} /></section><StatusSection isOpen={status.data.is_open} processing={status.processing} onToggle={updateStatus} /></div>; }

function EditForm({ profile, logoPreview, bannerPreview, chooseImage, submitProfile, cancelEdit, isCreate }) { return <form id="store-profile" onSubmit={submitProfile} className="mt-8 space-y-6"><section className="overflow-hidden rounded-xl border border-surface-container-high bg-surface shadow-sm"><div className="relative h-48 bg-surface-container-low sm:h-64">{bannerPreview ? <img src={bannerPreview} alt="Preview banner toko" className="size-full object-cover" /> : <div className="flex size-full items-center justify-center text-secondary"><Image size={42} aria-hidden="true" /></div>}<div className="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent" /><ImagePicker id="banner" label="Ubah Banner Toko" icon={<Camera size={17} aria-hidden="true" />} onChange={(event) => chooseImage(event, 'banner')} /></div><div className="relative -mt-12 px-6 pb-6 sm:px-8"><div className="w-fit rounded-2xl border border-surface-container-high bg-surface p-1.5 shadow-md"><div className="relative flex size-24 items-center justify-center overflow-hidden rounded-xl bg-primary-container/10 text-primary sm:size-28">{logoPreview ? <img src={logoPreview} alt="Preview logo toko" className="size-full object-cover" /> : <StoreIcon size={44} aria-hidden="true" />}<ImagePicker id="logo" label="Ganti foto profil toko" icon={<Camera size={22} aria-hidden="true" />} onChange={(event) => chooseImage(event, 'logo')} /></div></div></div></section><section className="rounded-xl border border-surface-container-high bg-surface p-6 shadow-sm sm:p-8"><div className="mb-6 border-b border-surface-container-high pb-4"><h2 className="font-heading text-xl font-bold">Informasi Dasar Toko</h2><p className="mt-1 text-sm text-secondary">Informasi yang dilihat pelanggan di etalase Anda.</p></div><div className="grid gap-5 md:grid-cols-2"><Field label="Nama Toko" name="name" value={profile.data.name} onChange={(event) => profile.setData('name', event.target.value)} error={profile.errors.name} required /><Field label="Slug / URL Toko" name="slug" value={profile.data.slug} onChange={(event) => profile.setData('slug', event.target.value)} error={profile.errors.slug} required /><div className="md:col-span-2"><Field label="Deskripsi Toko" name="description" type="textarea" value={profile.data.description} onChange={(event) => profile.setData('description', event.target.value)} error={profile.errors.description} /></div><Field label="Nomor Telepon" name="phone" value={profile.data.phone} onChange={(event) => profile.setData('phone', event.target.value)} error={profile.errors.phone} /></div></section><div className="relative z-10 flex flex-col-reverse justify-end gap-3 sm:flex-row">{!isCreate && <button type="button" onClick={cancelEdit} className="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-surface-container-high px-5 py-2.5 font-semibold"><X size={18} aria-hidden="true" />Batal</button>}<button form="store-profile" type="submit" disabled={profile.processing} className="pointer-events-auto inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-primary-container px-5 py-2.5 font-semibold text-white hover:bg-primary disabled:opacity-60"><Save size={18} aria-hidden="true" />{profile.processing ? 'Menyimpan...' : isCreate ? 'Buat Toko' : 'Simpan Perubahan'}</button></div></form>; }

function ImagePicker({ id, label, icon, onChange }) { return <label htmlFor={id} className="absolute right-4 top-4 inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-lg bg-surface/90 px-3.5 py-2 font-semibold shadow transition hover:bg-surface">{icon}<span className={id === 'logo' ? 'sr-only' : ''}>{label}</span><input id={id} type="file" accept="image/jpeg,image/png,image/webp" className="sr-only" onChange={onChange} /></label>; }
function InfoCard({ label, value }) { return <div className="rounded-xl border border-surface-container-high bg-surface p-6 shadow-sm"><p className="text-sm font-semibold text-secondary">{label}</p><p className="mt-3 whitespace-pre-line leading-7">{value}</p></div>; }
function StatusSection({ isOpen, processing, onToggle }) { return <section className="rounded-xl border border-surface-container-high bg-surface p-6 shadow-sm sm:p-8"><div className="flex flex-wrap items-center justify-between gap-4"><div><h2 className="font-heading text-xl font-bold">Status Operasional</h2><p className="mt-1 text-sm text-secondary">Status buka/tutup diatur secara manual.</p></div><button type="button" role="switch" aria-checked={isOpen} disabled={processing} onClick={onToggle} className={`inline-flex min-h-11 items-center gap-2 rounded-full px-5 py-2.5 font-semibold text-white focus-visible:ring-2 focus-visible:ring-primary disabled:opacity-60 ${isOpen ? 'bg-primary-container' : 'bg-secondary'}`}><span className="size-2 rounded-full bg-white" />{processing ? 'Memproses...' : isOpen ? 'Toko Online' : 'Toko Tutup'}</button></div></section>; }
function StatusBadge({ isOpen }) { return <span className={`rounded-full px-3 py-1.5 text-xs font-semibold ${isOpen ? 'bg-green-100 text-green-700' : 'bg-surface-container-high text-secondary'}`}>{isOpen ? 'Toko Online' : 'Toko Tutup'}</span>; }
function Field({ label, name, error, type = 'text', required, ...props }) { const common = { id: name, name, 'aria-invalid': Boolean(error), 'aria-describedby': error ? `${name}-error` : undefined, ...props }; return <div><label htmlFor={name} className="mb-2 block text-sm font-semibold">{label}{required && <span className="ml-1 text-primary">*</span>}</label>{type === 'textarea' ? <textarea {...common} rows="5" className="w-full rounded-lg border border-surface-container-high bg-surface px-3 py-2.5 outline-none focus:border-primary-container focus:ring-1 focus:ring-primary-container" /> : <input {...common} type={type} className="w-full rounded-lg border border-surface-container-high bg-surface px-3 py-2.5 outline-none focus:border-primary-container focus:ring-1 focus:ring-primary-container" />}{error && <p id={`${name}-error`} className="mt-1.5 text-sm text-error" role="alert">{error}</p>}</div>; }