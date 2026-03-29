import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  // Set base to /frontend/ so all asset paths resolve correctly
  // when the built files are served from a /frontend subfolder
  base: '/frontend/',
})
