import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.vue",
    ],

    theme: {
        extend: {
            colors: {
                primary: "#121B06", // Dark background
                secondary: "#062F69", // Deep Blue
                accent: "#069BFF", // Light Blue/Accent
                memory: {
                    dark: "#0d1304",
                    light: "#e2f0d5",
                },
            },
            fontFamily: {
                sans: ["Cairo", "Figtree", ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
