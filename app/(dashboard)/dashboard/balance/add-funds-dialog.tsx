'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { formatCurrency } from '@/lib/utils'
import { Plus, CreditCard, Bitcoin, Loader2, CheckCircle } from 'lucide-react'
import { createClient } from '@/lib/supabase/client'

const PRESET_AMOUNTS = [10, 25, 50, 100, 250, 500]

export function AddFundsDialog() {
  const router = useRouter()
  const [open, setOpen] = useState(false)
  const [amount, setAmount] = useState('')
  const [method, setMethod] = useState<'card' | 'crypto'>('card')
  const [loading, setLoading] = useState(false)
  const [success, setSuccess] = useState(false)

  const handleAddFunds = async () => {
    const parsedAmount = parseFloat(amount)
    if (isNaN(parsedAmount) || parsedAmount < 5) {
      return
    }

    setLoading(true)

    try {
      const supabase = createClient()
      const { data: { user } } = await supabase.auth.getUser()

      if (!user) return

      // Get current balance
      const { data: profile } = await supabase
        .from('profiles')
        .select('balance')
        .eq('id', user.id)
        .single()

      // Update balance (in real app, this would go through a payment processor)
      const { error } = await supabase
        .from('profiles')
        .update({ 
          balance: (profile?.balance || 0) + parsedAmount 
        })
        .eq('id', user.id)

      if (error) throw error

      setSuccess(true)
      setTimeout(() => {
        setOpen(false)
        setSuccess(false)
        setAmount('')
        router.refresh()
      }, 1500)
    } catch (err) {
      console.error('Error adding funds:', err)
    } finally {
      setLoading(false)
    }
  }

  const parsedAmount = parseFloat(amount) || 0

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button>
          <Plus className="h-4 w-4 mr-2" />
          Add Funds
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-[425px]">
        {success ? (
          <div className="py-8 text-center">
            <CheckCircle className="h-16 w-16 text-success mx-auto mb-4" />
            <h3 className="text-lg font-semibold text-foreground">Funds Added!</h3>
            <p className="text-muted-foreground mt-2">
              {formatCurrency(parsedAmount)} has been added to your balance.
            </p>
          </div>
        ) : (
          <>
            <DialogHeader>
              <DialogTitle>Add Funds</DialogTitle>
              <DialogDescription>
                Add money to your account balance to make purchases.
              </DialogDescription>
            </DialogHeader>

            <div className="space-y-6 py-4">
              {/* Amount Input */}
              <div className="space-y-2">
                <Label htmlFor="amount">Amount (USD)</Label>
                <Input
                  id="amount"
                  type="number"
                  min="5"
                  step="0.01"
                  value={amount}
                  onChange={(e) => setAmount(e.target.value)}
                  placeholder="Enter amount"
                />
                <p className="text-xs text-muted-foreground">Minimum: $5.00</p>
              </div>

              {/* Preset Amounts */}
              <div className="grid grid-cols-3 gap-2">
                {PRESET_AMOUNTS.map((preset) => (
                  <Button
                    key={preset}
                    type="button"
                    variant={amount === preset.toString() ? "default" : "outline"}
                    onClick={() => setAmount(preset.toString())}
                  >
                    ${preset}
                  </Button>
                ))}
              </div>

              {/* Payment Method */}
              <div className="space-y-2">
                <Label>Payment Method</Label>
                <div className="grid grid-cols-2 gap-2">
                  <Button
                    type="button"
                    variant={method === 'card' ? "default" : "outline"}
                    onClick={() => setMethod('card')}
                    className="justify-start"
                  >
                    <CreditCard className="h-4 w-4 mr-2" />
                    Card
                  </Button>
                  <Button
                    type="button"
                    variant={method === 'crypto' ? "default" : "outline"}
                    onClick={() => setMethod('crypto')}
                    className="justify-start"
                  >
                    <Bitcoin className="h-4 w-4 mr-2" />
                    Crypto
                  </Button>
                </div>
              </div>

              {/* Summary */}
              {parsedAmount >= 5 && (
                <div className="rounded-lg bg-muted p-4 space-y-2">
                  <div className="flex justify-between text-sm">
                    <span className="text-muted-foreground">Amount</span>
                    <span>{formatCurrency(parsedAmount)}</span>
                  </div>
                  <div className="flex justify-between text-sm">
                    <span className="text-muted-foreground">Processing Fee</span>
                    <span>$0.00</span>
                  </div>
                  <div className="flex justify-between font-semibold pt-2 border-t border-border">
                    <span>Total</span>
                    <span className="text-primary">{formatCurrency(parsedAmount)}</span>
                  </div>
                </div>
              )}
            </div>

            <DialogFooter>
              <Button variant="outline" onClick={() => setOpen(false)}>
                Cancel
              </Button>
              <Button
                onClick={handleAddFunds}
                disabled={loading || parsedAmount < 5}
              >
                {loading ? (
                  <>
                    <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                    Processing...
                  </>
                ) : (
                  <>Add {formatCurrency(parsedAmount)}</>
                )}
              </Button>
            </DialogFooter>
          </>
        )}
      </DialogContent>
    </Dialog>
  )
}
