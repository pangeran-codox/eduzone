import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    safelist: [
        // Feature card colors
        { pattern: /bg-(blue|violet|emerald|amber|rose|teal|pink|indigo|orange|slate)-(50|100|200)/ },
        { pattern: /text-(blue|violet|emerald|amber|rose|teal|pink|indigo|orange|slate)-(500|600|700)/ },
        // Gradient from/to colors
        { pattern: /from-(slate|blue|violet|emerald|amber|orange|teal|pink|rose|indigo)-(500|600|700|800)/ },
        { pattern: /to-(slate|blue|violet|emerald|amber|orange|teal|pink|rose|indigo)-(600|700|800)/ },
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Plus Jakarta Sans', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },
    plugins: [],
};
