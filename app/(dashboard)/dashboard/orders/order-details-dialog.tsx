'use client'

import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import { Badge } from '@/components/ui/badge'
import { formatCurrency, formatDateTime } from '@/lib/utils'
import { Eye, Copy, CheckCircle } from 'lucide-react'
import { useState } from 'react'

interface Order {
  id: string
  quantity: number
  unit_price: number
  total_price: number
  status: string
  delivery_data: string | null
  created_at: string
  completed_at: string | null
  product: {
    id: string
    title: string
    delivery_type: string
  } | null
  buyer: {
    username: string
  } | null
  seller: {
    username: string
  } | null
}

interface OrderDetailsDialogProps {
  order: Order
  type: 'purchase' | 'sale'
}

const statusColors: Record<string, "default" | "success" | "warning" | "destructive" | "secondary"> = {
  pending: "warning",
  completed: "success",
  cancelled: "secondary",
  disputed: "destructive",
  refunded: "secondary",
}

export function OrderDetailsDialog({ order, type }: OrderDetailsDialogProps) {
  const [copied, setCopied] = useState(false)

  const copyToClipboard = async (text: string) => {
    try {
      await navigator.clipboard.writeText(text)
      setCopied(true)
      setTimeout(() => setCopied(false), 2000)
    } catch (err) {
      console.error('Failed to copy:', err)
    }
  }

  return (
    <Dialog>
      <DialogTrigger asChild>
        <Button variant="outline" size="sm">
          <Eye className="h-4 w-4 mr-1" />
          Details
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle>Order Details</DialogTitle>
          <DialogDescription>
            Order #{order.id.slice(0, 8)}
          </DialogDescription>
        </DialogHeader>
        
        <div className="space-y-4 py-4">
          {/* Product Info */}
          <div className="rounded-lg bg-muted p-4">
            <h4 className="font-medium text-foreground mb-2">
              {order.product?.title || "Product"}
            </h4>
            <div className="grid grid-cols-2 gap-2 text-sm">
              <div>
                <span className="text-muted-foreground">Quantity:</span>
                <span className="ml-2 text-foreground">{order.quantity}</span>
              </div>
              <div>
                <span className="text-muted-foreground">Unit Price:</span>
                <span className="ml-2 text-foreground">{formatCurrency(order.unit_price)}</span>
              </div>
              <div>
                <span className="text-muted-foreground">Total:</span>
                <span className="ml-2 font-semibold text-primary">{formatCurrency(order.total_price)}</span>
              </div>
              <div>
                <span className="text-muted-foreground">Status:</span>
                <Badge variant={statusColors[order.status]} className="ml-2">
                  {order.status}
                </Badge>
              </div>
            </div>
          </div>

          {/* Parties */}
          <div className="grid grid-cols-2 gap-4 text-sm">
            <div>
              <span className="text-muted-foreground block mb-1">
                {type === 'purchase' ? 'Seller' : 'Buyer'}
              </span>
              <span className="font-medium text-foreground">
                {type === 'purchase' ? order.seller?.username : order.buyer?.username}
              </span>
            </div>
            <div>
              <span className="text-muted-foreground block mb-1">Delivery Type</span>
              <span className="font-medium text-foreground capitalize">
                {order.product?.delivery_type || 'Manual'}
              </span>
            </div>
          </div>

          {/* Timestamps */}
          <div className="grid grid-cols-2 gap-4 text-sm">
            <div>
              <span className="text-muted-foreground block mb-1">Ordered</span>
              <span className="text-foreground">{formatDateTime(order.created_at)}</span>
            </div>
            {order.completed_at && (
              <div>
                <span className="text-muted-foreground block mb-1">Completed</span>
                <span className="text-foreground">{formatDateTime(order.completed_at)}</span>
              </div>
            )}
          </div>

          {/* Delivery Data (only for purchases with instant delivery) */}
          {type === 'purchase' && order.delivery_data && order.status === 'completed' && (
            <div className="space-y-2">
              <div className="flex items-center justify-between">
                <span className="text-sm font-medium text-foreground">Delivery Data</span>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => copyToClipboard(order.delivery_data || '')}
                >
                  {copied ? (
                    <CheckCircle className="h-4 w-4 text-success" />
                  ) : (
                    <Copy className="h-4 w-4" />
                  )}
                  <span className="ml-1">{copied ? 'Copied!' : 'Copy'}</span>
                </Button>
              </div>
              <div className="p-3 rounded-lg bg-muted font-mono text-sm break-all max-h-48 overflow-y-auto">
                {order.delivery_data}
              </div>
            </div>
          )}

          {/* Pending delivery message */}
          {type === 'purchase' && order.status === 'pending' && !order.delivery_data && (
            <div className="rounded-lg bg-warning/10 border border-warning/20 p-4 text-sm">
              <p className="text-warning font-medium">Awaiting Delivery</p>
              <p className="text-muted-foreground mt-1">
                The seller will deliver your product manually. You&apos;ll be notified when it&apos;s ready.
              </p>
            </div>
          )}
        </div>
      </DialogContent>
    </Dialog>
  )
}
