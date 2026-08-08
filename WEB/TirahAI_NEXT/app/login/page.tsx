'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import Link from 'next/link'
import { useTranslations } from 'next-intl'

export default function LoginPage() {
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)
  const router = useRouter()
  const t = useTranslations()

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    setError('')
    setLoading(true)
    try {
      const res = await fetch('/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password }),
      })
      const data = await res.json()
      if (!res.ok) throw new Error(data.detail || 'Login failed')
      localStorage.setItem('access_token', data.access_token)
      router.push('/dashboard')
    } catch (err: any) {
      setError(err.message)
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center">
      <a href="#login-form" className="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:bg-primary focus:text-white focus:px-4 focus:py-2 focus:rounded">
        Skip to login form
      </a>
      <form id="login-form" onSubmit={handleSubmit} className="w-full max-w-sm space-y-4 rounded border p-6" aria-label="Login form">
        <h1 className="text-2xl font-bold">{t('auth.login')}</h1>
        {error && <p className="text-red-500" role="alert" aria-live="assertive">{error}</p>}
        <div>
          <label htmlFor="login-email" className="block text-sm font-medium text-gray-700 mb-1">{t('auth.email')}</label>
          <input id="login-email" className="w-full rounded border p-2" type="email" placeholder={t('auth.email')} value={email} onChange={e => setEmail(e.target.value)} required autoComplete="email" />
        </div>
        <div>
          <label htmlFor="login-password" className="block text-sm font-medium text-gray-700 mb-1">{t('auth.password')}</label>
          <input id="login-password" className="w-full rounded border p-2" type="password" placeholder={t('auth.password')} value={password} onChange={e => setPassword(e.target.value)} required autoComplete="current-password" />
        </div>
        <button type="submit" className="w-full rounded bg-primary p-2 text-white" disabled={loading} aria-busy={loading}>
          {loading ? 'Signing in...' : t('auth.login')}
        </button>
        <p className="text-sm">
          <Link href="/forgot" className="text-primary">{t('auth.forgotPassword')}</Link>
        </p>
        <p className="text-sm">
          No account? <Link href="/register" className="text-primary">{t('auth.register')}</Link>
        </p>
      </form>
    </div>
  )
}
