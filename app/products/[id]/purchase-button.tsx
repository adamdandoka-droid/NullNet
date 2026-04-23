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
import { Loader2, ShoppingCart, CheckCircle, AlertCircle } from 'lucide-react'

interface PurchaseButtonProps {
  productId: string
  productTitle: string
  price: number
}

export function PurchaseButton({ productId, productTitle, price }: PurchaseButtonProps) {
  const router = useRouter()
  const [open, setOpen] = useState(false)
  const [quantity, setQuantity] = useState(1)
  const [loading, setLoading] = useState(false)
  const [result, setResult] = useState<{
    success: boolean
    message: string
    deliveryData?: string
    orderId?: string
  } | null>(null)

  const totalPrice = price * quantity

  const handlePurchase = async () => {
    setLoading(true)
    setResult(null)

    try {
      const response = await fetch('/api/purchase', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          productId,
          quantity,
        }),
      })

      const data = await response.json()

      if (!response.ok) {
        setResult({
          success: false,
          message: data.error || 'Purchase failed. Please try again.',
        })
        return
      }

      setResult({
        success: true,
        message: 'Purchase successful!',
        deliveryData: data.deliveryData,
        orderId: data.orderId,
      })
    } catch {
      setResult({
        success: false,
        message: 'Network error. Please try again.',
      })
    } finally {
      setLoading(false)
    }
  }

  const handleClose = () => {
    if (result?.success) {
      router.push('/dashboard/orders')
    } else {
      setOpen(false)
      setResult(null)
    }
  }

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button className="w-full" size="lg">
          <ShoppingCart className="h-4 w-4 mr-2" />
          Buy Now
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-[425px]">
        {!result ? (
          <>
            <DialogHeader>
              <DialogTitle>Confirm Purchase</DialogTitle>
              <DialogDescription>
                You are about to purchase: {productTitle}
              </DialogDescription>
            </DialogHeader>
            <div className="space-y-4 py-4">
              <div className="space-y-2">
                <Label htmlFor="quantity">Quantity</Label>
                <Input
                  id="quantity"
                  type="number"
                  min={1}
                  max={10}
                  value={quantity}
                  onChange={(e) => setQuantity(Math.max(1, parseInt(e.target.value) || 1))}
                />
              </div>
              <div className="rounded-lg bg-muted p-4 space-y-2">
                <div className="flex justify-between text-sm">
                  <span className="text-muted-foreground">Price per unit</span>
                  <span>{formatCurrency(price)}</span>
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-muted-foreground">Quantity</span>
                  <span>x{quantity}</span>
                </div>
                <div className="flex justify-between font-semibold pt-2 border-t border-border">
                  <span>Total</span>
                  <span className="text-primary">{formatCurrency(totalPrice)}</span>
                </div>
              </div>
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setOpen(false)}>
                Cancel
              </Button>
              <Button onClick={handlePurchase} disabled={loading}>
                {loading ? (
                  <>
                    <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                    Processing...
                  </>
                ) : (
                  <>Confirm Purchase</>
                )}
              </Button>
            </DialogFooter>
          </>
        ) : result.success ? (
          <>
            <DialogHeader>
              <div className="mx-auto mb-4">
                <CheckCircle className="h-16 w-16 text-success" />
              </div>
              <DialogTitle className="text-center">Purchase Complete!</DialogTitle>
              <DialogDescription className="text-center">
                Your order has been processed successfully.
              </DialogDescription>
            </DialogHeader>
            {result.deliveryData && (
              <div className="py-4">
                <Label>Your Product</Label>
                <div className="mt-2 p-4 rounded-lg bg-muted font-mono text-sm break-all">
                  {result.deliveryData}
                </div>
                <p className="text-xs text-muted-foreground mt-2">
                  This information is also available in your orders.
                </p>
              </div>
            )}
            <DialogFooter>
              <Button onClick={handleClose} className="w-full">
                View My Orders
              </Button>
            </DialogFooter>
          </>
        ) : (
          <>
            <DialogHeader>
              <div className="mx-auto mb-4">
                <AlertCircle className="h-16 w-16 text-destructive" />
              </div>
              <DialogTitle className="text-center">Purchase Failed</DialogTitle>
              <DialogDescription className="text-center">
                {result.message}
              </DialogDescription>
            </DialogHeader>
            <DialogFooter>
              <Button variant="outline" onClick={handleClose} className="w-full">
                Try Again
              </Button>
            </DialogFooter>
          </>
        )}
      </DialogContent>
    </Dialog>
  )
}
