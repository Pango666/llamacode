import { defineConfig } from 'astro/config';
import tailwind from '@tailwindcss/vite';

export default defineConfig({
  server: { host: true },
  site: 'http://localhost:4321', // luego lo cambias por tu dominio
  vite: { plugins: [tailwind()] }
});