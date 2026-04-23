import { createClient } from "@/lib/supabase/server"
import { redirect } from "next/navigation"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { formatCurrency, formatDateTime } from "@/lib/utils"
import Link from "next/link"
import { Wallet, ArrowUpRight, ArrowDownLeft, Plus, CreditCard, History } from "lucide-react"
import { AddFundsDialog } from "./add-funds-dialog"

export default async function BalancePage() {
  const supabase = await createClient()
  const { data: { user } } = await supabase.auth.getUser()

  if (!user) {
    redirect("/auth/login")
  }

  // Get profile with balance
  const { data: profile } = await supabase
    .from("profiles")
    .select("*")
    .eq("id", user.id)
    .single()

  const isSeller = profile?.role === "seller" || profile?.role === "reseller"

  // Get recent transactions (orders as buyer and seller)
  const { data: buyerOrders } = await supabase
    .from("orders")
    .select(`
      id,
      total_price,
      status,
      created_at,
      product:products(title)
    `)
    .eq("buyer_id", user.id)
    .eq("status", "completed")
    .order("created_at", { ascending: false })
    .limit(5)

  const { data: sellerOrders } = isSeller
    ? await supabase
        .from("orders")
        .select(`
          id,
          total_price,
          status,
          created_at,
          product:products(title)
        `)
        .eq("seller_id", user.id)
        .eq("status", "completed")
        .order("created_at", { ascending: false })
        .limit(5)
    : { data: [] }

  // Get withdrawal requests
  const { data: withdrawals } = await supabase
    .from("withdrawal_requests")
    .select("*")
    .eq("user_id", user.id)
    .order("created_at", { ascending: false })
    .limit(5)

  // Combine and sort transactions
  const transactions = [
    ...(buyerOrders?.map(o => ({
      id: o.id,
      type: 'purchase' as const,
      amount: -o.total_price,
      description: o.product?.title || 'Purchase',
      date: o.created_at,
      status: o.status,
    })) || []),
    ...(sellerOrders?.map(o => ({
      id: o.id,
      type: 'sale' as const,
      amount: o.total_price,
      description: o.product?.title || 'Sale',
      date: o.created_at,
      status: o.status,
    })) || []),
    ...(withdrawals?.map(w => ({
      id: w.id,
      type: 'withdrawal' as const,
      amount: -w.amount,
      description: `Withdrawal (${w.method})`,
      date: w.created_at,
      status: w.status,
    })) || []),
  ].sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime())
    .slice(0, 10)

  const statusColors: Record<string, "default" | "success" | "warning" | "destructive" | "secondary"> = {
    completed: "success",
    pending: "warning",
    processing: "warning",
    rejected: "destructive",
  }

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-3xl font-bold text-foreground">Balance</h1>
        <p className="text-muted-foreground mt-1">
          Manage your funds and view transaction history
        </p>
      </div>

      {/* Balance Cards */}
      <div className="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Available Balance</CardTitle>
            <Wallet className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-primary">
              {formatCurrency(profile?.balance || 0)}
            </div>
            <p className="text-xs text-muted-foreground mt-1">
              Available for purchases or withdrawal
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Spent</CardTitle>
            <ArrowUpRight className="h-4 w-4 text-destructive" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {formatCurrency(profile?.total_purchases || 0)}
            </div>
            <p className="text-xs text-muted-foreground mt-1">
              Lifetime purchases
            </p>
          </CardContent>
        </Card>

        {isSeller && (
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Total Earned</CardTitle>
              <ArrowDownLeft className="h-4 w-4 text-success" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-success">
                {formatCurrency(profile?.total_sales || 0)}
              </div>
              <p className="text-xs text-muted-foreground mt-1">
                Lifetime sales earnings
              </p>
            </CardContent>
          </Card>
        )}
      </div>

      {/* Actions */}
      <div className="flex flex-wrap gap-4">
        <AddFundsDialog />
        {isSeller && (
          <Button variant="outline" asChild>
            <Link href="/dashboard/withdrawals">
              <CreditCard className="h-4 w-4 mr-2" />
              Withdraw Funds
            </Link>
          </Button>
        )}
      </div>

      {/* Recent Transactions */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <History className="h-5 w-5" />
            Recent Transactions
          </CardTitle>
          <CardDescription>
            Your recent account activity
          </CardDescription>
        </CardHeader>
        <CardContent>
          {transactions.length > 0 ? (
            <div className="space-y-4">
              {transactions.map((transaction) => (
                <div
                  key={`${transaction.type}-${transaction.id}`}
                  className="flex items-center justify-between p-4 rounded-lg bg-muted/50"
                >
                  <div className="flex items-center gap-3">
                    <div className={`p-2 rounded-full ${
                      transaction.amount > 0 ? 'bg-success/10' : 'bg-destructive/10'
                    }`}>
                      {transaction.amount > 0 ? (
                        <ArrowDownLeft className="h-4 w-4 text-success" />
                      ) : (
                        <ArrowUpRight className="h-4 w-4 text-destructive" />
                      )}
                    </div>
                    <div>
                      <p className="font-medium text-foreground">
                        {transaction.description}
                      </p>
                      <p className="text-sm text-muted-foreground">
                        {formatDateTime(transaction.date)}
                      </p>
                    </div>
                  </div>
                  <div className="text-right">
                    <p className={`font-semibold ${
                      transaction.amount > 0 ? 'text-success' : 'text-foreground'
                    }`}>
                      {transaction.amount > 0 ? '+' : ''}{formatCurrency(transaction.amount)}
                    </p>
                    <Badge variant={statusColors[transaction.status] || "secondary"} className="mt-1">
                      {transaction.status}
                    </Badge>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="text-center py-8 text-muted-foreground">
              <History className="h-12 w-12 mx-auto mb-4 opacity-50" />
              <p>No transactions yet</p>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
