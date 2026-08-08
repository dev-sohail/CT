'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import Link from 'next/link'
import { useTranslations } from 'next-intl'

export default function RegisterPage() {
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const router = useRouter()
  const t = useTranslations()

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    setError('')
    try {
      const res = await fetch('/api/auth/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password, first_name: '', last_name: '', role: 'student' }),
      })
      const data = await res.json()
      if (!res.ok) throw new Error(data.detail || 'Registration failed')
      router.push('/login')
    } catch (err: any) {
      setError(err.message)
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center">
      <form onSubmit={handleSubmit} className="w-full max-w-sm space-y-4 rounded border p-6">
        <h1 className="text-2xl font-bold">{t('auth.register')}</h1>
        {error && <p className="text-red-500">{error}</p>}
        <input className="w-full rounded border p-2" type="email" placeholder={t('auth.email')} value={email} onChange={e => setEmail(e.target.value)} required />
        <input className="w-full rounded border p-2" type="password" placeholder={t('auth.password')} value={password} onChange={e => setPassword(e.target.value)} required />
        <button type="submit" className="w-full rounded bg-primary p-2 text-white">{t('auth.register')}</button>
        <p className="text-sm">
          Already have an account? <Link href="/login" className="text-primary">{t('auth.login')}</Link>
        </p>
      </form>
    </div>
  )
}
