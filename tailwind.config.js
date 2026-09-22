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
            // Cores extraídas do logo da Via Vale Sistemas.
            colors: {
                brand: {
                    slate: { DEFAULT: '#2C3A49', dark: '#212C38' },
                    green: { DEFAULT: '#35D159', dark: '#1E9E3F', light: '#E7F9EC' },
                    blue: { DEFAULT: '#496AE2', dark: '#354FB8', light: '#ECEFFC' },
                },
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
