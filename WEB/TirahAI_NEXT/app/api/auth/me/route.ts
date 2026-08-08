import { NextResponse } from 'next/server'
import { getCached, setCached } from '@/lib/cache'

export async function GET(request: Request) {
  try {
    const token = request.headers.get('authorization')?.replace('Bearer ', '') || ''
    if (!token) return NextResponse.json({ detail: 'Not authenticated' }, { status: 401 })
    const cached = getCached<{ user: any }>(`auth/me:${token}`)
    if (cached) {
      return NextResponse.json(cached.user, { headers: { 'Cache-Control': 'private, max-age=60, stale-while-revalidate=30' } })
    }
    const apiBase = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000'
    const res = await fetch(`${apiBase}/v1/auth/me`, { headers: { Authorization: `Bearer ${token}` } })
    const data = await res.json()
    if (!res.ok) return NextResponse.json(data, { status: res.status })
    setCached(`auth/me:${token}`, data, 60_000)
    return NextResponse.json(data, { headers: { 'Cache-Control': 'private, max-age=60, stale-while-revalidate=30' } })
  } catch (err: any) {
    return NextResponse.json({ detail: err.message }, { status: 500 })
  }
}
