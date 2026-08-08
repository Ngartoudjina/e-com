import axios from 'axios'

/**
 * Base de l'API.
 *
 * Configurable par VITE_API_BASE afin de pouvoir viser un backend local en
 * développement : l'instance déployée refuse l'origine localhost (CORS), ce
 * qui rendait le catalogue vide en local sans erreur visible à l'écran.
 */
export const API_BASE = import.meta.env.VITE_API_BASE || 'https://e-com-back-nxod.onrender.com'

export const api = axios.create({
  baseURL: API_BASE,
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})
