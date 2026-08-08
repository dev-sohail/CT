'use client'

import { useEffect, useRef, useState } from 'react'
import { useRouter } from 'next/navigation'

type Step = {
  target: string
  title: string
  content: string
}

const STORAGE_KEY = 'tirahai_tour_seen'

const steps: Step[] = [
  {
    target: '#chat-input',
    title: 'Assistant',
    content: 'Chat with TirahAI here. Ask anything and get instant answers.',
  },
  {
    target: '#tools-panel',
    title: 'Tools',
    content: 'Execute tools like weather, translate, and search directly from the dashboard.',
  },
  {
    target: '#history-panel',
    title: 'History',
    content: 'Pick up where you left off. Your past conversations are saved here.',
  },
  {
    target: '#theme-toggle',
    title: 'Theme',
    content: 'Toggle between light and dark mode anytime.',
  },
]

export default function OnboardingTour() {
  const [active, setActive] = useState(false)
  const [index, setIndex] = useState(0)
  const router = useRouter()
  const tooltipRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    const seen = typeof window !== 'undefined' ? localStorage.getItem(STORAGE_KEY) : null
    if (!seen) {
      setActive(true)
    }
  }, [])

  useEffect(() => {
    if (!active) return
    const handleKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') finish()
      if (e.key === 'ArrowRight') next()
      if (e.key === 'ArrowLeft') prev()
    }
    window.addEventListener('keydown', handleKey)
    return () => window.removeEventListener('keydown', handleKey)
  }, [active, index])

  if (!active) return null

  const step = steps[index]
  const target = typeof document !== 'undefined' ? document.querySelector<HTMLElement>(step?.target || '') : null

  const finish = () => {
    setActive(false)
    localStorage.setItem(STORAGE_KEY, '1')
  }

  const next = () => {
    if (index >= steps.length - 1) {
      finish()
    } else {
      setIndex(i => i + 1)
    }
  }

  const prev = () => {
    if (index > 0) setIndex(i => i - 1)
  }

  if (!step) return null

  return (
    <div className="fixed inset-0 z-50">
      <div className="absolute inset-0 bg-black/40" onClick={finish} />
      {target ? (
        <div
          id="tour-highlight"
          ref={tooltipRef}
          className="absolute z-50 rounded-lg border bg-white p-4 shadow-xl dark:bg-slate-800"
          style={{
            top: target.getBoundingClientRect().top - 10 + window.scrollY,
            left: Math.min(target.getBoundingClientRect().left + window.scrollX, window.innerWidth - 320),
            width: 300,
          }}
        >
          <h3 className="font-semibold">{step.title}</h3>
          <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">{step.content}</p>
          <div className="mt-4 flex items-center justify-between">
            <span className="text-xs text-gray-500">
              {index + 1} / {steps.length}
            </span>
            <div className="flex gap-2">
              {index > 0 && (
                <button onClick={prev} className="rounded border px-3 py-1 text-sm">
                  Back
                </button>
              )}
              <button onClick={next} className="rounded bg-primary px-3 py-1 text-sm text-white">
                {index >= steps.length - 1 ? 'Finish' : 'Next'}
              </button>
            </div>
          </div>
        </div>
      ) : (
        <div className="flex items-center justify-center h-full">
          <div className="rounded-lg border bg-white p-6 shadow-xl dark:bg-slate-800">
            <h3 className="font-semibold">{step.title}</h3>
            <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">{step.content}</p>
            <div className="mt-4 flex justify-end gap-2">
              <button onClick={prev} className="rounded border px-3 py-1 text-sm">Back</button>
              <button onClick={next} className="rounded bg-primary px-3 py-1 text-sm text-white">Next</button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
