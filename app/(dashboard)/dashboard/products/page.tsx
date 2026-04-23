import { createClient } from "@/lib/supabase/server"
import { redirect } from "next/navigation"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { formatCurrency, formatDate } from "@/lib/utils"
import Link from "next/link"
import { Package, Plus, Edit, Eye, AlertCircle, CheckCircle, Clock } from "lucide-react"

const statusConfig = {
  active: { label: "Active", variant: "success" as const, icon: CheckCircle },
  pending: { label: "Pending Approval", variant: "warning" as const, icon: Clock },
  inactive: { label: "Inactive", variant: "secondary" as const, icon: AlertCircle },
}

export default async function ProductsPage() {
  const supabase = await createClient()
  const { data: { user } } = await supabase.auth.getUser()

  if (!user) {
    redirect("/auth/login")
  }

  // Check if user is a seller
  const { data: profile } = await supabase
    .from("profiles")
    .select("role")
    .eq("id", user.id)
    .single()

  if (profile?.role !== "seller" && profile?.role !== "reseller" && profile?.role !== "admin") {
    redirect("/dashboard/become-seller")
  }

  // Get user's products
  const { data: products } = await supabase
    .from("products")
    .select(`
      *,
      category:categories(name, slug)
    `)
    .eq("seller_id", user.id)
    .order("created_at", { ascending: false })

  // Get stock counts
  const productIds = products?.map(p => p.id) || []
  let stockCounts: Record<string, number> = {}
  
  if (productIds.length > 0) {
    const { data: files } = await supabase
      .from("product_files")
      .select("product_id")
      .in("product_id", productIds)
      .eq("is_sold", false)

    if (files) {
      stockCounts = files.reduce((acc, file) => {
        acc[file.product_id] = (acc[file.product_id] || 0) + 1
        return acc
      }, {} as Record<string, number>)
    }
  }

  const getProductStatus = (product: { is_active: boolean; is_approved: boolean }) => {
    if (!product.is_approved) return statusConfig.pending
    if (!product.is_active) return statusConfig.inactive
    return statusConfig.active
  }

  return (
    <div className="space-y-8">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-foreground">My Products</h1>
          <p className="text-muted-foreground mt-1">
            Manage your product listings
          </p>
        </div>
        <Button asChild>
          <Link href="/dashboard/products/new">
            <Plus className="h-4 w-4 mr-2" />
            Add Product
          </Link>
        </Button>
      </div>

      {/* Products Stats */}
      <div className="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Total Products</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-bold">{products?.length || 0}</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Active</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-bold text-success">
              {products?.filter(p => p.is_active && p.is_approved).length || 0}
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Pending Approval</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-bold text-warning">
              {products?.filter(p => !p.is_approved).length || 0}
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Total Sales</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-bold">
              {products?.reduce((acc, p) => acc + (p.total_sales || 0), 0) || 0}
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Products List */}
      <Card>
        <CardHeader>
          <CardTitle>All Products</CardTitle>
          <CardDescription>
            Click on a product to edit or manage stock
          </CardDescription>
        </CardHeader>
        <CardContent>
          {products && products.length > 0 ? (
            <div className="space-y-4">
              {products.map((product) => {
                const status = getProductStatus(product)
                const StatusIcon = status.icon
                const availableStock = product.delivery_type === "instant" 
                  ? (stockCounts[product.id] || 0)
                  : product.stock

                return (
                  <div
                    key={product.id}
                    className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-lg bg-muted/50 hover:bg-muted/70 transition-colors"
                  >
                    <div className="flex-1">
                      <div className="flex items-center gap-2 mb-1">
                        <p className="font-medium text-foreground">{product.title}</p>
                        <Badge variant={status.variant} className="flex items-center gap-1">
                          <StatusIcon className="h-3 w-3" />
                          {status.label}
                        </Badge>
                      </div>
                      <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground">
                        <span>{product.category?.name || "Uncategorized"}</span>
                        <span>{formatCurrency(product.price)}</span>
                        <span>
                          Stock: {availableStock}
                          {product.delivery_type === "instant" && " files"}
                        </span>
                        <span>{product.total_sales || 0} sales</span>
                        <span>Listed {formatDate(product.created_at)}</span>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <Button variant="outline" size="sm" asChild>
                        <Link href={`/products/${product.id}`}>
                          <Eye className="h-4 w-4 mr-1" />
                          View
                        </Link>
                      </Button>
                      <Button variant="outline" size="sm" asChild>
                        <Link href={`/dashboard/products/${product.id}`}>
                          <Edit className="h-4 w-4 mr-1" />
                          Edit
                        </Link>
                      </Button>
                    </div>
                  </div>
                )
              })}
            </div>
          ) : (
            <div className="text-center py-12">
              <Package className="h-12 w-12 mx-auto mb-4 text-muted-foreground opacity-50" />
              <h3 className="text-lg font-medium text-foreground mb-2">No products yet</h3>
              <p className="text-muted-foreground mb-4">
                Start selling by adding your first product
              </p>
              <Button asChild>
                <Link href="/dashboard/products/new">
                  <Plus className="h-4 w-4 mr-2" />
                  Add Your First Product
                </Link>
              </Button>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
