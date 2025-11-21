import { defineConfig } from "vite";
import symfonyPlugin from "vite-plugin-symfony";
import vue from "@vitejs/plugin-vue";
import vuetify from "vite-plugin-vuetify";

export default defineConfig({
    plugins: [
        vue(),
        vuetify({ autoImport: true }),
        symfonyPlugin(),
    ],
    build: {
        rollupOptions: {
            input: {
                app: "./assets/app.js",
            },
        },
    },
    server: {
        host: true,
        port: 5173,
    }
});
