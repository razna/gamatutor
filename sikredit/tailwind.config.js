import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/Enums/*.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#eef6ff', 100: '#d9eaff', 200: '#bcdaff', 300: '#8ec3ff', 400: '#59a1ff',
                    500: '#337dfc', 600: '#1d5ef1', 700: '#1549de', 800: '#183db4', 900: '#1a378d', 950: '#142356',
                },
                gold: { 400: '#f5c451', 500: '#e9a92a', 600: '#cc8a16' },
            },
        },
    },
    plugins: [forms],
};
