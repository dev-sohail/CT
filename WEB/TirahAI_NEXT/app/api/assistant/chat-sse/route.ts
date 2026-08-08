export const runtime = 'nodejs'

export async function POST(request: Request) {
  try {
    const body = await request.json()
    const token = request.headers.get('authorization')?.replace('Bearer ', '') || ''
    const apiBase = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000'

    const upstream = await fetch(`${apiBase}/v1/assistant/chat/stream`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify(body),
    })

    if (!upstream.ok || !upstream.body) {
      const text = await upstream.text()
      return new Response(text, { status: upstream.status, headers: { 'Content-Type': 'application/json' } })
    }

    const encoder = new TextEncoder()
    const stream = new ReadableStream({
      async start(controller) {
        const reader = upstream.body!.getReader()
        const decoder = new TextDecoder()
        try {
          while (true) {
            const { done, value } = await reader.read()
            if (done) break
            controller.enqueue(encoder.encode(decoder.decode(value)))
          }
        } catch (err) {
          controller.enqueue(encoder.encode(`data: [error] ${err}\n\n`))
        } finally {
          controller.close()
        }
      },
    })

    return new Response(stream, {
      headers: {
        'Content-Type': 'text/event-stream',
        'Cache-Control': 'no-cache',
        Connection: 'keep-alive',
      },
    })
  } catch (err: any) {
    return new Response(`data: [error] ${err.message}\n\ndata: [DONE]\n\n`, {
      status: 500,
      headers: { 'Content-Type': 'text/event-stream' },
    })
  }
}
