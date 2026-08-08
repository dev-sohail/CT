import Link from 'next/link'

export default function NotFound() {
  return (
    <div className="flex min-h-screen flex-col items-center justify-center p-6">
      <h2 className="text-4xl font-bold">404</h2>
      <p className="mt-2 text-gray-600">Page not found</p>
      <Link href="/dashboard" className="mt-4 rounded bg-primary px-4 py-2 text-white">
        Go back home
      </Link>
    </div>
  )
}
