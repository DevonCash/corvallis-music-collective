import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    logLevel: 'info',
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/js/app.js",

                "resources/css/filament/member/theme.css"
            ],
            refresh: true,
        }),
    ],
});
