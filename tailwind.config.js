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
    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
                display: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                rose: {
                    50: '#FFF5F5',
                    100: '#FFE5E5',
                    200: '#FFC8C8',
                    300: '#FFA0A0',
                    400: '#FF6B6B',
                    500: '#FA3E3E',
                    600: '#EC0A23', // Telkomsel Brand Red
                    700: '#C8081B',
                    800: '#A40615',
                    900: '#80040F',
                    950: '#4C0003',
                }
            }
        },
    },
    plugins: [],
};
