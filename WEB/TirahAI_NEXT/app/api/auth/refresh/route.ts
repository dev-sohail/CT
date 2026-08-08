import { NextResponse } from 'next/server'

export async function POST(request: Request) {
  try {
    const { refresh_token } = await request.json()
    const apiBase = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000'
    const res = await fetch(`${apiBase}/v1/auth/refresh`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ refresh_token }),
    })
    const data = await res.json()
    if (!res.ok) return NextResponse.json(data, { status: res.status })
    const response = NextResponse.json(data)
    response.cookies.set('access_token', data.access_token, { httpOnly: true, secure: process.env.NODE_ENV === 'production', sameSite: 'lax', path: '/' })
    return response
  } catch (err: any) {
    return NextResponse.json({ detail: err.message }, { status: 500 })
  }
}
