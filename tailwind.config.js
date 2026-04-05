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
                primary: "var(--c-accent)", 
                accent: "var(--c-accent)",
                surface: "var(--c-surface)",
                "surface-2": "var(--c-surface2)",
                "card-bg": "var(--c-card-bg)",
                "glass-bg": "var(--c-glass-bg)",
                "glass-border": "var(--c-glass-border)",
                "input-bg": "var(--c-input-bg)",
                "text-main": "var(--c-text)",
                "text-muted": "var(--c-text-muted)",
                "border-main": "var(--c-border)",
                "border-subtle": "var(--c-border-subtle)",
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
