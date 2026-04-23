'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { Button } from '@/components/ui/button'
import { CheckCircle, XCircle, Loader2 } from 'lucide-react'
import { createClient } from '@/lib/supabase/client'

interface WithdrawalActionsProps {
  withdrawalId: string
  userId: string
  amount: number
  userBalance: number
}

export function WithdrawalActions({ withdrawalId, userId, amount, userBalance }: WithdrawalActionsProps) {
  const router = useRouter()
  const [loading, setLoading] = useState<string | null>(null)

  const handleAction = async (action: 'approve' | 'reject') => {
    setLoading(action)

    try {
      const supabase = createClient()
      const { data: { user } } = await supabase.auth.getUser()

      if (action === 'approve') {
        // Deduct from user balance
        await supabase
          .from('profiles')
          .update({ balance: userBalance - amount })
          .eq('id', userId)

        // Update withdrawal status
        await supabase
          .from('withdrawal_requests')
          .update({ 
            status: 'completed',
            processed_at: new Date().toISOString(),
            processed_by: user?.id,
          })
          .eq('id', withdrawalId)
      } else {
        // Just update withdrawal status
        await supabase
          .from('withdrawal_requests')
          .update({ 
            status: 'rejected',
            processed_at: new Date().toISOString(),
            processed_by: user?.id,
            admin_notes: 'Request rejected by admin',
          })
          .eq('id', withdrawalId)
      }

      router.refresh()
    } catch (err) {
      console.error('Error:', err)
    } finally {
      setLoading(null)
    }
  }

  return (
    <div className="flex items-center gap-2">
      <Button
        size="sm"
        onClick={() => handleAction('approve')}
        disabled={loading !== null || userBalance < amount}
      >
        {loading === 'approve' ? (
          <Loader2 className="h-4 w-4 mr-1 animate-spin" />
        ) : (
          <CheckCircle className="h-4 w-4 mr-1" />
        )}
        Approve
      </Button>
      <Button
        size="sm"
        variant="destructive"
        onClick={() => handleAction('reject')}
        disabled={loading !== null}
      >
        {loading === 'reject' ? (
          <Loader2 className="h-4 w-4 mr-1 animate-spin" />
        ) : (
          <XCircle className="h-4 w-4 mr-1" />
        )}
        Reject
      </Button>
    </div>
  )
}
