import { Head, useForm as useInertiaForm, usePage } from '@inertiajs/react';
import { Camera, CircleUserRound, Eye, EyeOff, LoaderCircle, LockKeyhole, Save, Settings2 } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { useForm as useReactHookForm, useWatch } from 'react-hook-form';
import { z } from 'zod';
import VendorLayout from '../../Layouts/VendorLayout';

const emptySettings = { name: '', email: '', phone: '', avatar_url: null };
const tabs = [
    { id: 'account', label: 'Informasi Akun', icon: CircleUserRound },
    { id: 'security', label: 'Keamanan & Kata Sandi', icon: LockKeyhole },
];
const schema = z.object({
    name: z.string().trim().min(1, 'Nama wajib diisi.').max(255, 'Nama maksimal 255 karakter.'),
    email: z.string().trim().min(1, 'Email wajib diisi.').email('Format email tidak valid.').max(255, 'Email maksimal 255 karakter.'),
    phone: z.string().max(50, 'Nomor telepon maksimal 50 karakter.').optional(),
    avatar: z.any().refine((file) => !file || file instanceof File, 'File avatar tidak valid.').refine((file) => !file || ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'].includes(file.type), 'Avatar harus berupa JPEG, JPG, PNG, atau WEBP.').refine((file) => !file || file.size <= 5 * 1024 * 1024, 'Avatar maksimal 5MB.'),
});
const passwordSchema = z.object({
    current_password: z.string().min(1, 'Kata sandi saat ini wajib diisi.'),
    new_password: z.string().min(1, 'Kata sandi baru wajib diisi.').min(8, 'Kata sandi baru minimal 8 karakter.'),
    new_password_confirmation: z.string().min(1, 'Konfirmasi kata sandi baru wajib diisi.'),
}).refine((data) => data.new_password === data.new_password_confirmation, {
    path: ['new_password_confirmation'],
    message: 'Konfirmasi kata sandi baru tidak cocok.',
});

export default function SettingsPage({ settings = emptySettings }) {
    const { props } = usePage();
    const initialValues = { name: settings.name ?? '', email: settings.email ?? '', phone: settings.phone ?? '', avatar: null };
    const [baseline, setBaseline] = useState(() => ({ ...initialValues, avatar_url: settings.avatar_url ?? null }));
    const [preview, setPreview] = useState(baseline.avatar_url);
    const [generalError, setGeneralError] = useState(null);
    const [activeTab, setActiveTab] = useState('account');
    const inertia = useInertiaForm(initialValues);
    const { control, register, handleSubmit, reset, setError, clearErrors, formState: { errors } } = useReactHookForm({ defaultValues: initialValues });
    const [name, email, phone] = useWatch({ control, name: ['name', 'email', 'phone'] });
    const isDirty = name !== baseline.name || email !== baseline.email || phone !== baseline.phone || inertia.data.avatar !== null;
    const serverErrors = inertia.errors;
    const mergedErrors = useMemo(() => ({ ...serverErrors, ...Object.fromEntries(Object.entries(errors).map(([key, value]) => [key, value.message])) }), [errors, serverErrors]);

    useEffect(() => () => revoke(preview), [preview]);

    const chooseAvatar = (event) => {
        const file = event.target.files?.[0] ?? null;
        revoke(preview);
        setPreview(file ? URL.createObjectURL(file) : baseline.avatar_url);
        inertia.setData('avatar', file);
        clearErrors('avatar');
    };

    const submit = (data) => {
        if (!isDirty || inertia.processing) return;

        const result = schema.safeParse({ ...data, phone: data.phone || undefined, avatar: inertia.data.avatar });
        if (!result.success) {
            clearErrors();
            result.error.issues.forEach((issue) => setError(issue.path[0], { type: 'client', message: issue.message }));
            return;
        }

        setGeneralError(null);
        clearErrors();
        inertia.setData({ name: result.data.name, email: result.data.email, phone: result.data.phone ?? '', avatar: result.data.avatar ?? null });
        inertia.transform(() => ({ name: result.data.name, email: result.data.email, phone: result.data.phone ?? '', avatar: result.data.avatar ?? null, _method: 'PUT' }));
        inertia.post('/vendor/settings', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: (page) => {
                const next = page.props.settings ?? emptySettings;
                const values = { name: next.name ?? '', email: next.email ?? '', phone: next.phone ?? '', avatar: null };
                setBaseline({ ...values, avatar_url: next.avatar_url ?? null });
                revoke(preview);
                setPreview(next.avatar_url ?? null);
                reset(values);
                inertia.setData(values);
            },
            onError: (server) => {
                const known = ['name', 'email', 'phone', 'avatar'];
                Object.entries(server).filter(([key]) => known.includes(key)).forEach(([key, message]) => setError(key, { type: 'server', message }));
                setGeneralError(known.some((key) => server[key]) ? null : 'Perubahan belum dapat disimpan. Periksa data lalu coba lagi.');
            },
        });
    };

    const cancel = () => {
        const values = { name: baseline.name, email: baseline.email, phone: baseline.phone, avatar: null };

        revoke(preview);
        clearErrors();
        inertia.clearErrors();
        inertia.reset();
        reset(values);
        inertia.setData(values);
        setPreview(baseline.avatar_url);
        setGeneralError(null);
    };

    return <VendorLayout>
        <Head title="Pengaturan Akun" />
        <main className="min-h-screen bg-background px-5 py-8 text-foreground sm:px-8 lg:px-10">
            <div className="mx-auto max-w-6xl">
                <header className="border-b border-surface-container-high pb-6">
                    <p className="text-sm font-semibold uppercase tracking-[0.18em] text-primary">Vendor Portal / Pengaturan</p>
                    <h1 className="mt-3 font-heading text-3xl font-bold sm:text-4xl">Pengaturan Akun</h1>
                    <p className="mt-2 max-w-2xl text-base leading-7 text-secondary">Kelola informasi akun dan preferensi vendor Anda.</p>
                    <nav className="mt-6 -mb-6 overflow-x-auto" aria-label="Tab pengaturan"><div className="flex min-w-max gap-6">{tabs.map(({ id, label, icon: Icon }) => <button key={id} type="button" onClick={() => setActiveTab(id)} aria-selected={activeTab === id} role="tab" className={`inline-flex min-h-12 items-center gap-2 border-b-2 px-1 text-sm font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary ${activeTab === id ? 'border-primary text-primary' : 'border-transparent text-secondary hover:text-foreground'}`}><Icon size={17} aria-hidden="true" />{label}</button>)}</div></nav>
                </header>
                {props.flash?.success && <p className="mt-5 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700" role="status">{props.flash.success}</p>}
                {activeTab === 'security' ? <SecurityForm onSuccess={() => setActiveTab('security')} /> : <form onSubmit={handleSubmit(submit)} className="mt-7 overflow-hidden rounded-xl border border-surface-container-high bg-surface shadow-sm">
                    <div className="border-b border-surface-container-high p-6 sm:p-8">
                        <div className="flex items-center gap-3"><Settings2 size={22} className="text-primary" aria-hidden="true" /><div><h2 className="font-heading text-xl font-bold">Informasi Akun</h2><p className="mt-1 text-sm text-secondary">Informasi dasar yang digunakan untuk akun vendor Anda.</p></div></div>
                        <div className="mt-8 flex flex-col gap-5 sm:flex-row sm:items-center"><div className="flex size-24 shrink-0 items-center justify-center overflow-hidden rounded-full border-4 border-primary-container/20 bg-primary-container/10 text-primary">{preview ? <img src={preview} alt="Avatar vendor" className="size-full object-cover" /> : <CircleUserRound size={42} aria-hidden="true" />}</div><div><label htmlFor="avatar" className="inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-lg border border-surface-container-high px-4 py-2.5 text-sm font-semibold transition hover:bg-surface-container-low focus-within:ring-2 focus-within:ring-primary"><Camera size={18} aria-hidden="true" />Ubah Avatar<input id="avatar" type="file" accept="image/jpeg,image/jpg,image/png,image/webp" className="sr-only" onChange={chooseAvatar} /></label><p className="mt-2 text-xs text-secondary">JPG, PNG, WEBP. Maksimal 5MB.</p>{mergedErrors.avatar && <p className="mt-1 text-sm text-error" role="alert">{mergedErrors.avatar}</p>}</div></div>
                        <div className="mt-8 grid gap-5 md:grid-cols-2"><Field label="Nama" name="name" required error={mergedErrors.name} register={register} /><Field label="Email" name="email" type="email" required error={mergedErrors.email} register={register} /><Field label="Nomor Telepon" name="phone" error={mergedErrors.phone} register={register} /></div>
                    </div>
                    {isDirty && <div className="flex flex-col-reverse gap-3 bg-surface-container-low p-5 sm:flex-row sm:items-center sm:justify-end sm:p-6"><button type="button" onClick={cancel} disabled={inertia.processing} className="min-h-11 rounded-lg border border-surface-container-high px-5 py-2.5 text-sm font-semibold transition hover:bg-surface focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary disabled:opacity-50">Batal</button><button type="submit" disabled={inertia.processing} className="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-primary-container px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary disabled:cursor-not-allowed disabled:opacity-60">{inertia.processing ? <><LoaderCircle size={18} className="animate-spin" aria-hidden="true" />Menyimpan...</> : <><Save size={18} aria-hidden="true" />Simpan Perubahan</>}</button></div>}
                </form>}
                {activeTab === 'account' && generalError && <p className="mt-5 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-error" role="alert">{generalError}</p>}
            </div>
        </main>
    </VendorLayout>;
}

function Field({ label, name, type = 'text', required = false, error, register }) {
    return <div><label htmlFor={name} className="mb-2 block text-sm font-semibold">{label}{required && <span className="ml-1 text-primary" aria-hidden="true">*</span>}</label><input id={name} type={type} {...register(name)} aria-invalid={Boolean(error)} aria-describedby={error ? `${name}-error` : undefined} className="w-full rounded-lg border border-surface-container-high bg-surface px-3 py-2.5 outline-none transition focus:border-primary-container focus:ring-1 focus:ring-primary-container" />{error && <p id={`${name}-error`} className="mt-1.5 text-sm text-error" role="alert">{error}</p>}</div>;
}

function SecurityForm({ onSuccess }) {
    const passwordInertia = useInertiaForm({ current_password: '', new_password: '', new_password_confirmation: '' });
    const [visible, setVisible] = useState({ current_password: false, new_password: false, new_password_confirmation: false });
    const { register, handleSubmit, reset, setError, clearErrors, formState: { errors } } = useReactHookForm({ defaultValues: passwordInertia.data });
    const fields = [
        ['current_password', 'Kata Sandi Saat Ini', 'current-password'],
        ['new_password', 'Kata Sandi Baru', 'new-password'],
        ['new_password_confirmation', 'Konfirmasi Kata Sandi Baru', 'new-password'],
    ];

    const submitPassword = (data) => {
        if (passwordInertia.processing) return;
        const result = passwordSchema.safeParse(data);
        if (!result.success) {
            clearErrors();
            result.error.issues.forEach((issue) => setError(issue.path[0], { type: 'client', message: issue.message }));
            return;
        }

        clearErrors();
        passwordInertia.setData(result.data);
        passwordInertia.transform(() => result.data);
        passwordInertia.put('/vendor/settings/password', {
            preserveScroll: true,
            onSuccess: () => {
                reset({ current_password: '', new_password: '', new_password_confirmation: '' });
                passwordInertia.reset();
                passwordInertia.clearErrors();
                onSuccess();
            },
            onError: (server) => {
                reset({ current_password: '', new_password: '', new_password_confirmation: '' });
                passwordInertia.reset();
                clearErrors();
                passwordInertia.clearErrors();
                Object.entries(server).forEach(([key, message]) => setError(key, { type: 'server', message }));
            },
        });
    };

    return <form onSubmit={handleSubmit(submitPassword)} className="mt-7 overflow-hidden rounded-xl border border-surface-container-high bg-surface shadow-sm">
        <div className="border-b border-surface-container-high p-6 sm:p-8">
            <div className="flex items-center gap-3"><LockKeyhole size={22} className="text-primary" aria-hidden="true" /><div><h2 className="font-heading text-xl font-bold">Keamanan & Kata Sandi</h2><p className="mt-1 text-sm text-secondary">Perbarui kata sandi untuk menjaga keamanan akun vendor Anda.</p></div></div>
            <div className="mt-8 grid gap-5">
                {fields.map(([name, label, autoComplete]) => <PasswordField key={name} label={label} name={name} autoComplete={autoComplete} visible={visible[name]} onToggle={() => setVisible((state) => ({ ...state, [name]: !state[name] }))} error={errors[name]?.message || passwordInertia.errors[name]} register={register} disabled={passwordInertia.processing} />)}
            </div>
        </div>
        <div className="flex justify-end bg-surface-container-low p-5 sm:p-6"><button type="submit" disabled={passwordInertia.processing} className="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-primary-container px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary disabled:cursor-not-allowed disabled:opacity-60">{passwordInertia.processing ? <><LoaderCircle size={18} className="animate-spin" aria-hidden="true" />Menyimpan...</> : <><Save size={18} aria-hidden="true" />Simpan Kata Sandi</>}</button></div>
    </form>;
}

function PasswordField({ label, name, autoComplete, visible, onToggle, error, register, disabled }) {
    return <div><label htmlFor={name} className="mb-2 block text-sm font-semibold">{label}<span className="ml-1 text-primary" aria-hidden="true">*</span></label><div className="relative"><input id={name} type={visible ? 'text' : 'password'} autoComplete={autoComplete} disabled={disabled} {...register(name)} aria-invalid={Boolean(error)} aria-describedby={error ? `${name}-error` : undefined} className="w-full rounded-lg border border-surface-container-high bg-surface px-3 py-2.5 pr-12 outline-none transition focus:border-primary-container focus:ring-1 focus:ring-primary-container disabled:opacity-60" /><button type="button" onClick={onToggle} aria-label={visible ? `Sembunyikan ${label.toLowerCase()}` : `Tampilkan ${label.toLowerCase()}`} className="absolute inset-y-0 right-0 px-3 text-secondary hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary" tabIndex={disabled ? -1 : 0}>{visible ? <EyeOff size={18} aria-hidden="true" /> : <Eye size={18} aria-hidden="true" />}</button></div>{error && <p id={`${name}-error`} className="mt-1.5 text-sm text-error" role="alert">{error}</p>}</div>;
}

function revoke(url) {
    if (url?.startsWith('blob:')) URL.revokeObjectURL(url);
}