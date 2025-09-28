import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";

export default defineConfig({
    plugins: [
        laravel({
            input: "resources/js/app.js",
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    server: {
        host: "0.0.0.0", // aceita conexões externas
        port: 5173,
        strictPort: true,
        hmr: {
            host: "192.168.18.22", // IP do PC (igual ao .env)
            protocol: "ws", // WebSocket para hot reload
        },
    },
});
