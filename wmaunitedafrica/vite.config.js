import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    react(),
    {
      name: 'fix-pwa-manifest',
      transformIndexHtml: {
        order: 'post',
        handler(html) {
          return html
            .replace(/\.\/assets\/site-[^"]+\.webmanifest/g, './site.webmanifest')
            .replace(/\.\/assets\/favicon-([0-9x]+)-[A-Za-z0-9]+\.png/g, './favicon-$1.png')
            .replace(/\.\/assets\/apple-touch-icon-[A-Za-z0-9]+\.png/g, './apple-touch-icon.png')
            .replace(/\.\/assets\/favicon-[A-Za-z0-9]+\.ico/g, './favicon.ico');
        },
      },
    },
  ],
  base: './',
})
