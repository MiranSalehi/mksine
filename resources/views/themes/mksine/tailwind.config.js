/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './**/*.blade.php',
        './src/**/*.js',
        // Package components (pagination, etc.)
        '../../components/**/*.blade.php',
        // Page Builder components and render views
        '../../page-builder/**/*.blade.php',
        // Modireshop plugin blocks (pricing, etc.)
        '../../../../../../plugins/modireshop/resources/views/**/*.blade.php',
        // Filament form fields
        '../../filament/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                primary: '#ec4899',
                secondary: '#f43f5e',
            },
        },
    },
    plugins: [],
}
