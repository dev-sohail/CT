'use client'

import { useEffect, useState } from 'react'
import { useRouter } from 'next/navigation'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { useTranslations } from 'next-intl'

type User = {
  user_id: number
  username: string
  email: string
  role: string
  first_name: string
  last_name: string
  phone_number: string
  status: string
}

function SkeletonCard() {
  return (
    <Card>
      <CardHeader><div className="h-4 bg-gray-200 rounded w-16 animate-pulse" /></CardHeader>
      <CardContent><div className="h-8 bg-gray-200 rounded w-12 animate-pulse" /></CardContent>
    </Card>
  )
}

export default function AdminPage() {
  const [stats, setStats] = useState<any>(null)
  const [capabilities, setCapabilities] = useState<any>(null)
  const [users, setUsers] = useState<User[]>([])
  const [loading, setLoading] = useState(false)
  const [toast, setToast] = useState<{ type: 'success' | 'error'; message: string } | null>(null)
  const router = useRouter()
  const t = useTranslations()

  useEffect(() => {
    const token = localStorage.getItem('access_token')
    if (!token) { router.push('/login'); return }

    fetch('/api/admin/stats', { headers: { Authorization: `Bearer ${token}` } })
      .then(res => res.ok ? res.json() : Promise.reject())
      .then(data => setStats(data))
      .catch(() => router.push('/login'))

    fetch('/api/assistant/capabilities', { headers: { Authorization: `Bearer ${token}` } })
      .then(res => res.ok ? res.json() : Promise.reject())
      .then(setCapabilities)
      .catch(() => {})

    fetch('/api/admin/users', { headers: { Authorization: `Bearer ${token}` } })
      .then(res => res.ok ? res.json() : Promise.reject())
      .then(data => {
        if (Array.isArray(data)) setUsers(data)
        else if (data?.users && Array.isArray(data.users)) setUsers(data.users)
      })
      .catch(() => {})
  }, [router])

  useEffect(() => {
    if (!toast) return
    const timer = setTimeout(() => setToast(null), 4000)
    return () => clearTimeout(timer)
  }, [toast])

  async function toggleStatus(user: User) {
    setLoading(true)
    try {
      const token = localStorage.getItem('access_token') || ''
      const newStatus = user.status === 'active' ? 'inactive' : 'active'
      const res = await fetch('/api/admin/users', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
        body: JSON.stringify({ id: user.user_id, status: newStatus }),
      })
      const data = await res.json()
      if (!res.ok) throw new Error(data.detail || 'Failed')
      setUsers(list => list.map(u => u.user_id === user.user_id ? { ...u, status: newStatus } : u))
      setToast({ type: 'success', message: `User ${user.username} is now ${newStatus}` })
    } catch (err: any) {
      setToast({ type: 'error', message: err.message })
    } finally {
      setLoading(false)
    }
  }

  async function deleteUser(user: User) {
    if (!confirm(`Delete user ${user.username}?`)) return
    setLoading(true)
    try {
      const token = localStorage.getItem('access_token') || ''
      const res = await fetch('/api/admin/users', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
        body: JSON.stringify({ id: user.user_id }),
      })
      const data = await res.json()
      if (!res.ok) throw new Error(data.detail || 'Failed')
      setUsers(list => list.filter(u => u.user_id !== user.user_id))
      setToast({ type: 'success', message: `User ${user.username} deleted` })
    } catch (err: any) {
      setToast({ type: 'error', message: err.message })
    } finally {
      setLoading(false)
    }
  }

  const isLoading = !stats && !capabilities && users.length === 0

  return (
    <div className="mx-auto max-w-5xl px-4 py-8 space-y-6" role="main" aria-label="Admin dashboard">
      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-bold">{t('admin.title')}</h1>
        <span className="rounded bg-green-100 px-2 py-1 text-xs text-green-700" aria-label="System status">Live</span>
      </div>

      <div className="grid grid-cols-1 gap-4 md:grid-cols-3" role="region" aria-label="Statistics">
        {isLoading ? (
          <>
            <SkeletonCard />
            <SkeletonCard />
            <SkeletonCard />
          </>
        ) : (
          <>
            <Card>
              <CardHeader><CardTitle>Users</CardTitle></CardHeader>
              <CardContent><p className="text-2xl" aria-label={`${stats?.users ?? users.length} users`}>{stats?.users ?? users.length}</p></CardContent>
            </Card>
            <Card>
              <CardHeader><CardTitle>Queries</CardTitle></CardHeader>
              <CardContent><p className="text-2xl" aria-label={`${stats?.queries ?? 0} queries`}>{stats?.queries ?? 0}</p></CardContent>
            </Card>
            <Card>
              <CardHeader><CardTitle>System</CardTitle></CardHeader>
              <CardContent><p className="text-2xl" aria-label={`System status: ${stats?.system ?? 'ok'}`}>{stats?.system ?? 'ok'}</p></CardContent>
            </Card>
          </>
        )}
      </div>

      <Card>
        <CardHeader>
          <CardTitle>{t('admin.users')}</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="overflow-x-auto">
            <table className="w-full text-sm" role="table" aria-label="User management">
              <thead>
                <tr className="border-b">
                  <th scope="col" className="text-left p-2">ID</th>
                  <th scope="col" className="text-left p-2">Username</th>
                  <th scope="col" className="text-left p-2">Email</th>
                  <th scope="col" className="text-left p-2">{t('admin.role')}</th>
                  <th scope="col" className="text-left p-2">{t('admin.active')}</th>
                  <th scope="col" className="text-left p-2">Actions</th>
                </tr>
              </thead>
              <tbody>
                {users.map(user => (
                  <tr key={user.user_id} className="border-b">
                    <td className="p-2">{user.user_id}</td>
                    <td className="p-2">{user.username}</td>
                    <td className="p-2">{user.email}</td>
                    <td className="p-2">{user.role}</td>
                    <td className="p-2">
                      <span className={user.status === 'active' ? 'text-green-600' : 'text-red-600'} aria-label={`Status: ${user.status}`}>
                        {user.status}
                      </span>
                    </td>
                    <td className="p-2 space-x-2">
                      <button onClick={() => toggleStatus(user)} disabled={loading} className="text-xs underline" aria-label={`Toggle status for ${user.username}`}>
                        {t('admin.toggleStatus')}
                      </button>
                      <button onClick={() => deleteUser(user)} disabled={loading} className="text-xs text-red-600 underline" aria-label={`Delete user ${user.username}`}>
                        Delete
                      </button>
                    </td>
                  </tr>
                ))}
                {users.length === 0 && (
                  <tr>
                    <td colSpan={6} className="p-4 text-center text-gray-500" role="status">No users found</td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Assistant Capabilities & Tools</CardTitle>
        </CardHeader>
        <CardContent>
          <pre className="max-h-64 overflow-auto rounded bg-gray-50 p-3 text-xs dark:bg-slate-800 dark:text-gray-100" aria-label="Assistant capabilities JSON">
            {capabilities ? JSON.stringify(capabilities, null, 2) : 'Loading...'}
          </pre>
        </CardContent>
      </Card>

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
