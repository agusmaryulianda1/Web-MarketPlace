import { useEffect, useState } from "react";
import { Link, useForm as useInertiaForm } from "@inertiajs/react";
import {
    ArrowRight,
    Eye,
    EyeOff,
    KeyRound,
    LoaderCircle,
    Mail,
    ShieldCheck,
} from "lucide-react";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { Button } from "@/Components/UI/Button";
import AuthLayout from "@/Layouts/AuthLayout";

const schema = z.object({
    email: z
        .string()
        .min(1, "Email wajib diisi.")
        .email("Masukkan alamat email yang valid."),
    password: z.string().min(1, "Kata sandi wajib diisi."),
});

function FieldError({ children, id }) {
    return children ? (
        <p id={id} className="mt-1 text-sm text-error" role="alert">
            {children}
        </p>
    ) : null;
}

function GoogleLogo() {
    return (
        <svg viewBox="0 0 24 24" className="size-4" aria-hidden="true">
            <path
                d="M12 5c1.54 0 2.92.54 4.02 1.43l3.01-3.01C17.21 1.7 14.77 1 12 1 7.42 1 3.53 3.61 1.67 7.41l3.66 2.84C6.22 7.28 8.87 5 12 5z"
                fill="#EA4335"
            />
            <path
                d="M23.49 12.27c0-.79-.07-1.54-.19-2.27H12v4.51h6.47c-.29 1.48-1.14 2.73-2.4 3.58l3.7 2.87c2.16-2 3.72-4.94 3.72-8.69z"
                fill="#4285F4"
            />
            <path
                d="M5.33 14.75c-.24-.71-.38-1.47-.38-2.25s.14-1.54.38-2.25L1.67 7.41C.61 9.53 0 11.9 0 14.5s.61 4.97 1.67 7.09l3.66-2.84z"
                fill="#FBBC05"
            />
            <path
                d="M12 23.5c3.24 0 5.95-1.08 7.93-2.91l-3.7-2.87c-1.08.72-2.45 1.16-4.23 1.16-3.13 0-5.78-2.28-6.67-5.25L1.67 16.47C3.53 20.27 7.42 23.5 12 23.5z"
                fill="#34A853"
            />
        </svg>
    );
}

function AppleLogo() {
    return (
        <svg
            viewBox="0 0 24 24"
            className="size-4 fill-current"
            aria-hidden="true"
        >
            <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.84c.65-.79 1.1-1.89.98-2.99-.95.04-2.1.63-2.77 1.42-.59.68-1.11 1.79-.97 2.86 1.06.08 2.14-.53 2.76-1.29z" />
        </svg>
    );
}

export default function Login() {
    const [showPassword, setShowPassword] = useState(false);
    const inertia = useInertiaForm({
        email: "",
        password: "",
    });

    const {
        register,
        handleSubmit,
        setError,
        formState: { errors },
    } = useForm({
        defaultValues: {
            email: "",
            password: "",
        },
    });

    useEffect(() => {
        Object.entries(inertia.errors).forEach(([field, message]) => {
            setError(field, { type: "server", message });
        });
    }, [inertia.errors, setError]);

    const submit = (data) => {
        const result = schema.safeParse(data);

        if (!result.success) {
            result.error.issues.forEach((issue) =>
                setError(issue.path[0], {
                    type: "client",
                    message: issue.message,
                }),
            );
            return;
        }

        inertia.transform(() => data);
        inertia.post("/login", { preserveScroll: true });
    };

    return (
        <AuthLayout
            alternateHref="/register"
            alternateText="Belum punya akun?"
            alternateLabel="Daftar Sekarang"
        >
            <section
                className="w-full max-w-[600px] rounded-xl border border-surface-container-high bg-surface p-6 shadow-sm sm:p-10 lg:p-10"
                aria-labelledby="login-title"
            >
                <div className="text-center">
                    <span className="mx-auto flex size-14 items-center justify-center rounded-full bg-orange-100 text-primary">
                        <KeyRound size={25} aria-hidden="true" />
                    </span>
                    <h1
                        id="login-title"
                        className="mt-5 font-heading text-2xl font-bold sm:text-[28px]"
                    >
                        Masuk ke Akun Anda
                    </h1>
                    <p className="mx-auto mt-3 max-w-[470px] text-base leading-7 text-secondary">
                        Selamat datang kembali! Masuk untuk mulai berbelanja
                        atau kelola toko Anda.
                    </p>
                </div>

                <form
                    className="mt-8 space-y-5"
                    onSubmit={handleSubmit(submit)}
                    noValidate
                >
                    {inertia.errors.email && !errors.email && (
                        <p
                            className="rounded-lg bg-red-50 p-3 text-sm text-error"
                            role="alert"
                        >
                            {inertia.errors.email}
                        </p>
                    )}
                    <div>
                        <label
                            htmlFor="email"
                            className="mb-1.5 block font-medium"
                        >
                            Email
                        </label>
                        <div className="relative">
                            <Mail
                                className="absolute left-3.5 top-1/2 -translate-y-1/2 text-secondary"
                                size={19}
                                aria-hidden="true"
                            />
                            <input
                                id="email"
                                type="email"
                                autoComplete="email"
                                placeholder="nama@email.com"
                                {...register("email")}
                                className="w-full rounded-lg border border-surface-container-high bg-white py-3 pl-11 pr-4 outline-none transition focus:border-primary-container focus:ring-1 focus:ring-primary-container"
                                aria-invalid={Boolean(errors.email)}
                                aria-describedby="email-error"
                            />
                        </div>
                        <FieldError id="email-error">
                            {errors.email?.message}
                        </FieldError>
                    </div>

                    <div>
                        <label
                            htmlFor="password"
                            className="mb-1.5 block font-medium"
                        >
                            Kata Sandi
                        </label>
                        <div className="relative">
                            <KeyRound
                                className="absolute left-3.5 top-1/2 -translate-y-1/2 text-secondary"
                                size={19}
                                aria-hidden="true"
                            />
                            <input
                                id="password"
                                type={showPassword ? "text" : "password"}
                                autoComplete="current-password"
                                placeholder="Masukkan kata sandi akun"
                                {...register("password")}
                                className="w-full rounded-lg border border-surface-container-high bg-white py-3 pl-11 pr-12 outline-none transition focus:border-primary-container focus:ring-1 focus:ring-primary-container"
                                aria-invalid={Boolean(errors.password)}
                            />
                            <button
                                type="button"
                                onClick={() =>
                                    setShowPassword((value) => !value)
                                }
                                className="absolute right-3.5 top-1/2 -translate-y-1/2 text-secondary hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                                aria-label={
                                    showPassword
                                        ? "Sembunyikan kata sandi"
                                        : "Tampilkan kata sandi"
                                }
                            >
                                {showPassword ? (
                                    <EyeOff size={19} />
                                ) : (
                                    <Eye size={19} />
                                )}
                            </button>
                        </div>
                        <FieldError>{errors.password?.message}</FieldError>
                    </div>

                    <Button
                        type="submit"
                        disabled={inertia.processing}
                        className="h-14 w-full gap-2 bg-primary-container text-base font-bold hover:bg-orange-600"
                    >
                        {inertia.processing ? (
                            <LoaderCircle
                                className="animate-spin"
                                size={19}
                                aria-hidden="true"
                            />
                        ) : (
                            <ArrowRight size={20} aria-hidden="true" />
                        )}
                        {inertia.processing ? "Memproses..." : "Masuk ke Akun"}
                    </Button>
                </form>

                <div className="mt-8 flex items-center gap-3 text-sm uppercase tracking-wide text-secondary">
                    <span className="h-px flex-1 bg-surface-container-high" />
                    atau masuk dengan
                    <span className="h-px flex-1 bg-surface-container-high" />
                </div>
                <div className="mt-4 grid grid-cols-2 gap-3">
                    <button
                        type="button"
                        disabled
                        className="flex items-center justify-center gap-2 rounded-lg border border-surface-container-high px-3 py-3 text-sm text-secondary disabled:cursor-not-allowed"
                    >
                        <GoogleLogo />
                        Google
                    </button>
                    <button
                        type="button"
                        disabled
                        className="flex items-center justify-center gap-2 rounded-lg border border-surface-container-high px-3 py-3 text-sm text-secondary disabled:cursor-not-allowed"
                    >
                        <AppleLogo />
                        Apple
                    </button>
                </div>
                <div className="mt-6 flex items-center justify-center gap-2 border-t border-surface-container-high pt-5 text-sm text-secondary">
                    <ShieldCheck size={17} aria-hidden="true" />
                    Dilindungi enkripsi data 256-bit standar industri.
                </div>
                <p className="mt-5 text-center text-sm text-secondary sm:hidden">
                    Belum punya akun?{" "}
                    <Link
                        href="/register"
                        className="font-semibold text-primary"
                    >
                        Daftar Sekarang
                    </Link>
                </p>
            </section>
        </AuthLayout>
    );
}
