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
                'pe-purple':  '#6D28D9',
                'pe-purple2': '#8B5CF6',
                'pe-bg':      '#EEF2FF',
                'pe-card':    '#FFFFFF',
                'pe-text':    '#1E1B4B',
                'pe-muted':   '#4B5563',
                'pe-border':  '#DDD6FE',
                'pe-green':   '#059669',
                'pe-red':     '#DC2626',
                'pe-amber':   '#D97706',
                'pe-pink':    '#DB2777',
                primary: {
                    100: '#f3e8ff',
                    200: '#e9d5ff',
                    300: '#d8b4fe',
                    400: '#c084fc',
                    500: '#a855f7',
                    600: '#9333ea',
                    700: '#7e22ce',
                    800: '#6b21a8',
                    900: '#581c87',
                },
                secondary: {
                    100: '#fef3c7',
                    200: '#fde68a',
                    300: '#fcd34d',
                    400: '#fbbf24',
                    500: '#f59e0b',
                    600: '#d97706',
                    700: '#b45309',
                    800: '#92400e',
                    900: '#78350f',
                },
                background: {
                    light: '#f9fafb',
                    DEFAULT: '#f3f4f6',
                    dark: '#1f2937',
                },
                success: '#10b981',
                warning: '#f59e0b',
                error: '#ef4444',
                info: '#3b82f6',
                secondaryText: '#6b7280',
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
            fontSize: {
                'h1': ['2.25rem', { lineHeight: '2.5rem', fontWeight: '700' }],
                'h2': ['1.875rem', { lineHeight: '2.25rem', fontWeight: '600' }],
                'h3': ['1.5rem', { lineHeight: '2rem', fontWeight: '500' }],
                'stat': ['3rem', { lineHeight: '3.5rem', fontWeight: '800' }],
                'label': ['0.875rem', { lineHeight: '1rem', fontWeight: '600', letterSpacing: '0.05em' }],
                'secondary': ['0.875rem', { lineHeight: '1.25rem', fontWeight: '400', color: '#6b7280' }],
            },
        },
    },

    plugins: [forms],
};
