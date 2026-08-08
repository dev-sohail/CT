import { NextResponse } from 'next/server'

export async function GET(request: Request) {
  try {
    const token = request.headers.get('authorization')?.replace('Bearer ', '') || ''
    const apiBase = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000'
    const res = await fetch(`${apiBase}/v1/auth/users`, {
      headers: { Authorization: `Bearer ${token}` },
    })
    const data = await res.json()
    if (!res.ok) return NextResponse.json(data, { status: res.status })
    return NextResponse.json(data)
  } catch (err: any) {
    return NextResponse.json({ detail: err.message }, { status: 500 })
  }
}

export async function PUT(request: Request) {
  try {
    const body = await request.json()
    const id = Array.isArray(body) ? body[0]?.id : body?.id
    if (!id) return NextResponse.json({ detail: 'missing_id' }, { status: 400 })
    const token = request.headers.get('authorization')?.replace('Bearer ', '') || ''
    const apiBase = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000'
    const payload = Array.isArray(body) ? body[0] : body
    const res = await fetch(`${apiBase}/v1/auth/users/${encodeURIComponent(id)}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify(payload),
    })
    const data = await res.json()
    if (!res.ok) return NextResponse.json(data, { status: res.status })
    return NextResponse.json(data)
  } catch (err: any) {
    return NextResponse.json({ detail: err.message }, { status: 500 })
  }
}

export async function DELETE(request: Request) {
  try {
    const body = await request.json()
    const id = Array.isArray(body) ? body[0]?.id : body?.id
    if (!id) return NextResponse.json({ detail: 'missing_id' }, { status: 400 })
    const token = request.headers.get('authorization')?.replace('Bearer ', '') || ''
    const apiBase = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000'
    const res = await fetch(`${apiBase}/v1/auth/users/${encodeURIComponent(id)}`, {
      method: 'DELETE',
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
    const data = await res.json()
    if (!res.ok) return NextResponse.json(data, { status: res.status })
    return NextResponse.json(data)
  } catch (err: any) {
    return NextResponse.json({ detail: err.message }, { status: 500 })
  }
}
