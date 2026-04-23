import { createClient } from "@/lib/supabase/server"
import { redirect } from "next/navigation"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { formatCurrency, formatDateTime } from "@/lib/utils"
import { CreditCard, Clock, CheckCircle, XCircle, Loader2 } from "lucide-react"
import { WithdrawalForm } from "./withdrawal-form"

const statusConfig = {
  pending: { label: "Pending", variant: "warning" as const, icon: Clock },
  processing: { label: "Processing", variant: "warning" as const, icon: Loader2 },
  completed: { label: "Completed", variant: "success" as const, icon: CheckCircle },
  rejected: { label: "Rejected", variant: "destructive" as const, icon: XCircle },
}

export default async function WithdrawalsPage() {
  const supabase = await createClient()
  const { data: { user } } = await supabase.auth.getUser()

  if (!user) {
    redirect("/auth/login")
  }

  // Check if user is a seller
  const { data: profile } = await supabase
    .from("profiles")
    .select("*")
    .eq("id", user.id)
    .single()

  if (profile?.role !== "seller" && profile?.role !== "reseller" && profile?.role !== "admin") {
    redirect("/dashboard")
  }

  // Get withdrawal requests
  const { data: withdrawals } = await supabase
    .from("withdrawal_requests")
    .select("*")
    .eq("user_id", user.id)
    .order("created_at", { ascending: false })

  // Calculate pending withdrawals
  const pendingAmount = withdrawals
    ?.filter(w => w.status === "pending" || w.status === "processing")
    .reduce((acc, w) => acc + w.amount, 0) || 0

  const availableBalance = (profile?.balance || 0) - pendingAmount

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-3xl font-bold text-foreground">Withdrawals</h1>
        <p className="text-muted-foreground mt-1">
          Request withdrawals for your earnings
        </p>
      </div>

      {/* Balance Overview */}
      <div className="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">
              Total Balance
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-bold">{formatCurrency(profile?.balance || 0)}</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">
              Pending Withdrawals
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-bold text-warning">{formatCurrency(pendingAmount)}</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">
              Available to Withdraw
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-bold text-success">{formatCurrency(availableBalance)}</p>
          </CardContent>
        </Card>
      </div>

      {/* Withdrawal Form */}
      <Card>
        <CardHeader>
          <CardTitle>Request Withdrawal</CardTitle>
          <CardDescription>
            Minimum withdrawal amount is $10.00. Processing time is 1-3 business days.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <WithdrawalForm availableBalance={availableBalance} />
        </CardContent>
      </Card>

      {/* Withdrawal History */}
      <Card>
        <CardHeader>
          <CardTitle>Withdrawal History</CardTitle>
          <CardDescription>
            Your past withdrawal requests
          </CardDescription>
        </CardHeader>
        <CardContent>
          {withdrawals && withdrawals.length > 0 ? (
            <div className="space-y-4">
              {withdrawals.map((withdrawal) => {
                const status = statusConfig[withdrawal.status as keyof typeof statusConfig] || statusConfig.pending
                const StatusIcon = status.icon

                return (
                  <div
                    key={withdrawal.id}
                    className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-lg bg-muted/50"
                  >
                    <div className="flex items-center gap-3">
                      <div className="p-2 rounded-full bg-primary/10">
                        <CreditCard className="h-4 w-4 text-primary" />
                      </div>
                      <div>
                        <p className="font-medium text-foreground capitalize">
                          {withdrawal.method} Withdrawal
                        </p>
                        <p className="text-sm text-muted-foreground">
                          {formatDateTime(withdrawal.created_at)}
                        </p>
                      </div>
                    </div>
                    <div className="flex items-center gap-4">
                      <p className="font-semibold text-foreground">
                        {formatCurrency(withdrawal.amount)}
                      </p>
                      <Badge variant={status.variant} className="flex items-center gap-1">
                        <StatusIcon className="h-3 w-3" />
                        {status.label}
                      </Badge>
                    </div>
                  </div>
                )
              })}
            </div>
          ) : (
            <div className="text-center py-8 text-muted-foreground">
              <CreditCard className="h-12 w-12 mx-auto mb-4 opacity-50" />
              <p>No withdrawal requests yet</p>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
