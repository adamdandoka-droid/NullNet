'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { Button } from '@/components/ui/button'
import { CheckCircle, XCircle, Eye, Loader2 } from 'lucide-react'
import { createClient } from '@/lib/supabase/client'
import Link from 'next/link'

interface ProductActionsProps {
  productId: string
  isApproved: boolean
  isActive?: boolean
}

export function ProductActions({ productId, isApproved, isActive }: ProductActionsProps) {
  const router = useRouter()
  const [loading, setLoading] = useState<string | null>(null)

  const handleAction = async (action: 'approve' | 'reject' | 'toggle') => {
    setLoading(action)

    try {
      const supabase = createClient()

      if (action === 'approve') {
        await supabase
          .from('products')
          .update({ is_approved: true })
          .eq('id', productId)
      } else if (action === 'reject') {
        await supabase
          .from('products')
          .delete()
          .eq('id', productId)
      } else if (action === 'toggle') {
        await supabase
          .from('products')
          .update({ is_active: !isActive })
          .eq('id', productId)
      }

      router.refresh()
    } catch (err) {
      console.error('Error:', err)
    } finally {
      setLoading(null)
    }
  }

  if (!isApproved) {
    return (
      <div className="flex items-center gap-2">
        <Button variant="outline" size="sm" asChild>
          <Link href={`/products/${productId}`} target="_blank">
            <Eye className="h-4 w-4 mr-1" />
            View
          </Link>
        </Button>
        <Button
          size="sm"
          onClick={() => handleAction('approve')}
          disabled={loading !== null}
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

  return (
    <div className="flex items-center gap-2">
      <Button variant="outline" size="sm" asChild>
        <Link href={`/products/${productId}`} target="_blank">
          <Eye className="h-4 w-4 mr-1" />
          View
        </Link>
      </Button>
      <Button
        size="sm"
        variant={isActive ? "destructive" : "default"}
        onClick={() => handleAction('toggle')}
        disabled={loading !== null}
      >
        {loading === 'toggle' && <Loader2 className="h-4 w-4 mr-1 animate-spin" />}
        {isActive ? 'Deactivate' : 'Activate'}
      </Button>
    </div>
  )
}
