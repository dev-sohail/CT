import { NextResponse } from 'next/server'
import { getCached, setCached } from '@/lib/cache'

export async function GET(request: Request) {
  try {
    const token = request.headers.get('authorization')?.replace('Bearer ', '') || ''
    const cacheKey = `capabilities:${token}`
    const cached = getCached<{ intents: any[]; tools: any[]; status: any }>(cacheKey)
    if (cached) {
      return NextResponse.json(cached, { headers: { 'Cache-Control': 'private, max-age=120, stale-while-revalidate=60' } })
    }
    const apiBase = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000'
    const res = await fetch(`${apiBase}/v1/capabilities`, {
      headers: { Authorization: `Bearer ${token}` },
    })
    const data = await res.json()
    if (!res.ok) return NextResponse.json(data, { status: res.status })
    setCached(cacheKey, data, 120_000)
    return NextResponse.json(data, { headers: { 'Cache-Control': 'private, max-age=120, stale-while-revalidate=60' } })
  } catch (err: any) {
    return NextResponse.json({ detail: err.message }, { status: 500 })
  }
}
