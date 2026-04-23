'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { Button } from '@/components/ui/button'
import { Loader2, Store } from 'lucide-react'
import { createClient } from '@/lib/supabase/client'

export function BecomeSellerButton() {
  const router = useRouter()
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const handleBecomeSeller = async () => {
    setLoading(true)
    setError(null)

    try {
      const supabase = createClient()
      const { data: { user } } = await supabase.auth.getUser()

      if (!user) {
        setError('Please sign in to continue')
        return
      }

      const { error: updateError } = await supabase
        .from('profiles')
        .update({ role: 'seller' })
        .eq('id', user.id)

      if (updateError) throw updateError

      router.push('/dashboard/products')
      router.refresh()
    } catch (err) {
      console.error('Error upgrading to seller:', err)
      setError(err instanceof Error ? err.message : 'Failed to upgrade account')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="space-y-2">
      {error && (
        <p className="text-sm text-destructive text-center">{error}</p>
      )}
      <Button 
        onClick={handleBecomeSeller} 
        disabled={loading}
        className="w-full"
        size="lg"
      >
        {loading ? (
          <Loader2 className="h-4 w-4 mr-2 animate-spin" />
        ) : (
          <Store className="h-4 w-4 mr-2" />
        )}
        Become a Seller
      </Button>
    </div>
  )
}
