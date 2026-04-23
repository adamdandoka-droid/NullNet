import { createClient } from "@/lib/supabase/server"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { formatCurrency, formatDate } from "@/lib/utils"
import { Package, CheckCircle, Clock, XCircle } from "lucide-react"
import { ProductActions } from "./product-actions"

export default async function AdminProductsPage() {
  const supabase = await createClient()

  // Get all products with seller info
  const { data: products } = await supabase
    .from("products")
    .select(`
      *,
      category:categories(name),
      seller:profiles!products_seller_id_fkey(username, email)
    `)
    .order("created_at", { ascending: false })

  const pendingProducts = products?.filter(p => !p.is_approved) || []
  const approvedProducts = products?.filter(p => p.is_approved) || []

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-3xl font-bold text-foreground">Products</h1>
        <p className="text-muted-foreground mt-1">
          Manage and approve product listings
        </p>
      </div>

      {/* Pending Approval */}
      {pendingProducts.length > 0 && (
        <Card className="border-warning">
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Clock className="h-5 w-5 text-warning" />
              Pending Approval ({pendingProducts.length})
            </CardTitle>
            <CardDescription>
              These products are waiting for review
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {pendingProducts.map((product) => (
                <div
                  key={product.id}
                  className="flex flex-col lg:flex-row lg:items-center justify-between gap-4 p-4 rounded-lg bg-warning/10 border border-warning/20"
                >
                  <div className="flex-1">
                    <div className="flex items-center gap-2 mb-1">
                      <p className="font-medium text-foreground">{product.title}</p>
                      <Badge variant="warning">Pending</Badge>
                    </div>
                    <p className="text-sm text-muted-foreground line-clamp-1">
                      {product.description || 'No description'}
                    </p>
                    <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground mt-2">
                      <span>Seller: {product.seller?.username}</span>
                      <span>Price: {formatCurrency(product.price)}</span>
                      <span>Category: {product.category?.name || 'Uncategorized'}</span>
                      <span>Stock: {product.stock}</span>
                      <span>Listed: {formatDate(product.created_at)}</span>
                    </div>
                  </div>
                  <ProductActions productId={product.id} isApproved={false} />
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      )}

      {/* Approved Products */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Package className="h-5 w-5" />
            All Products ({approvedProducts.length})
          </CardTitle>
          <CardDescription>
            Active product listings on the marketplace
          </CardDescription>
        </CardHeader>
        <CardContent>
          {approvedProducts.length > 0 ? (
            <div className="space-y-4">
              {approvedProducts.map((product) => (
                <div
                  key={product.id}
                  className="flex flex-col lg:flex-row lg:items-center justify-between gap-4 p-4 rounded-lg bg-muted/50"
                >
                  <div className="flex-1">
                    <div className="flex items-center gap-2 mb-1">
                      <p className="font-medium text-foreground">{product.title}</p>
                      <Badge variant={product.is_active ? "success" : "secondary"}>
                        {product.is_active ? "Active" : "Inactive"}
                      </Badge>
                    </div>
                    <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground">
                      <span>Seller: {product.seller?.username}</span>
                      <span>Price: {formatCurrency(product.price)}</span>
                      <span>Category: {product.category?.name || 'Uncategorized'}</span>
                      <span>Stock: {product.stock}</span>
                      <span>Sales: {product.total_sales || 0}</span>
                    </div>
                  </div>
                  <ProductActions productId={product.id} isApproved={true} isActive={product.is_active} />
                </div>
              ))}
            </div>
          ) : (
            <div className="text-center py-8 text-muted-foreground">
              <Package className="h-12 w-12 mx-auto mb-4 opacity-50" />
              <p>No approved products yet</p>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
