interface ImportMetaEnv {
  readonly VITE_API_URL: string;
  readonly VITE_API_PROXY_TARGET: string;
  readonly DEV: boolean;
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
}
