import { createClient } from "@/lib/supabase/server"
import { redirect, notFound } from "next/navigation"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { formatDateTime } from "@/lib/utils"
import Link from "next/link"
import { ArrowLeft, Clock, CheckCircle, AlertCircle, Loader2, User, Shield } from "lucide-react"
import { TicketMessageForm } from "./ticket-message-form"

const statusConfig = {
  open: { label: "Open", variant: "warning" as const, icon: Clock },
  in_progress: { label: "In Progress", variant: "info" as const, icon: Loader2 },
  waiting: { label: "Waiting", variant: "secondary" as const, icon: AlertCircle },
  resolved: { label: "Resolved", variant: "success" as const, icon: CheckCircle },
  closed: { label: "Closed", variant: "secondary" as const, icon: CheckCircle },
}

const priorityColors = {
  low: "secondary",
  normal: "default",
  high: "warning",
  urgent: "destructive",
} as const

interface TicketPageProps {
  params: Promise<{ id: string }>
}

export default async function TicketPage({ params }: TicketPageProps) {
  const { id } = await params
  const supabase = await createClient()
  const { data: { user } } = await supabase.auth.getUser()

  if (!user) {
    redirect("/auth/login")
  }

  // Get ticket
  const { data: ticket, error } = await supabase
    .from("tickets")
    .select(`
      *,
      order:orders(id, product:products(title))
    `)
    .eq("id", id)
    .eq("user_id", user.id)
    .single()

  if (error || !ticket) {
    notFound()
  }

  // Get messages
  const { data: messages } = await supabase
    .from("ticket_messages")
    .select(`
      *,
      sender:profiles(username, role)
    `)
    .eq("ticket_id", id)
    .order("created_at", { ascending: true })

  const status = statusConfig[ticket.status as keyof typeof statusConfig] || statusConfig.open
  const StatusIcon = status.icon
  const isClosed = ticket.status === "closed" || ticket.status === "resolved"

  return (
    <div className="space-y-6">
      <div>
        <Link 
          href="/dashboard/tickets" 
          className="inline-flex items-center gap-2 text-muted-foreground hover:text-foreground mb-4"
        >
          <ArrowLeft className="h-4 w-4" />
          Back to Tickets
        </Link>
        <div className="flex flex-col sm:flex-row sm:items-center gap-4">
          <h1 className="text-2xl font-bold text-foreground flex-1">
            {ticket.subject}
          </h1>
          <div className="flex items-center gap-2">
            <Badge variant={status.variant} className="flex items-center gap-1">
              <StatusIcon className="h-3 w-3" />
              {status.label}
            </Badge>
            <Badge variant={priorityColors[ticket.priority as keyof typeof priorityColors]}>
              {ticket.priority}
            </Badge>
          </div>
        </div>
      </div>

      {/* Ticket Info */}
      <Card>
        <CardHeader>
          <CardTitle className="text-sm font-medium">Ticket Information</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 text-sm">
            <div>
              <span className="text-muted-foreground">Category</span>
              <p className="font-medium text-foreground capitalize">{ticket.category}</p>
            </div>
            <div>
              <span className="text-muted-foreground">Created</span>
              <p className="font-medium text-foreground">{formatDateTime(ticket.created_at)}</p>
            </div>
            <div>
              <span className="text-muted-foreground">Last Updated</span>
              <p className="font-medium text-foreground">{formatDateTime(ticket.updated_at)}</p>
            </div>
            {ticket.order && (
              <div>
                <span className="text-muted-foreground">Related Order</span>
                <p className="font-medium text-foreground">{ticket.order.product?.title || 'Order'}</p>
              </div>
            )}
          </div>
        </CardContent>
      </Card>

      {/* Messages */}
      <Card>
        <CardHeader>
          <CardTitle>Conversation</CardTitle>
          <CardDescription>
            {messages?.length || 0} message{(messages?.length || 0) !== 1 ? 's' : ''}
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          {messages && messages.length > 0 ? (
            <div className="space-y-4">
              {messages.map((msg) => (
                <div
                  key={msg.id}
                  className={`p-4 rounded-lg ${
                    msg.is_staff 
                      ? 'bg-primary/10 border border-primary/20' 
                      : 'bg-muted/50'
                  }`}
                >
                  <div className="flex items-center gap-2 mb-2">
                    <div className={`p-1.5 rounded-full ${
                      msg.is_staff ? 'bg-primary/20' : 'bg-muted'
                    }`}>
                      {msg.is_staff ? (
                        <Shield className="h-3 w-3 text-primary" />
                      ) : (
                        <User className="h-3 w-3 text-muted-foreground" />
                      )}
                    </div>
                    <span className="font-medium text-foreground">
                      {msg.is_staff ? 'Support Team' : (msg.sender?.username || 'You')}
                    </span>
                    <span className="text-xs text-muted-foreground">
                      {formatDateTime(msg.created_at)}
                    </span>
                  </div>
                  <p className="text-foreground whitespace-pre-wrap">{msg.message}</p>
                </div>
              ))}
            </div>
          ) : (
            <p className="text-muted-foreground text-center py-8">
              No messages yet
            </p>
          )}

          {/* Reply Form */}
          {!isClosed ? (
            <div className="pt-4 border-t border-border">
              <TicketMessageForm ticketId={ticket.id} />
            </div>
          ) : (
            <div className="pt-4 border-t border-border text-center">
              <p className="text-muted-foreground">
                This ticket is closed. Create a new ticket if you need further assistance.
              </p>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
