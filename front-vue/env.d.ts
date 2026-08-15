/// <reference types="vite/client" />

interface ImportMetaEnv {
  /**
   * Base de l'API Laravel. Absente, les appels partent en relatif.
   *
   * Les déclarations VITE_FIREBASE_* et VITE_GOOGLE_CLIENT_ID ont disparu avec
   * l'authentification Firebase, remplacée par Sanctum.
   */
  readonly VITE_API_BASE?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
