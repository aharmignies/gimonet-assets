import { defineConfig } from 'astro/config';

export default defineConfig({
  site: 'https://hermelin-peinture.fr',
  build: {
    format: 'directory',
  },
  trailingSlash: 'ignore',
});
