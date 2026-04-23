import { createClient } from "@/lib/supabase/server"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { formatDateTime } from "@/lib/utils"
import Link from "next/link"
import { HelpCircle, Clock, CheckCircle, MessageSquare, Loader2, AlertCircle } from "lucide-react"

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

export default async function AdminTicketsPage() {
  const supabase = await createClient()

  // Get all tickets with user info
  const { data: tickets } = await supabase
    .from("tickets")
    .select(`
      *,
      user:profiles(username, email),
      order:orders(id, product:products(title))
    `)
    .order("created_at", { ascending: false })

  const openTickets = tickets?.filter(t => t.status === "open" || t.status === "in_progress") || []
  const otherTickets = tickets?.filter(t => t.status !== "open" && t.status !== "in_progress") || []

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-3xl font-bold text-foreground">Support Tickets</h1>
        <p className="text-muted-foreground mt-1">
          Manage customer support requests
        </p>
      </div>

      {/* Open Tickets */}
      <Card className={openTickets.length > 0 ? "border-warning" : ""}>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <MessageSquare className="h-5 w-5 text-warning" />
            Open Tickets ({openTickets.length})
          </CardTitle>
          <CardDescription>
            Tickets that need attention
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
                    href={`/admin/tickets/${ticket.id}`}
                    className="block p-4 rounded-lg bg-warning/10 border border-warning/20 hover:bg-warning/20 transition-colors"
                  >
                    <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
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
                          From: {ticket.user?.username} ({ticket.user?.email}) • 
                          Category: {ticket.category} • 
                          Created: {formatDateTime(ticket.created_at)}
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
              <CheckCircle className="h-12 w-12 mx-auto mb-4 opacity-50" />
              <p>No open tickets</p>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Other Tickets */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <HelpCircle className="h-5 w-5" />
            All Tickets
          </CardTitle>
          <CardDescription>
            Complete ticket history
          </CardDescription>
        </CardHeader>
        <CardContent>
          {otherTickets.length > 0 ? (
            <div className="space-y-4">
              {otherTickets.map((ticket) => {
                const status = statusConfig[ticket.status as keyof typeof statusConfig] || statusConfig.open
                const StatusIcon = status.icon

                return (
                  <Link
                    key={ticket.id}
                    href={`/admin/tickets/${ticket.id}`}
                    className="block p-4 rounded-lg bg-muted/50 hover:bg-muted/70 transition-colors"
                  >
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                      <div>
                        <div className="flex items-center gap-2 mb-1">
                          <p className="font-medium text-foreground">{ticket.subject}</p>
                          <Badge variant={status.variant} className="flex items-center gap-1">
                            <StatusIcon className="h-3 w-3" />
                            {status.label}
                          </Badge>
                        </div>
                        <p className="text-sm text-muted-foreground">
                          {ticket.user?.username} • {formatDateTime(ticket.created_at)}
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
          ) : (
            <div className="text-center py-8 text-muted-foreground">
              <HelpCircle className="h-12 w-12 mx-auto mb-4 opacity-50" />
              <p>No tickets yet</p>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
