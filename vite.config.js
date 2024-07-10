import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import { svelte } from "@sveltejs/vite-plugin-svelte";

export default defineConfig({
  plugins: [
    laravel({
      input: ["resources/js/app.js", `resources/css/filament/admin/theme.css`],
      refresh: true,
    }),
    svelte({}),
  ],
});
