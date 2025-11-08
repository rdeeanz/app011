/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_APP_NAME: string
  readonly VITE_APP_ENV: string
  readonly VITE_APP_URL: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
  readonly glob: (pattern: string) => Record<string, () => Promise<any>>
}

declare module '*.vue' {
  import type { DefineComponent } from 'vue'
  const component: DefineComponent<{}, {}, any>
  export default component
}

declare module 'ziggy-js' {
  interface Config {
    routes: Record<string, any>
    location: string
  }
  
  export const Ziggy: Config
}

// Laravel Ziggy types
declare global {
  interface Window {
    route: (name?: string, params?: any, absolute?: boolean) => string
  }
  
  const route: (name?: string, params?: any, absolute?: boolean) => string
}