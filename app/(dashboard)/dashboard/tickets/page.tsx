import { createClient } from "@/lib/supabase/server"
import { redirect } from "next/navigation"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { formatDateTime } from "@/lib/utils"
import Link from "next/link"
import { HelpCircle, Plus, MessageSquare, Clock, CheckCircle, AlertCircle, Loader2 } from "lucide-react"

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

export default async function TicketsPage() {
  const supabase = await createClient()
  const { data: { user } } = await supabase.auth.getUser()

  if (!user) {
    redirect("/auth/login")
  }

  // Get user's tickets
  const { data: tickets } = await supabase
    .from("tickets")
    .select(`
      *,
      order:orders(id, product:products(title))
    `)
    .eq("user_id", user.id)
    .order("created_at", { ascending: false })

  const openTickets = tickets?.filter(t => t.status !== "closed" && t.status !== "resolved") || []
  const closedTickets = tickets?.filter(t => t.status === "closed" || t.status === "resolved") || []

  return (
    <div className="space-y-8">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-foreground">Support</h1>
          <p className="text-muted-foreground mt-1">
            Get help with your orders and account
          </p>
        </div>
        <Button asChild>
          <Link href="/dashboard/tickets/new">
            <Plus className="h-4 w-4 mr-2" />
            New Ticket
          </Link>
        </Button>
      </div>

      {/* Open Tickets */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <MessageSquare className="h-5 w-5" />
            Open Tickets
          </CardTitle>
          <CardDescription>
            Your active support requests
          </CardDescription>
        </CardHeader>
        <CardContent>
          {openTickets.length > 0 ? (
            <div className="space-y-4">
              {openTickets.map((ticket) => {
                const status = statusConfig[ticket.status as keyof typeof statusConfig] || statusConfig.open
                const StatusIcon = status.icon

                return (
                  <Link
                    key={ticket.id}
                    href={`/dashboard/tickets/${ticket.id}`}
                    className="block p-4 rounded-lg bg-muted/50 hover:bg-muted/70 transition-colors"
                  >
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                      <div className="flex-1">
                        <div className="flex items-center gap-2 mb-1">
                          <p className="font-medium text-foreground">{ticket.subject}</p>
                          <Badge variant={status.variant} className="flex items-center gap-1">
                            <StatusIcon className="h-3 w-3" />
                            {status.label}
                          </Badge>
                          <Badge variant={priorityColors[ticket.priority as keyof typeof priorityColors]}>
                            {ticket.priority}
                          </Badge>
                        </div>
                        <p className="text-sm text-muted-foreground">
                          Category: {ticket.category} • 
                          {ticket.order?.product?.title && ` Order: ${ticket.order.product.title} • `}
                          Created {formatDateTime(ticket.created_at)}
                        </p>
                      </div>
                      <Button variant="outline" size="sm">
                        View
                      </Button>
                    </div>
                  </Link>
                )
              })}
            </div>
          ) : (
            <div className="text-center py-8 text-muted-foreground">
              <HelpCircle className="h-12 w-12 mx-auto mb-4 opacity-50" />
              <p>No open tickets</p>
              <p className="text-sm mt-1">
                Need help?{" "}
                <Link href="/dashboard/tickets/new" className="text-primary hover:underline">
                  Create a ticket
                </Link>
              </p>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Closed Tickets */}
      {closedTickets.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>Resolved Tickets</CardTitle>
            <CardDescription>
              Your past support requests
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {closedTickets.map((ticket) => {
                const status = statusConfig[ticket.status as keyof typeof statusConfig] || statusConfig.closed
                const StatusIcon = status.icon

                return (
                  <Link
                    key={ticket.id}
                    href={`/dashboard/tickets/${ticket.id}`}
                    className="block p-4 rounded-lg bg-muted/50 hover:bg-muted/70 transition-colors"
                  >
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                      <div className="flex-1">
                        <div className="flex items-center gap-2 mb-1">
                          <p className="font-medium text-foreground">{ticket.subject}</p>
                          <Badge variant={status.variant} className="flex items-center gap-1">
                            <StatusIcon className="h-3 w-3" />
                            {status.label}
                          </Badge>
                        </div>
                        <p className="text-sm text-muted-foreground">
                          Category: {ticket.category} • 
                          Created {formatDateTime(ticket.created_at)}
                        </p>
                      </div>
                      <Button variant="ghost" size="sm">
                        View
                      </Button>
                    </div>
                  </Link>
                )
              })}
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  )
}
