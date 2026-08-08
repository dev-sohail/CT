import { NextResponse } from 'next/server'

export async function GET(request: Request) {
  try {
    const token = request.headers.get('authorization')?.replace('Bearer ', '') || ''
    const apiBase = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000'
    const usersRes = await fetch(`${apiBase}/v1/auth/users`, {
      headers: { Authorization: `Bearer ${token}` },
    })
    const usersData = await usersRes.json()
    if (!usersRes.ok) return NextResponse.json(usersData, { status: usersRes.status })

    return NextResponse.json({
      users: Array.isArray(usersData) ? usersData.length : 0,
      queries: 0,
      system: 'ok',
    })
  } catch (err: any) {
    return NextResponse.json({ detail: err.message }, { status: 500 })
  }
}
