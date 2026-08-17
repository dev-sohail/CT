export const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000'

type CacheEntry = { data: any; expires: number }
const routeCache = new Map<string, CacheEntry>()

export function getCached<T>(key: string): T | null {
  const entry = routeCache.get(key)
  if (!entry) return null
  if (Date.now() > entry.expires) {
    routeCache.delete(key)
    return null
  }
  return entry.data as T
}

export function setCached(key: string, data: any, ttlMs = 60_000) {
  routeCache.set(key, { data, expires: Date.now() + ttlMs })
}
