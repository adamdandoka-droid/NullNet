import { createClient } from "@/lib/supabase/server"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { formatCurrency, formatDateTime } from "@/lib/utils"
import { Wallet, Clock, CheckCircle, XCircle } from "lucide-react"
import { WithdrawalActions } from "./withdrawal-actions"

const statusConfig = {
  pending: { label: "Pending", variant: "warning" as const },
  processing: { label: "Processing", variant: "warning" as const },
  completed: { label: "Completed", variant: "success" as const },
  rejected: { label: "Rejected", variant: "destructive" as const },
}

export default async function AdminWithdrawalsPage() {
  const supabase = await createClient()

  // Get all withdrawals with user info
  const { data: withdrawals } = await supabase
    .from("withdrawal_requests")
    .select(`
      *,
      user:profiles(username, email, balance)
    `)
    .order("created_at", { ascending: false })

  const pendingWithdrawals = withdrawals?.filter(w => w.status === "pending" || w.status === "processing") || []
  const completedWithdrawals = withdrawals?.filter(w => w.status === "completed" || w.status === "rejected") || []

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-3xl font-bold text-foreground">Withdrawals</h1>
        <p className="text-muted-foreground mt-1">
          Process withdrawal requests
        </p>
      </div>

      {/* Pending Withdrawals */}
      <Card className={pendingWithdrawals.length > 0 ? "border-warning" : ""}>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Clock className="h-5 w-5 text-warning" />
            Pending Withdrawals ({pendingWithdrawals.length})
          </CardTitle>
          <CardDescription>
            Withdrawal requests waiting to be processed
          </CardDescription>
        </CardHeader>
        <CardContent>
          {pendingWithdrawals.length > 0 ? (
            <div className="space-y-4">
              {pendingWithdrawals.map((withdrawal) => {
                const status = statusConfig[withdrawal.status as keyof typeof statusConfig]
                const details = withdrawal.details as Record<string, string>

                return (
                  <div
                    key={withdrawal.id}
                    className="flex flex-col lg:flex-row lg:items-center justify-between gap-4 p-4 rounded-lg bg-warning/10 border border-warning/20"
                  >
                    <div className="flex-1">
                      <div className="flex items-center gap-2 mb-1">
                        <p className="font-medium text-foreground">
                          {withdrawal.user?.username}
                        </p>
                        <Badge variant={status.variant}>{status.label}</Badge>
                        <Badge variant="outline" className="capitalize">{withdrawal.method}</Badge>
                      </div>
                      <div className="text-sm text-muted-foreground space-y-1">
                        <p>Email: {withdrawal.user?.email}</p>
                        <p>Current Balance: {formatCurrency(withdrawal.user?.balance || 0)}</p>
                        <p>Requested: {formatDateTime(withdrawal.created_at)}</p>
                        {withdrawal.method === 'paypal' && (
                          <p>PayPal: {details.paypal_email}</p>
                        )}
                        {withdrawal.method === 'crypto' && (
                          <p>Wallet: {details.wallet_address}</p>
                        )}
                        {withdrawal.method === 'bank' && (
                          <p>Account: ****{details.account_number?.slice(-4)}</p>
                        )}
                      </div>
                    </div>
                    <div className="flex items-center gap-4">
                      <p className="text-xl font-bold text-foreground">
                        {formatCurrency(withdrawal.amount)}
                      </p>
                      <WithdrawalActions 
                        withdrawalId={withdrawal.id} 
                        userId={withdrawal.user_id}
                        amount={withdrawal.amount}
                        userBalance={withdrawal.user?.balance || 0}
                      />
                    </div>
                  </div>
                )
              })}
            </div>
          ) : (
            <div className="text-center py-8 text-muted-foreground">
              <CheckCircle className="h-12 w-12 mx-auto mb-4 opacity-50" />
              <p>No pending withdrawals</p>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Completed Withdrawals */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Wallet className="h-5 w-5" />
            Processed Withdrawals
          </CardTitle>
          <CardDescription>
            History of processed withdrawal requests
          </CardDescription>
        </CardHeader>
        <CardContent>
          {completedWithdrawals.length > 0 ? (
            <div className="space-y-4">
              {completedWithdrawals.map((withdrawal) => {
                const status = statusConfig[withdrawal.status as keyof typeof statusConfig]

                return (
                  <div
                    key={withdrawal.id}
                    className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-lg bg-muted/50"
                  >
                    <div>
                      <div className="flex items-center gap-2 mb-1">
                        <p className="font-medium text-foreground">
                          {withdrawal.user?.username}
                        </p>
                        <Badge variant={status.variant}>{status.label}</Badge>
                        <Badge variant="outline" className="capitalize">{withdrawal.method}</Badge>
                      </div>
                      <p className="text-sm text-muted-foreground">
                        {formatDateTime(withdrawal.processed_at || withdrawal.created_at)}
                        {withdrawal.admin_notes && ` • ${withdrawal.admin_notes}`}
                      </p>
                    </div>
                    <p className="font-semibold text-foreground">
                      {formatCurrency(withdrawal.amount)}
                    </p>
                  </div>
                )
              })}
            </div>
          ) : (
            <div className="text-center py-8 text-muted-foreground">
              <Wallet className="h-12 w-12 mx-auto mb-4 opacity-50" />
              <p>No processed withdrawals yet</p>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
