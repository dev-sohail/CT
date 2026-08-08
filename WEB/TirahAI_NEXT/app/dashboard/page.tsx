'use client'

import { useEffect, useState } from 'react'
import { useRouter } from 'next/navigation'
import ThemeToggle from '@/components/ThemeToggle'
import { useTranslations } from 'next-intl'
import OnboardingTour from '@/components/OnboardingTour'

type Tool = { name: string; description: string }
type HistoryItem = { message: string; response: string }

function SkeletonCard() {
  return (
    <div className="rounded border p-4 animate-pulse" aria-hidden="true">
      <div className="h-4 bg-gray-200 rounded w-3/4 mb-2" />
      <div className="h-3 bg-gray-200 rounded w-1/2" />
    </div>
  )
}

export default function DashboardPage() {
  const [capabilities, setCapabilities] = useState<any>(null)
  const [message, setMessage] = useState('')
  const [response, setResponse] = useState('')
  const [streaming, setStreaming] = useState(false)
  const [history, setHistory] = useState<HistoryItem[]>([])
  const [activeTool, setActiveTool] = useState<string | null>(null)
  const [toast, setToast] = useState<{ type: 'success' | 'error'; message: string } | null>(null)
  const router = useRouter()
  const t = useTranslations()

  useEffect(() => {
    const token = localStorage.getItem('access_token')
    if (!token) { router.push('/login'); return }
    const stored = localStorage.getItem('chat_history')
    if (stored) {
      try { setHistory(JSON.parse(stored)) } catch {}
    }
    fetch('/api/auth/me', { headers: { Authorization: `Bearer ${token}` } })
      .then(res => res.ok ? res.json() : Promise.reject())
      .then(() => fetch('/api/assistant/capabilities', { headers: { Authorization: `Bearer ${token}` } }))
      .then(res => res.json())
      .then(setCapabilities)
      .catch(() => router.push('/login'))
  }, [router])

  useEffect(() => {
    if (!toast) return
    const timer = setTimeout(() => setToast(null), 4000)
    return () => clearTimeout(timer)
  }, [toast])

  async function sendChat(e: React.FormEvent) {
    e.preventDefault()
    const token = localStorage.getItem('access_token') || ''
    setResponse('')
    setStreaming(true)
    try {
      const res = await fetch('/api/assistant/chat-sse', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
        body: JSON.stringify({ message, session_id: 'web' }),
      })
      const reader = res.body?.getReader()
      const decoder = new TextDecoder()
      let full = ''
      if (reader) {
        while (true) {
          const { done, value } = await reader.read()
          if (done) break
          const chunk = decoder.decode(value)
          const lines = chunk.split('\n')
          for (const line of lines) {
            if (line.startsWith('data: ')) {
              const payload = line.slice(6)
              if (payload === '[DONE]') break
              if (payload.startsWith('[error]')) {
                setResponse(payload.replace('[error]', '').trim())
                setToast({ type: 'error', message: 'Assistant error' })
                break
              }
              full += payload
              setResponse(full)
            }
          }
        }
      }
      const next = [...history, { message, response: full }].slice(-50)
      setHistory(next)
      localStorage.setItem('chat_history', JSON.stringify(next))
      setToast({ type: 'success', message: 'Response received' })
    } finally {
      setStreaming(false)
    }
  }

  async function executeTool(tool: Tool) {
    setActiveTool(tool.name)
    const token = localStorage.getItem('access_token') || ''
    setResponse('Running...')
    try {
      const res = await fetch('/api/tools/execute', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
        body: JSON.stringify({ tool: tool.name, kwargs: { query: message } }),
      })
      const data = await res.json()
      setResponse(JSON.stringify(data, null, 2))
      setToast({ type: 'success', message: `Tool ${tool.name} executed` })
    } catch (err: any) {
      setResponse(err.message)
      setToast({ type: 'error', message: err.message })
    } finally {
      setActiveTool(null)
    }
  }

  function clearHistory() {
    setHistory([])
    localStorage.removeItem('chat_history')
    setToast({ type: 'success', message: 'History cleared' })
  }

  const isLoading = !capabilities

  return (
    <div className="mx-auto max-w-4xl p-8 space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-bold" id="dashboard-title">{t('dashboard.title')}</h1>
        <div className="flex items-center gap-2">
          <ThemeToggle id="theme-toggle" aria-label="Toggle theme" />
          <OnboardingTour />
        </div>
      </div>

      <div className="grid grid-cols-1 gap-4 md:grid-cols-3" id="tools-panel" role="region" aria-label="Assistant tools">
        {isLoading ? (
          <>
            <SkeletonCard />
            <SkeletonCard />
            <SkeletonCard />
          </>
        ) : (
          <>
            {(capabilities.tools || []).map((tool: Tool) => (
              <button
                key={tool.name}
                onClick={() => executeTool(tool)}
                disabled={activeTool === tool.name}
                className="rounded border p-4 text-left hover:bg-gray-50 disabled:opacity-50 dark:hover:bg-slate-800"
                aria-label={`Execute ${tool.name}: ${tool.description}`}
              >
                <h3 className="font-semibold">{tool.name}</h3>
                <p className="text-sm text-gray-600 dark:text-gray-300">{tool.description}</p>
              </button>
            ))}
            {(!capabilities.tools || capabilities.tools.length === 0) && (
              <p className="text-sm text-gray-500" role="status">{t('dashboard.noTools')}</p>
            )}
          </>
        )}
      </div>

      <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
        <div className="md:col-span-2 space-y-4">
          <form onSubmit={sendChat} className="space-y-2" id="chat-input" aria-label="Chat with assistant">
            <label htmlFor="chat-message" className="sr-only">Message</label>
            <textarea
              id="chat-message"
              className="w-full rounded border p-2 dark:bg-slate-800 dark:text-white"
              rows={3}
              placeholder={t('dashboard.assistant')}
              value={message}
              onChange={e => setMessage(e.target.value)}
              disabled={streaming}
              aria-describedby="chat-status"
            />
            <button type="submit" className="rounded bg-primary px-4 py-2 text-white" disabled={streaming || !message.trim()}>
              {streaming ? t('dashboard.thinking') : t('dashboard.send')}
            </button>
            <span id="chat-status" className="sr-only" aria-live="polite">
              {streaming ? 'Assistant is thinking' : response ? 'Assistant responded' : ''}
            </span>
          </form>
          {response && (
            <pre className="rounded border bg-gray-50 p-4 text-sm whitespace-pre-wrap dark:bg-slate-800 dark:text-gray-100" role="region" aria-label="Assistant response">
              {response}
            </pre>
          )}
        </div>

        <div className="space-y-2" id="history-panel" role="region" aria-label="Conversation history">
          <div className="flex items-center justify-between">
            <h2 className="font-semibold">{t('dashboard.history')}</h2>
            <button onClick={clearHistory} className="text-xs text-gray-500 underline" aria-label="Clear conversation history">
              {t('dashboard.clearHistory')}
            </button>
          </div>
          <div className="max-h-96 space-y-2 overflow-y-auto" role="list" aria-label="Past conversations">
            {history.length === 0 && (
              <p className="text-sm text-gray-500" role="status">{t('dashboard.noHistory')}</p>
            )}
            {history.slice().reverse().map((item, i) => (
              <button
                key={i}
                onClick={() => setMessage(item.message)}
                className="w-full rounded border p-2 text-left text-sm hover:bg-gray-50 dark:hover:bg-slate-800"
                aria-label={`Previous conversation: ${item.message}`}
                role="listitem"
              >
                <p className="font-medium truncate">{item.message}</p>
                <p className="truncate text-gray-500">{item.response.slice(0, 80)}</p>
              </button>
            ))}
          </div>
        </div>
      </div>

      {toast && (
        <div
          role="alert"
          aria-live="assertive"
          className={`fixed bottom-4 right-4 z-50 rounded-lg px-4 py-3 text-sm text-white shadow-lg ${
            toast.type === 'success' ? 'bg-green-600' : 'bg-red-600'
          }`}
        >
          {toast.message}
        </div>
      )}
    </div>
  )
}
