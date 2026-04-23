import { createClient } from "@/lib/supabase/server"
import { redirect } from "next/navigation"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import Link from "next/link"
import { ArrowLeft } from "lucide-react"
import { TicketForm } from "./ticket-form"

interface NewTicketPageProps {
  searchParams: Promise<{ order?: string }>
}

export default async function NewTicketPage({ searchParams }: NewTicketPageProps) {
  const params = await searchParams
  const supabase = await createClient()
  const { data: { user } } = await supabase.auth.getUser()

  if (!user) {
    redirect("/auth/login")
  }

  // Get user's recent orders for linking
  const { data: orders } = await supabase
    .from("orders")
    .select(`
      id,
      created_at,
      product:products(title)
    `)
    .eq("buyer_id", user.id)
    .order("created_at", { ascending: false })
    .limit(20)

  return (
    <div className="max-w-2xl mx-auto space-y-6">
      <div>
        <Link 
          href="/dashboard/tickets" 
          className="inline-flex items-center gap-2 text-muted-foreground hover:text-foreground mb-4"
        >
          <ArrowLeft className="h-4 w-4" />
          Back to Tickets
        </Link>
        <h1 className="text-3xl font-bold text-foreground">Create Support Ticket</h1>
        <p className="text-muted-foreground mt-1">
          Describe your issue and we&apos;ll help you resolve it
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Ticket Details</CardTitle>
          <CardDescription>
            Please provide as much detail as possible to help us assist you faster.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <TicketForm orders={orders || []} preselectedOrderId={params.order} />
        </CardContent>
      </Card>
    </div>
  )
}
