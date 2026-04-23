import { createClient } from "@/lib/supabase/server"
import { redirect } from "next/navigation"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { formatCurrency, formatDateTime } from "@/lib/utils"
import Link from "next/link"
import { ShoppingBag, Eye, MessageSquare, Package } from "lucide-react"
import { OrderDetailsDialog } from "./order-details-dialog"

const statusColors: Record<string, "default" | "success" | "warning" | "destructive" | "secondary"> = {
  pending: "warning",
  completed: "success",
  cancelled: "secondary",
  disputed: "destructive",
  refunded: "secondary",
}

export default async function OrdersPage() {
  const supabase = await createClient()
  const { data: { user } } = await supabase.auth.getUser()

  if (!user) {
    redirect("/auth/login")
  }

  // Get user profile to determine role
  const { data: profile } = await supabase
    .from("profiles")
    .select("role")
    .eq("id", user.id)
    .single()

  const isSeller = profile?.role === "seller" || profile?.role === "reseller"

  // Get orders based on role - buyers see their purchases, sellers see their sales
  const { data: orders } = await supabase
    .from("orders")
    .select(`
      *,
      product:products(id, title, delivery_type),
      buyer:profiles!orders_buyer_id_fkey(username),
      seller:profiles!orders_seller_id_fkey(username)
    `)
    .or(`buyer_id.eq.${user.id},seller_id.eq.${user.id}`)
    .order("created_at", { ascending: false })

  // Separate into purchases and sales
  const purchases = orders?.filter(o => o.buyer_id === user.id) || []
  const sales = orders?.filter(o => o.seller_id === user.id) || []

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-3xl font-bold text-foreground">Orders</h1>
        <p className="text-muted-foreground mt-1">
          {isSeller ? "Manage your purchases and sales" : "View your purchase history"}
        </p>
      </div>

      {/* Purchases Section */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <ShoppingBag className="h-5 w-5" />
            My Purchases
          </CardTitle>
          <CardDescription>Products you have bought</CardDescription>
        </CardHeader>
        <CardContent>
          {purchases.length > 0 ? (
            <div className="space-y-4">
              {purchases.map((order) => (
                <div
                  key={order.id}
                  className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-lg bg-muted/50"
                >
                  <div className="flex-1">
                    <div className="flex items-center gap-2 mb-1">
                      <p className="font-medium text-foreground">
                        {order.product?.title || "Product"}
                      </p>
                      <Badge variant={statusColors[order.status] || "secondary"}>
                        {order.status}
                      </Badge>
                    </div>
                    <p className="text-sm text-muted-foreground">
                      Seller: {order.seller?.username || "Unknown"} • 
                      Qty: {order.quantity} • 
                      {formatDateTime(order.created_at)}
                    </p>
                  </div>
                  <div className="flex items-center gap-4">
                    <p className="font-semibold text-foreground">
                      {formatCurrency(order.total_price)}
                    </p>
                    <div className="flex gap-2">
                      <OrderDetailsDialog order={order} type="purchase" />
                      {order.status === "completed" && !order.delivery_data && (
                        <Button variant="outline" size="sm" asChild>
                          <Link href={`/dashboard/tickets/new?order=${order.id}`}>
                            <MessageSquare className="h-4 w-4 mr-1" />
                            Contact
                          </Link>
                        </Button>
                      )}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="text-center py-8 text-muted-foreground">
              <ShoppingBag className="h-12 w-12 mx-auto mb-4 opacity-50" />
              <p>No purchases yet</p>
              <Link href="/products" className="text-primary hover:underline text-sm">
                Browse products
              </Link>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Sales Section (for sellers) */}
      {isSeller && (
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Package className="h-5 w-5" />
              My Sales
            </CardTitle>
            <CardDescription>Products you have sold</CardDescription>
          </CardHeader>
          <CardContent>
            {sales.length > 0 ? (
              <div className="space-y-4">
                {sales.map((order) => (
                  <div
                    key={order.id}
                    className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-lg bg-muted/50"
                  >
                    <div className="flex-1">
                      <div className="flex items-center gap-2 mb-1">
                        <p className="font-medium text-foreground">
                          {order.product?.title || "Product"}
                        </p>
                        <Badge variant={statusColors[order.status] || "secondary"}>
                          {order.status}
                        </Badge>
                      </div>
                      <p className="text-sm text-muted-foreground">
                        Buyer: {order.buyer?.username || "Unknown"} • 
                        Qty: {order.quantity} • 
                        {formatDateTime(order.created_at)}
                      </p>
                    </div>
                    <div className="flex items-center gap-4">
                      <p className="font-semibold text-success">
                        +{formatCurrency(order.total_price)}
                      </p>
                      <OrderDetailsDialog order={order} type="sale" />
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-8 text-muted-foreground">
                <Package className="h-12 w-12 mx-auto mb-4 opacity-50" />
                <p>No sales yet</p>
                <Link href="/dashboard/products" className="text-primary hover:underline text-sm">
                  Add products to start selling
                </Link>
              </div>
            )}
          </CardContent>
        </Card>
      )}
    </div>
  )
}
