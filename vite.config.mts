import {resolve} from 'node:path';
import {defineConfig, ServerOptions, ViteDevServer, Plugin} from 'vite';
import process from "node:process";
const isDev = process.env.NODE_ENV !== 'production';
const server: ServerOptions = {
  host: false,
  port: 3000,
  open: true,
  fs: {strict: false}
};

const templatesRewritePlugin = (): Plugin => ({
  name: 'templates-rewrite',
  configureServer: (server: ViteDevServer) => {
    server.middlewares.use((req, _res, next) => {
      console.log(req.url);
      if (req.method === 'GET' && req.url?.endsWith('.html') && !req.url?.startsWith('/@templates/') && !req.url?.startsWith('/@templates/')) {
        req.url = `./src/@templates${req.url}`;
      }
      next();
    });
  },
})

export default defineConfig({
  root: './',
  publicDir: resolve(__dirname, 'assets'),
  base: '/',
  server,
  build: {
    outDir: 'dist',
    emptyOutDir: true,
  },
  plugins: [
    templatesRewritePlugin(),
  ]
});
