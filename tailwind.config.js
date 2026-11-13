const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/rappasoft/laravel-livewire-tables/resources/views/*.blade.php',
        './vendor/rappasoft/laravel-livewire-tables/resources/views/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        fontSize: {
            xs: ['11px', { lineHeight: '16px' }],
            sm: ['13px', { lineHeight: '20px' }],
            base: ['14px', { lineHeight: '20px' }],
            lg: ['16px', { lineHeight: '24px' }],
            xl: ['18px', { lineHeight: '28px' }],
            '2xl': ['21px', { lineHeight: '28px' }],
            '3xl': ['24px', { lineHeight: '32px' }],
            '4xl': ['30px', { lineHeight: '36px' }],
            '5xl': ['36px', { lineHeight: '40px' }],
            '6xl': ['48px', { lineHeight: '48px' }],
            '7xl': ['60px', { lineHeight: '60px' }],
            '8xl': ['72px', { lineHeight: '72px' }],
            '9xl': ['96px', { lineHeight: '96px' }],
        },
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [require('@tailwindcss/forms')],
};
