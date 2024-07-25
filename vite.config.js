import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import { svelte } from "@sveltejs/vite-plugin-svelte";

export default defineConfig({
  ssr: {
    noExternal: true,
  },
  plugins: [
    laravel({
      input: ["resources/js/app.js", `resources/css/filament/admin/theme.css`],
      refresh: true,
      ssr: "resources/js/ssr.js",
    }),
    svelte({
      compilerOptions: {
        hydratable: true,
      },
    }),
  ],
});
