import { defineConfig } from 'vitest/config';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [laravel({ input: ['resources/js/main.tsx'], refresh: true }), react()],
  test: { environment: 'jsdom', setupFiles: ['./resources/js/test/setup.ts'], globals: true, css: false, exclude: ['mobile/**', 'node_modules/**'] },
});
