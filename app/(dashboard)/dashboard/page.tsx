import { createClient } from "@/lib/supabase/server"
import { redirect } from "next/navigation"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { formatCurrency, formatDate } from "@/lib/utils"
import { Wallet, ShoppingBag, Package, TrendingUp } from "lucide-react"
import Link from "next/link"

export default async function DashboardPage() {
  const supabase = await createClient()
  const { data: { user } } = await supabase.auth.getUser()

  if (!user) {
    redirect("/auth/login")
  }

  // Get profile
  const { data: profile } = await supabase
    .from("profiles")
    .select("*")
    .eq("id", user.id)
    .single()

  // Get recent orders (as buyer)
  const { data: recentOrders } = await supabase
    .from("orders")
    .select(`
      *,
      product:products(title, price),
      seller:profiles!orders_seller_id_fkey(username)
    `)
    .eq("buyer_id", user.id)
    .order("created_at", { ascending: false })
    .limit(5)

  // Get order counts
  const { count: totalOrders } = await supabase
    .from("orders")
    .select("*", { count: "exact", head: true })
    .eq("buyer_id", user.id)

  // If seller, get additional stats
  let sellerStats = null
  if (profile?.role === "seller" || profile?.role === "reseller") {
    const { count: productCount } = await supabase
      .from("products")
      .select("*", { count: "exact", head: true })
      .eq("seller_id", user.id)

    const { count: salesCount } = await supabase
      .from("orders")
      .select("*", { count: "exact", head: true })
      .eq("seller_id", user.id)

    sellerStats = { productCount, salesCount }
  }

  const isSeller = profile?.role === "seller" || profile?.role === "reseller"

  return (
    <div className="space-y-8">
      {/* Welcome */}
      <div>
        <h1 className="text-3xl font-bold text-foreground">
          Welcome back, {profile?.username || "User"}
        </h1>
        <p className="text-muted-foreground mt-1">
          Here&apos;s what&apos;s happening with your account
        </p>
      </div>

      {/* Stats Grid */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Balance</CardTitle>
            <Wallet className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{formatCurrency(profile?.balance || 0)}</div>
            <p className="text-xs text-muted-foreground">
              <Link href="/dashboard/balance" className="text-primary hover:underline">
                Add funds
              </Link>
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Orders</CardTitle>
            <ShoppingBag className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{totalOrders || 0}</div>
            <p className="text-xs text-muted-foreground">
              Purchases made
            </p>
          </CardContent>
        </Card>

        {isSeller && (
          <>
            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Products</CardTitle>
                <Package className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{sellerStats?.productCount || 0}</div>
                <p className="text-xs text-muted-foreground">
                  <Link href="/dashboard/products" className="text-primary hover:underline">
                    Manage products
                  </Link>
                </p>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Total Sales</CardTitle>
                <TrendingUp className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{sellerStats?.salesCount || 0}</div>
                <p className="text-xs text-muted-foreground">
                  {formatCurrency(profile?.total_sales || 0)} earned
                </p>
              </CardContent>
            </Card>
          </>
        )}

        {!isSeller && (
          <>
            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Total Spent</CardTitle>
                <TrendingUp className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{formatCurrency(profile?.total_purchases || 0)}</div>
                <p className="text-xs text-muted-foreground">
                  Lifetime purchases
                </p>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Account Status</CardTitle>
                <Badge variant={profile?.is_verified ? "success" : "secondary"}>
                  {profile?.is_verified ? "Verified" : "Unverified"}
                </Badge>
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold capitalize">{profile?.role || "Buyer"}</div>
                <p className="text-xs text-muted-foreground">
                  Member since {formatDate(profile?.created_at || new Date())}
                </p>
              </CardContent>
            </Card>
          </>
        )}
      </div>

      {/* Recent Orders */}
      <Card>
        <CardHeader>
          <CardTitle>Recent Orders</CardTitle>
          <CardDescription>Your latest purchases</CardDescription>
        </CardHeader>
        <CardContent>
          {recentOrders && recentOrders.length > 0 ? (
            <div className="space-y-4">
              {recentOrders.map((order) => (
                <div
                  key={order.id}
                  className="flex items-center justify-between p-4 rounded-lg bg-muted/50"
                >
                  <div className="flex-1">
                    <p className="font-medium text-foreground">
                      {order.product?.title || "Product"}
                    </p>
                    <p className="text-sm text-muted-foreground">
                      Seller: {order.seller?.username || "Unknown"} • {formatDate(order.created_at)}
                    </p>
                  </div>
                  <div className="text-right">
                    <p className="font-medium">{formatCurrency(order.total_price)}</p>
                    <Badge variant={order.status === "completed" ? "success" : "secondary"}>
                      {order.status}
                    </Badge>
                  </div>
                </div>
              ))}
              <Link
                href="/dashboard/orders"
                className="block text-center text-sm text-primary hover:underline pt-2"
              >
                View all orders
              </Link>
            </div>
          ) : (
            <div className="text-center py-8 text-muted-foreground">
              <ShoppingBag className="h-12 w-12 mx-auto mb-4 opacity-50" />
              <p>No orders yet</p>
              <Link href="/products" className="text-primary hover:underline text-sm">
                Browse products
              </Link>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
