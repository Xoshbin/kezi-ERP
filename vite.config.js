import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/js/app.js",
                "resources/js/filament/jmeryar/theme.js",
                "packages/xoshbin/pertuk/resources/js/pertuk.js",
            ],
            refresh: true,
        }),
        tailwindcss({
            content: [
                "./resources/**/*.blade.php",
                "./resources/**/*.js",
                "./packages/xoshbin/pertuk/resources/views/**/*.blade.php",
                "./packages/xoshbin/pertuk/resources/css/**/*.css",
            ],
        }),
    ],
});
