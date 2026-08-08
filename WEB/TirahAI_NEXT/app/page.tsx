export default function Home() {
  return (
    <main className="flex min-h-screen flex-col items-center justify-center p-24">
      <h1 className="text-4xl font-bold">TirahAi</h1>
      <p className="mt-4 text-lg text-gray-600">Management Dashboard</p>
      <div className="mt-8 flex gap-4">
        <a href="/login" className="rounded bg-primary px-4 py-2 text-white">Login</a>
        <a href="/register" className="rounded border px-4 py-2">Register</a>
      </div>
    </main>
  )
}
