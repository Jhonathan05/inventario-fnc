import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            // Tipografía nativa del sistema (ver resources/css/design-system/macos.css).
            fontFamily: {
                sans: [
                    '-apple-system',
                    'BlinkMacSystemFont',
                    'SF Pro Display',
                    'SF Pro Text',
                    'Segoe UI Variable Text',
                    'Segoe UI',
                    'system-ui',
                    ...defaultTheme.fontFamily.sans,
                ],
                mono: ['ui-monospace', 'SFMono-Regular', 'SF Mono', 'Menlo', 'Consolas', ...defaultTheme.fontFamily.mono],
            },

            // Paleta institucional FNC.
            colors: {
                fnc: {
                    brand: '#9e052b',
                    'brand-strong': '#7f0422',
                    'brand-soft': '#fbebf0',
                    gold: '#d97706',
                },
            },

            // Radios del sistema macOS.
            borderRadius: {
                'fnc-sm': '10px',
                'fnc-md': '14px',
                'fnc-lg': '18px',
                'fnc-xl': '24px',
            },

            // Sombras de ventana / tarjeta / popover.
            boxShadow: {
                'fnc-card': '0 1px 2px rgba(15, 23, 42, 0.04), 0 14px 34px -22px rgba(15, 23, 42, 0.45)',
                'fnc-pop': '0 28px 64px -24px rgba(15, 23, 42, 0.45), 0 2px 8px rgba(15, 23, 42, 0.06)',
                'fnc-window': '0 22px 70px 4px rgba(15, 23, 42, 0.28)',
            },
        },
    },

    plugins: [forms],
};
