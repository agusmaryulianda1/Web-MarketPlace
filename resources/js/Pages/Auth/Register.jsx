import { useEffect, useState } from 'react';
import { Link, useForm as useInertiaForm } from '@inertiajs/react';
import { ArrowRight, Check, CheckCircle2, Circle, Eye, EyeOff, KeyRound, LoaderCircle, Mail, Phone, Store, UserRound } from 'lucide-react';
import { useForm, useWatch } from 'react-hook-form';
import { z } from 'zod';
import { Button } from '@/Components/UI/Button';
import AuthLayout from '@/Layouts/AuthLayout';

const schema = z.object({
    name: z.string().trim().min(1, 'Nama wajib diisi.'),
    email: z.string().min(1, 'Email wajib diisi.').email('Masukkan alamat email yang valid.'),
    phone: z.string().trim().min(1, 'Nomor HP / WhatsApp wajib diisi.'),
    role: z.enum(['buyer', 'vendor'], { message: 'Pilih jenis akun.' }),
    password: z.string().min(1, 'Kata sandi wajib diisi.'),
    password_confirmation: z.string().min(1, 'Konfirmasi kata sandi wajib diisi.'),
}).refine((data) => data.password === data.password_confirmation, { path: ['password_confirmation'], message: 'Konfirmasi kata sandi harus sama.' });

const requirements = [
    ['length', 'Minimal 8 karakter', (value) => value.length >= 8],
    ['upper', 'Huruf besar', (value) => /[A-Z]/.test(value)],
    ['lower', 'Huruf kecil', (value) => /[a-z]/.test(value)],
    ['number', 'Angka', (value) => /\d/.test(value)],
    ['symbol', 'Karakter khusus', (value) => /[^A-Za-z0-9]/.test(value)],
];

function FieldError({ children, id }) {
    return children ? <p id={id} className="mt-1 text-sm text-error" role="alert">{children}</p> : null;
}

export default function Register() {
    const [showPassword, setShowPassword] = useState(false);
    const [showConfirmation, setShowConfirmation] = useState(false);
    const inertia = useInertiaForm({ name: '', email: '', phone: '', role: 'buyer', password: '', password_confirmation: '' });
    const { register, control, handleSubmit, setError, formState: { errors } } = useForm({ defaultValues: inertia.data });
    const password = useWatch({ control, name: 'password', defaultValue: '' });
    const confirmation = useWatch({ control, name: 'password_confirmation', defaultValue: '' });
    const role = useWatch({ control, name: 'role', defaultValue: 'buyer' });
    const checks = requirements.map(([, , test]) => test(password));
    const strength = checks.filter(Boolean).length;
    const strengthLabel = !password ? 'Belum diisi' : strength <= 2 ? 'Lemah' : strength <= 4 ? 'Sedang' : 'Kuat';
    const strengthColor = !password ? 'bg-surface-container-high' : strength <= 2 ? 'bg-error' : strength <= 4 ? 'bg-yellow-500' : 'bg-green-600';

    useEffect(() => {
        Object.entries(inertia.errors).forEach(([field, message]) => setError(field, { type: 'server', message }));
    }, [inertia.errors, setError]);

    const submit = (data) => {
        const result = schema.safeParse(data);
        if (!result.success) {
            result.error.issues.forEach((issue) => setError(issue.path[0], { type: 'client', message: issue.message }));
            return;
        }
        inertia.post('/register', data, { preserveScroll: true });
    };

    return (
        <AuthLayout alternateHref="/login" alternateText="Sudah punya akun?" alternateLabel="Masuk">
            <section className="w-full max-w-[700px] rounded-xl border border-surface-container-high bg-surface p-6 shadow-sm sm:p-10" aria-labelledby="register-title">
                <div className="text-center"><span className="inline-flex items-center gap-2 rounded-full border border-surface-container-high bg-surface-container-low px-3 py-1 text-sm text-secondary"><CheckCircle2 size={16} className="text-emerald-600" aria-hidden="true" />Registrasi Terverifikasi &amp; Aman</span><h1 id="register-title" className="mt-5 font-heading text-2xl font-bold sm:text-[30px]">Daftar Akun Baru</h1><p className="mx-auto mt-2 max-w-[500px] text-base leading-6 text-secondary">Bergabung dengan ribuan pembeli dan merchant terpercaya di seluruh Indonesia.</p></div>
                <form className="mt-8 space-y-5" onSubmit={handleSubmit(submit)} noValidate>
                    <fieldset><legend className="mb-2.5 block font-medium">Pilih Jenis Akun <span className="text-primary-container">*</span></legend><div className="grid gap-3 sm:grid-cols-2">{[['buyer', 'Pembeli', 'Belanja produk dari vendor resmi.', UserRound], ['vendor', 'Penjual / Vendor', 'Jual produk melalui platform.', Store]].map(([value, label, description, Icon]) => <label key={value} className={`cursor-pointer rounded-xl border-2 p-4 transition ${role === value ? 'border-primary-container bg-orange-50' : 'border-surface-container-high bg-white hover:border-primary-container/60'}`}><input type="radio" value={value} {...register('role')} className="sr-only" /><span className="flex items-center justify-between"><span className="flex size-9 items-center justify-center rounded-lg border border-surface-container-high bg-white text-primary-container"><Icon size={19} aria-hidden="true" /></span>{role === value && <CheckCircle2 size={20} className="text-primary-container" aria-hidden="true" />}</span><span className="mt-2 block font-semibold">{label}</span><span className="mt-1 block text-sm text-secondary">{description}</span></label>)}</div><FieldError id="role-error">{errors.role?.message}</FieldError></fieldset>
                    <div><label htmlFor="name" className="mb-1.5 block font-medium">Nama Lengkap</label><div className="relative"><UserRound className="absolute left-3.5 top-1/2 -translate-y-1/2 text-secondary" size={19} aria-hidden="true" /><input id="name" autoComplete="name" placeholder="cth. Budi Pratama" {...register('name')} className="w-full rounded-lg border border-surface-container-high bg-white py-3 pl-11 pr-4 outline-none focus:border-primary-container focus:ring-1 focus:ring-primary-container" aria-invalid={Boolean(errors.name)} aria-describedby="name-error" /></div><FieldError id="name-error">{errors.name?.message}</FieldError></div>
                    <div><label htmlFor="email" className="mb-1.5 block font-medium">Alamat Email</label><div className="relative"><Mail className="absolute left-3.5 top-1/2 -translate-y-1/2 text-secondary" size={19} aria-hidden="true" /><input id="email" type="email" autoComplete="email" placeholder="nama@email.com" {...register('email')} className="w-full rounded-lg border border-surface-container-high bg-white py-3 pl-11 pr-4 outline-none focus:border-primary-container focus:ring-1 focus:ring-primary-container" aria-invalid={Boolean(errors.email)} aria-describedby="email-error" /></div><FieldError id="email-error">{errors.email?.message}</FieldError></div>
                    <div><label htmlFor="phone" className="mb-1.5 block font-medium">Nomor HP / WhatsApp</label><div className="relative"><Phone className="absolute left-3.5 top-1/2 -translate-y-1/2 text-secondary" size={19} aria-hidden="true" /><input id="phone" type="tel" autoComplete="tel" placeholder="08xxxxxxxxxx" {...register('phone')} className="w-full rounded-lg border border-surface-container-high bg-white py-3 pl-11 pr-4 outline-none focus:border-primary-container focus:ring-1 focus:ring-primary-container" aria-invalid={Boolean(errors.phone)} aria-describedby="phone-error" /></div><FieldError id="phone-error">{errors.phone?.message}</FieldError></div>
                    <div><label htmlFor="password" className="mb-1.5 block font-medium">Kata Sandi</label><div className="relative"><KeyRound className="absolute left-3.5 top-1/2 -translate-y-1/2 text-secondary" size={19} aria-hidden="true" /><input id="password" type={showPassword ? 'text' : 'password'} autoComplete="new-password" placeholder="Masukkan kata sandi aman" {...register('password')} className="w-full rounded-lg border border-surface-container-high bg-white py-3 pl-11 pr-12 outline-none focus:border-primary-container focus:ring-1 focus:ring-primary-container" aria-invalid={Boolean(errors.password)} aria-describedby="password-feedback" /><button type="button" onClick={() => setShowPassword((value) => !value)} className="absolute right-3.5 top-1/2 -translate-y-1/2 text-secondary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary" aria-label={showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}>{showPassword ? <EyeOff size={19} /> : <Eye size={19} />}</button></div><div id="password-feedback" className="mt-2 rounded-lg border border-surface-container-high bg-surface-container-low p-3"><div className="flex items-center justify-between text-sm"><span>Kekuatan password</span><span className="font-semibold">{strengthLabel}</span></div><div className="mt-2 flex gap-1" aria-label={`Kekuatan password: ${strengthLabel}`}>{[1, 2, 3, 4, 5].map((item) => <span key={item} className={`h-1.5 flex-1 rounded-full ${item <= strength ? strengthColor : 'bg-surface-container-high'}`} />)}</div><div className="mt-3 grid gap-2 text-sm sm:grid-cols-2">{requirements.map(([key, label], index) => <span key={key} className={`flex items-center gap-1.5 ${checks[index] ? 'text-green-700' : 'text-secondary'}`}>{checks[index] ? <Check size={15} aria-hidden="true" /> : <Circle size={15} aria-hidden="true" />}{label}</span>)}</div></div><FieldError>{errors.password?.message}</FieldError></div>
                    <div><label htmlFor="password_confirmation" className="mb-1.5 block font-medium">Konfirmasi Kata Sandi</label><div className="relative"><KeyRound className="absolute left-3.5 top-1/2 -translate-y-1/2 text-secondary" size={19} aria-hidden="true" /><input id="password_confirmation" type={showConfirmation ? 'text' : 'password'} autoComplete="new-password" placeholder="Ketik ulang kata sandi Anda" {...register('password_confirmation')} className="w-full rounded-lg border border-surface-container-high bg-white py-3 pl-11 pr-12 outline-none focus:border-primary-container focus:ring-1 focus:ring-primary-container" aria-invalid={Boolean(errors.password_confirmation)} aria-describedby="confirmation-error" /><button type="button" onClick={() => setShowConfirmation((value) => !value)} className="absolute right-3.5 top-1/2 -translate-y-1/2 text-secondary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary" aria-label={showConfirmation ? 'Sembunyikan konfirmasi kata sandi' : 'Tampilkan konfirmasi kata sandi'}>{showConfirmation ? <EyeOff size={19} /> : <Eye size={19} />}</button></div>{confirmation && <p className={`mt-1 text-sm ${password === confirmation ? 'text-green-700' : 'text-error'}`} role="status">{password === confirmation ? 'Konfirmasi kata sandi cocok.' : 'Konfirmasi kata sandi belum sama.'}</p>}<FieldError id="confirmation-error">{errors.password_confirmation?.message}</FieldError></div>
                    {Object.keys(inertia.errors).length > 0 && <p className="rounded-lg bg-red-50 p-3 text-sm text-error" role="alert">Periksa kembali data pendaftaran Anda.</p>}<Button type="submit" disabled={inertia.processing} className="h-14 w-full gap-2 bg-primary-container text-base font-bold hover:bg-orange-600">{inertia.processing ? <LoaderCircle className="animate-spin" size={19} aria-hidden="true" /> : <ArrowRight size={20} aria-hidden="true" />}{inertia.processing ? 'Memproses...' : 'Daftar Sekarang'}</Button>
                </form><div className="mt-5 text-center text-sm text-secondary sm:hidden">Sudah punya akun? <Link href="/login" className="font-semibold text-primary">Masuk</Link></div>
            </section>
        </AuthLayout>
    );
}