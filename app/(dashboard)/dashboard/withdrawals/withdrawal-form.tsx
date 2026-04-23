'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { formatCurrency } from '@/lib/utils'
import { Loader2, CheckCircle } from 'lucide-react'
import { createClient } from '@/lib/supabase/client'

interface WithdrawalFormProps {
  availableBalance: number
}

export function WithdrawalForm({ availableBalance }: WithdrawalFormProps) {
  const router = useRouter()
  const [loading, setLoading] = useState(false)
  const [success, setSuccess] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const [amount, setAmount] = useState('')
  const [method, setMethod] = useState('')
  const [details, setDetails] = useState({
    email: '',
    address: '',
    accountNumber: '',
    routingNumber: '',
  })

  const parsedAmount = parseFloat(amount) || 0
  const isValid = parsedAmount >= 10 && parsedAmount <= availableBalance && method

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!isValid) return

    setLoading(true)
    setError(null)

    try {
      const supabase = createClient()
      const { data: { user } } = await supabase.auth.getUser()

      if (!user) {
        setError('Please sign in to continue')
        return
      }

      const withdrawalDetails = method === 'paypal' 
        ? { paypal_email: details.email }
        : method === 'crypto'
        ? { wallet_address: details.address }
        : { 
            account_number: details.accountNumber,
            routing_number: details.routingNumber,
          }

      const { error: insertError } = await supabase
        .from('withdrawal_requests')
        .insert({
          user_id: user.id,
          amount: parsedAmount,
          method,
          details: withdrawalDetails,
          status: 'pending',
        })

      if (insertError) throw insertError

      setSuccess(true)
      setTimeout(() => {
        router.refresh()
        setSuccess(false)
        setAmount('')
        setMethod('')
        setDetails({ email: '', address: '', accountNumber: '', routingNumber: '' })
      }, 2000)
    } catch (err) {
      console.error('Error creating withdrawal:', err)
      setError(err instanceof Error ? err.message : 'Failed to submit withdrawal request')
    } finally {
      setLoading(false)
    }
  }

  if (success) {
    return (
      <div className="py-8 text-center">
        <CheckCircle className="h-16 w-16 text-success mx-auto mb-4" />
        <h3 className="text-lg font-semibold text-foreground">Request Submitted!</h3>
        <p className="text-muted-foreground mt-2">
          Your withdrawal request for {formatCurrency(parsedAmount)} has been submitted.
        </p>
      </div>
    )
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      {error && (
        <div className="p-4 rounded-lg bg-destructive/10 border border-destructive/20 text-destructive text-sm">
          {error}
        </div>
      )}

      <div className="grid gap-4 md:grid-cols-2">
        <div className="space-y-2">
          <Label htmlFor="amount">Amount (USD)</Label>
          <Input
            id="amount"
            type="number"
            min="10"
            max={availableBalance}
            step="0.01"
            value={amount}
            onChange={(e) => setAmount(e.target.value)}
            placeholder="Enter amount"
          />
          <p className="text-xs text-muted-foreground">
            Available: {formatCurrency(availableBalance)}
          </p>
        </div>

        <div className="space-y-2">
          <Label htmlFor="method">Withdrawal Method</Label>
          <Select value={method} onValueChange={setMethod}>
            <SelectTrigger>
              <SelectValue placeholder="Select method" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="paypal">PayPal</SelectItem>
              <SelectItem value="crypto">Cryptocurrency</SelectItem>
              <SelectItem value="bank">Bank Transfer</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      {method === 'paypal' && (
        <div className="space-y-2">
          <Label htmlFor="paypal-email">PayPal Email</Label>
          <Input
            id="paypal-email"
            type="email"
            value={details.email}
            onChange={(e) => setDetails({ ...details, email: e.target.value })}
            placeholder="your@email.com"
            required
          />
        </div>
      )}

      {method === 'crypto' && (
        <div className="space-y-2">
          <Label htmlFor="wallet">Wallet Address (USDT TRC20)</Label>
          <Input
            id="wallet"
            value={details.address}
            onChange={(e) => setDetails({ ...details, address: e.target.value })}
            placeholder="Enter your wallet address"
            required
          />
        </div>
      )}

      {method === 'bank' && (
        <div className="grid gap-4 md:grid-cols-2">
          <div className="space-y-2">
            <Label htmlFor="account">Account Number</Label>
            <Input
              id="account"
              value={details.accountNumber}
              onChange={(e) => setDetails({ ...details, accountNumber: e.target.value })}
              placeholder="Account number"
              required
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="routing">Routing Number</Label>
            <Input
              id="routing"
              value={details.routingNumber}
              onChange={(e) => setDetails({ ...details, routingNumber: e.target.value })}
              placeholder="Routing number"
              required
            />
          </div>
        </div>
      )}

      {parsedAmount >= 10 && method && (
        <div className="rounded-lg bg-muted p-4 space-y-2">
          <div className="flex justify-between text-sm">
            <span className="text-muted-foreground">Withdrawal Amount</span>
            <span>{formatCurrency(parsedAmount)}</span>
          </div>
          <div className="flex justify-between text-sm">
            <span className="text-muted-foreground">Processing Fee</span>
            <span>$0.00</span>
          </div>
          <div className="flex justify-between font-semibold pt-2 border-t border-border">
            <span>You&apos;ll Receive</span>
            <span className="text-success">{formatCurrency(parsedAmount)}</span>
          </div>
        </div>
      )}

      <Button type="submit" disabled={loading || !isValid}>
        {loading ? (
          <>
            <Loader2 className="h-4 w-4 mr-2 animate-spin" />
            Submitting...
          </>
        ) : (
          'Request Withdrawal'
        )}
      </Button>
    </form>
  )
}
