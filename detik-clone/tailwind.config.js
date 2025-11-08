import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
        './resources/**/*.ts',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'Roboto', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                'detik': {
                    'red': '#C4171D',
                    'dark': '#1a1a1a',
                    'gray': '#6B7280',
                },
            },
        },
    },
    plugins: [forms, typography],
};
