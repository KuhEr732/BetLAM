<script>
"use client"

import { useState, useEffect, useRef } from "react"
import { Button } from "@/components/ui/button"
import { Card } from "@/components/ui/card"
import { Slot } from "@/components/slot"
import { WinEffect } from "@/components/win-effect"
import { LoseEffect } from "@/components/lose-effect"
import { Coins, Volume2, VolumeX } from "lucide-react"

export default function SlotMachine() {
  const [spinning, setSpinning] = useState(false)
  const [results, setResults] = useState<number[]>([0, 0, 0])
  const [credits, setCredits] = useState(100)
  const [win, setWin] = useState(false)
  const [winAmount, setWinAmount] = useState(0)
  const [muted, setMuted] = useState(false)
  const spinSound = useRef<HTMLAudioElement | null>(null)
  const winSound = useRef<HTMLAudioElement | null>(null)
  const loseSound = useRef<HTMLAudioElement | null>(null)

  useEffect(() => {
    // Create audio elements with proper error handling
    try {
      spinSound.current = new Audio()
      winSound.current = new Audio()
      loseSound.current = new Audio()

      // Set sources after creating the elements
      if (spinSound.current) spinSound.current.src = "/sounds/spin.mp3"
      if (winSound.current) winSound.current.src = "/sounds/win.mp3"
      if (loseSound.current) loseSound.current.src = "/sounds/lose.mp3"

      // Preload audio files
      spinSound.current.load()
      winSound.current.load()
      loseSound.current.load()
    } catch (e) {
      console.error("Error initializing audio:", e)
    }
  }, [])

  const playSound = (sound: HTMLAudioElement | null) => {
    if (sound && !muted) {
      try {
        sound.currentTime = 0
        const playPromise = sound.play()

        if (playPromise !== undefined) {
          playPromise.catch((e) => {
            console.log("Audio playback error (handled):", e)
            // Continue game flow even if audio fails
          })
        }
      } catch (e) {
        console.log("Audio error (caught):", e)
        // Continue game flow even if audio fails
      }
    }
  }

  const spin = async () => {
    if (spinning || credits < 10) return

    setCredits((prev) => prev - 10)
    setSpinning(true)
    setWin(false)
    setWinAmount(0)

    playSound(spinSound.current)

    // Generate random results with different timing for each reel
    const newResults = [...results]

    // Spin first reel
    await new Promise((resolve) => setTimeout(resolve, 300))
    newResults[0] = Math.floor(Math.random() * 5)
    setResults([...newResults])

    // Spin second reel
    await new Promise((resolve) => setTimeout(resolve, 600))
    newResults[1] = Math.floor(Math.random() * 5)
    setResults([...newResults])

    // Spin third reel
    await new Promise((resolve) => setTimeout(resolve, 900))
    newResults[2] = Math.floor(Math.random() * 5)
    setResults([...newResults])

    // Check for win
    setTimeout(() => {
      checkWin(newResults)
      setSpinning(false)
    }, 500)
  }

  const checkWin = (results: number[]) => {
    // Check if all symbols are the same
    if (results[0] === results[1] && results[1] === results[2]) {
      const multiplier =
        results[0] === 4 ? 50 : results[0] === 3 ? 25 : results[0] === 2 ? 15 : results[0] === 1 ? 10 : 5
      const amount = 10 * multiplier
      setWinAmount(amount)
      setCredits((prev) => prev + amount)
      setWin(true)
      playSound(winSound.current)
    } else if (results[0] === results[1] || results[1] === results[2]) {
      // Two matching symbols
      const amount = 5
      setWinAmount(amount)
      setCredits((prev) => prev + amount)
      setWin(true)
      playSound(winSound.current)
    } else {
      playSound(loseSound.current)
    }
  }

  const addCredits = () => {
    setCredits((prev) => prev + 100)
  }

  return (
    <main className="flex min-h-screen flex-col items-center justify-center p-4 bg-gradient-to-b from-purple-900 to-purple-950">
      <Card className="w-full max-w-md p-6 bg-gradient-to-b from-amber-800 to-amber-950 border-4 border-amber-600 rounded-xl shadow-2xl">
        <div className="flex justify-between items-center mb-4">
          <div className="flex items-center gap-2 bg-black px-3 py-1 rounded-md">
            <Coins className="text-yellow-400" size={20} />
            <span className="text-yellow-400 font-bold">{credits}</span>
          </div>
          <Button
            variant="outline"
            size="icon"
            className="bg-black text-white border-gray-700"
            onClick={() => setMuted(!muted)}
          >
            {muted ? <VolumeX size={18} /> : <Volume2 size={18} />}
          </Button>
        </div>

        <div className="relative bg-black p-4 rounded-lg mb-4 overflow-hidden">
          <div className="flex justify-center gap-2">
            <Slot value={results[0]} spinning={spinning} delay={0} />
            <Slot value={results[1]} spinning={spinning} delay={300} />
            <Slot value={results[2]} spinning={spinning} delay={600} />
          </div>

          {win && <WinEffect amount={winAmount} />}
          {!win && !spinning && credits < 10 && <LoseEffect />}
        </div>

        <div className="flex gap-2">
          <Button
            className="w-full bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold py-3 rounded-lg shadow-lg transition-all duration-200 hover:scale-105 disabled:opacity-50 disabled:hover:scale-100"
            onClick={spin}
            disabled={spinning || credits < 10}
          >
            {spinning ? "SPINNING..." : "SPIN (10 CREDITS)"}
          </Button>

          <Button
            variant="outline"
            className="bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white border-none"
            onClick={addCredits}
          >
            +100
          </Button>
        </div>
      </Card>
    </main>
  )
}

