export const runtime = 'nodejs'

export async function GET(request: Request) {
  const url = new URL(request.url)
  const token = url.searchParams.get('token') || request.headers.get('authorization')?.replace('Bearer ', '') || ''
  const topic = url.searchParams.get('topic') || 'stats'

  const stream = new ReadableStream({
    start(controller) {
      const encoder = new TextEncoder()
      const send = (event: string, data: unknown) => {
        controller.enqueue(encoder.encode(`event: ${event}\ndata: ${JSON.stringify(data)}\n\n`))
      }

      send('connected', { topic, message: 'Realtime stream active' })

      const interval = setInterval(async () => {
        try {
          const stats: Record<string, unknown> = {}
          try {
            const apiBase = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000'
            const usersRes = await fetch(`${apiBase}/v1/auth/users`, { headers: { Authorization: `Bearer ${token}` } })
            const usersData = await usersRes.json()
            stats.users = Array.isArray(usersData) ? usersData.length : 0
          } catch {
            stats.users = 0
          }

          if (topic === 'stats') {
            send('stats', stats)
          }
        } catch {
          send('error', { detail: 'stream_error' })
        }
      }, 5000)

      const close = () => {
        clearInterval(interval)
        try { controller.close() } catch { /* ignore */ }
      }
      ;(request as any).signal?.addEventListener?.('abort', close)
    },
  })

  return new Response(stream, {
    headers: {
      'Content-Type': 'text/event-stream',
      'Cache-Control': 'no-cache, no-transform',
      'Connection': 'keep-alive',
    },
  })
}
