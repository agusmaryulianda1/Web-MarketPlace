import { router } from '@inertiajs/react';
import { useEffect } from 'react';

export default function AosProvider({ children }) {
    useEffect(() => {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return undefined;
        }

        let active = true;
        let unsubscribe;
        const root = document.documentElement;

        import('aos').then(({ default: AOS }) => {
            if (!active) {
                return;
            }

            AOS.init({
                once: true,
                duration: 550,
                easing: 'ease-out',
                offset: 70,
            });
            root.classList.add('aos-enabled');
            unsubscribe = router.on('navigate', () => {
                window.requestAnimationFrame(() => AOS.refreshHard());
            });
        }).catch(() => {
            root.classList.remove('aos-enabled');
        });

        return () => {
            active = false;
            unsubscribe?.();
            root.classList.remove('aos-enabled');
        };
    }, []);

    return children;
}
