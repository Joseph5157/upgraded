import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    100: '#f0f9ff',
                    500: '#3b82f6',
                    600: '#2563eb',
                    900: '#1e3a8a',
                },
                secondary: {
                    100: '#f3f4f6',
                    500: '#6b7280',
                    900: '#111827',
                },
                success: '#10b981',
                warning: '#f59e0b',
                error: '#ef4444',
                info: '#3b82f6',
            },
            borderRadius: {
                lg: '0.5rem',
                xl: '0.75rem',
                full: '9999px',
            },
            boxShadow: {
                button: '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -1px rgb(0 0 0 / 0.06)',
                card: '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -1px rgb(0 0 0 / 0.06)',
                cardHover: '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -2px rgb(0 0 0 / 0.05)',
                hover: '0 8px 16px -4px rgb(0 0 0 / 0.2)',
                focus: '0 0 0 4px rgb(59 130 246 / 0.5)',
            },
            spacing: {
                5: '1.25rem',
                7: '1.75rem',
                9: '2.25rem',
                14: '3.5rem',
                18: '4.5rem',
                22: '5.5rem',
                26: '6.5rem',
                30: '7.5rem',
                28: '7rem',
            },
            margin: {
                auto: 'auto',
            },
            padding: {
                auto: 'auto',
            },
            maxWidth: {
                screen: '100vw',
            },
            alignItems: {
                center: 'center',
                start: 'flex-start',
                end: 'flex-end',
            },
            justifyContent: {
                center: 'center',
                between: 'space-between',
                around: 'space-around',
            },
            transitionTimingFunction: {
                'ease-in-out': 'cubic-bezier(0.4, 0, 0.2, 1)',
            },
            transitionDuration: {
                300: '300ms',
                500: '500ms',
            },
            screens: {
                sm: '640px',
                md: '768px',
                lg: '1024px',
                xl: '1280px',
            },
        },
    },

    plugins: [forms],
};
