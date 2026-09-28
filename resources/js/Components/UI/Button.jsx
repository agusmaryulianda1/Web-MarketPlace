import { cn } from '@/Lib/utils';

export function Button({ className, type = 'button', ...props }) {
    return (
        <button
            className={cn(
                'inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
                className,
            )}
            type={type}
            {...props}
        />
    );
}