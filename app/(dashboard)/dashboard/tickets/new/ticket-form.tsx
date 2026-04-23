'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { formatDateTime } from '@/lib/utils'
import { Loader2 } from 'lucide-react'
import { createClient } from '@/lib/supabase/client'

interface Order {
  id: string
  created_at: string
  product: {
    title: string
  } | null
}

interface TicketFormProps {
  orders: Order[]
  preselectedOrderId?: string
}

const categories = [
  { value: 'order', label: 'Order Issue' },
  { value: 'payment', label: 'Payment Problem' },
  { value: 'account', label: 'Account Help' },
  { value: 'report', label: 'Report User/Product' },
  { value: 'other', label: 'Other' },
]

const priorities = [
  { value: 'low', label: 'Low' },
  { value: 'normal', label: 'Normal' },
  { value: 'high', label: 'High' },
  { value: 'urgent', label: 'Urgent' },
]

export function TicketForm({ orders, preselectedOrderId }: TicketFormProps) {
  const router = useRouter()
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const [subject, setSubject] = useState('')
  const [category, setCategory] = useState(preselectedOrderId ? 'order' : '')
  const [priority, setPriority] = useState('normal')
  const [orderId, setOrderId] = useState(preselectedOrderId || '')
  const [message, setMessage] = useState('')

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setLoading(true)
    setError(null)

    try {
      const supabase = createClient()
      const { data: { user } } = await supabase.auth.getUser()

      if (!user) {
        setError('Please sign in to continue')
        return
      }

      // Create ticket
      const { data: ticket, error: ticketError } = await supabase
        .from('tickets')
        .insert({
          user_id: user.id,
          order_id: orderId || null,
          subject: subject.trim(),
          category,
          priority,
          status: 'open',
        })
        .select()
        .single()

      if (ticketError) throw ticketError

      // Create initial message
      const { error: messageError } = await supabase
        .from('ticket_messages')
        .insert({
          ticket_id: ticket.id,
          sender_id: user.id,
          message: message.trim(),
          is_staff: false,
        })

      if (messageError) throw messageError

      router.push(`/dashboard/tickets/${ticket.id}`)
      router.refresh()
    } catch (err) {
      console.error('Error creating ticket:', err)
      setError(err instanceof Error ? err.message : 'Failed to create ticket')
    } finally {
      setLoading(false)
    }
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      {error && (
        <div className="p-4 rounded-lg bg-destructive/10 border border-destructive/20 text-destructive text-sm">
          {error}
        </div>
      )}

      <div className="space-y-2">
        <Label htmlFor="subject">Subject *</Label>
        <Input
          id="subject"
          value={subject}
          onChange={(e) => setSubject(e.target.value)}
          placeholder="Brief description of your issue"
          required
        />
      </div>

      <div className="grid gap-4 md:grid-cols-2">
        <div className="space-y-2">
          <Label htmlFor="category">Category *</Label>
          <Select value={category} onValueChange={setCategory} required>
            <SelectTrigger>
              <SelectValue placeholder="Select category" />
            </SelectTrigger>
            <SelectContent>
              {categories.map((cat) => (
                <SelectItem key={cat.value} value={cat.value}>
                  {cat.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        <div className="space-y-2">
          <Label htmlFor="priority">Priority</Label>
          <Select value={priority} onValueChange={setPriority}>
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {priorities.map((p) => (
                <SelectItem key={p.value} value={p.value}>
                  {p.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      </div>

      {(category === 'order' || orders.length > 0) && (
        <div className="space-y-2">
          <Label htmlFor="order">Related Order (Optional)</Label>
          <Select value={orderId} onValueChange={setOrderId}>
            <SelectTrigger>
              <SelectValue placeholder="Select an order if applicable" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="">None</SelectItem>
              {orders.map((order) => (
                <SelectItem key={order.id} value={order.id}>
                  {order.product?.title || 'Order'} - {formatDateTime(order.created_at)}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      )}

      <div className="space-y-2">
        <Label htmlFor="message">Message *</Label>
        <Textarea
          id="message"
          value={message}
          onChange={(e) => setMessage(e.target.value)}
          placeholder="Describe your issue in detail. Include any relevant information like order IDs, error messages, or steps to reproduce the problem."
          rows={6}
          required
        />
      </div>

      <div className="flex gap-4">
        <Button type="submit" disabled={loading || !subject || !category || !message}>
          {loading && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
          Submit Ticket
        </Button>
        <Button
          type="button"
          variant="outline"
          onClick={() => router.push('/dashboard/tickets')}
        >
          Cancel
        </Button>
      </div>
    </form>
  )
}
