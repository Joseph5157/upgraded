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
                primary: '#4F6EF7',
                surface: '#F0F2F5',
                base:    '#FAFBFC',
                ink:     '#1A1D23',
                muted:   '#9CA3AF',
                border:  '#E2E6EA',
                subtle:  '#ECEEF2',
            },
        },
    },

    plugins: [forms],
};
