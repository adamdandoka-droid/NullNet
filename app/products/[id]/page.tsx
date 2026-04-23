import { createClient } from "@/lib/supabase/server"
import { notFound } from "next/navigation"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { formatCurrency, formatDate } from "@/lib/utils"
import Link from "next/link"
import { 
  ShoppingBag, 
  ArrowLeft, 
  User, 
  Package, 
  Star, 
  Shield, 
  Zap,
  CheckCircle,
  Clock
} from "lucide-react"
import { PurchaseButton } from "./purchase-button"

interface ProductPageProps {
  params: Promise<{ id: string }>
}

export default async function ProductPage({ params }: ProductPageProps) {
  const { id } = await params
  const supabase = await createClient()

  // Get current user
  const { data: { user } } = await supabase.auth.getUser()

  // Get user profile if logged in
  let profile = null
  if (user) {
    const { data } = await supabase
      .from("profiles")
      .select("*")
      .eq("id", user.id)
      .single()
    profile = data
  }

  // Get product with seller info
  const { data: product, error } = await supabase
    .from("products")
    .select(`
      *,
      category:categories(id, name, slug),
      seller:profiles!products_seller_id_fkey(id, username, is_verified, total_sales, created_at)
    `)
    .eq("id", id)
    .single()

  if (error || !product) {
    notFound()
  }

  // Check if product is available for purchase
  const isOwner = user?.id === product.seller_id
  const canPurchase = user && !isOwner && product.stock > 0 && product.is_active && product.is_approved
  const hasEnoughBalance = profile && profile.balance >= product.price

  return (
    <div className="min-h-screen bg-background">
      {/* Header */}
      <header className="border-b border-border">
        <div className="container mx-auto px-4 py-4 flex items-center justify-between">
          <Link href="/" className="flex items-center gap-2">
            <ShoppingBag className="h-8 w-8 text-primary" />
            <span className="text-2xl font-bold text-foreground">NullNet</span>
          </Link>
          <div className="flex items-center gap-3">
            {user ? (
              <Button asChild>
                <Link href="/dashboard">Dashboard</Link>
              </Button>
            ) : (
              <>
                <Button variant="ghost" asChild>
                  <Link href="/auth/login">Login</Link>
                </Button>
                <Button asChild>
                  <Link href="/auth/sign-up">Sign Up</Link>
                </Button>
              </>
            )}
          </div>
        </div>
      </header>

      <div className="container mx-auto px-4 py-8">
        {/* Back Link */}
        <Link 
          href="/products" 
          className="inline-flex items-center gap-2 text-muted-foreground hover:text-foreground mb-6"
        >
          <ArrowLeft className="h-4 w-4" />
          Back to Products
        </Link>

        <div className="grid lg:grid-cols-3 gap-8">
          {/* Main Content */}
          <div className="lg:col-span-2 space-y-6">
            {/* Product Info Card */}
            <Card>
              <CardHeader>
                <div className="flex items-start justify-between gap-4">
                  <div className="flex-1">
                    <div className="flex items-center gap-2 mb-2">
                      {product.category && (
                        <Badge variant="outline">
                          {product.category.name}
                        </Badge>
                      )}
                      {product.delivery_type === "instant" && (
                        <Badge variant="success" className="flex items-center gap-1">
                          <Zap className="h-3 w-3" />
                          Instant
                        </Badge>
                      )}
                    </div>
                    <CardTitle className="text-2xl">{product.title}</CardTitle>
                    <CardDescription className="mt-2">
                      Listed on {formatDate(product.created_at)}
                    </CardDescription>
                  </div>
                  <div className="text-right">
                    <p className="text-3xl font-bold text-primary">
                      {formatCurrency(product.price)}
                    </p>
                    <p className="text-sm text-muted-foreground mt-1">
                      {product.stock} in stock
                    </p>
                  </div>
                </div>
              </CardHeader>
              <CardContent>
                <div className="prose prose-invert max-w-none">
                  <h3 className="text-lg font-semibold text-foreground mb-2">Description</h3>
                  <p className="text-muted-foreground whitespace-pre-wrap">
                    {product.description || "No description provided."}
                  </p>
                </div>

                {/* Product Stats */}
                <div className="grid grid-cols-3 gap-4 mt-6 pt-6 border-t border-border">
                  <div className="text-center">
                    <p className="text-2xl font-bold text-foreground">{product.total_sales}</p>
                    <p className="text-sm text-muted-foreground">Total Sales</p>
                  </div>
                  <div className="text-center">
                    <div className="flex items-center justify-center gap-1">
                      <Star className="h-5 w-5 text-warning fill-warning" />
                      <span className="text-2xl font-bold text-foreground">
                        {product.rating ? product.rating.toFixed(1) : "N/A"}
                      </span>
                    </div>
                    <p className="text-sm text-muted-foreground">
                      {product.rating_count} reviews
                    </p>
                  </div>
                  <div className="text-center">
                    <p className="text-2xl font-bold text-foreground capitalize">
                      {product.delivery_type}
                    </p>
                    <p className="text-sm text-muted-foreground">Delivery</p>
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Features */}
            <Card>
              <CardHeader>
                <CardTitle className="text-lg">What You Get</CardTitle>
              </CardHeader>
              <CardContent>
                <ul className="space-y-3">
                  <li className="flex items-start gap-3">
                    <CheckCircle className="h-5 w-5 text-success mt-0.5" />
                    <div>
                      <p className="font-medium text-foreground">
                        {product.delivery_type === "instant" ? "Instant Delivery" : "Manual Delivery"}
                      </p>
                      <p className="text-sm text-muted-foreground">
                        {product.delivery_type === "instant" 
                          ? "Receive your product immediately after purchase"
                          : "Seller will deliver manually within 24 hours"
                        }
                      </p>
                    </div>
                  </li>
                  <li className="flex items-start gap-3">
                    <Shield className="h-5 w-5 text-success mt-0.5" />
                    <div>
                      <p className="font-medium text-foreground">Buyer Protection</p>
                      <p className="text-sm text-muted-foreground">
                        Your purchase is protected. Open a dispute if there&apos;s an issue.
                      </p>
                    </div>
                  </li>
                  <li className="flex items-start gap-3">
                    <Clock className="h-5 w-5 text-success mt-0.5" />
                    <div>
                      <p className="font-medium text-foreground">24/7 Support</p>
                      <p className="text-sm text-muted-foreground">
                        Our support team is available around the clock.
                      </p>
                    </div>
                  </li>
                </ul>
              </CardContent>
            </Card>
          </div>

          {/* Sidebar */}
          <div className="space-y-6">
            {/* Purchase Card */}
            <Card>
              <CardHeader>
                <CardTitle className="text-lg">Purchase</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                {!user ? (
                  <div className="text-center py-4">
                    <p className="text-muted-foreground mb-4">
                      Sign in to purchase this product
                    </p>
                    <Button className="w-full" asChild>
                      <Link href={`/auth/login?redirect=/products/${id}`}>
                        Sign In to Buy
                      </Link>
                    </Button>
                  </div>
                ) : isOwner ? (
                  <div className="text-center py-4">
                    <p className="text-muted-foreground">
                      This is your product
                    </p>
                    <Button className="w-full mt-4" variant="outline" asChild>
                      <Link href="/dashboard/products">
                        Manage Products
                      </Link>
                    </Button>
                  </div>
                ) : product.stock === 0 ? (
                  <div className="text-center py-4">
                    <Badge variant="destructive" className="mb-2">Out of Stock</Badge>
                    <p className="text-muted-foreground">
                      This product is currently unavailable
                    </p>
                  </div>
                ) : (
                  <>
                    <div className="flex items-center justify-between text-sm">
                      <span className="text-muted-foreground">Your Balance</span>
                      <span className="font-medium text-foreground">
                        {formatCurrency(profile?.balance || 0)}
                      </span>
                    </div>
                    <div className="flex items-center justify-between text-sm">
                      <span className="text-muted-foreground">Product Price</span>
                      <span className="font-medium text-foreground">
                        {formatCurrency(product.price)}
                      </span>
                    </div>
                    <div className="border-t border-border pt-4">
                      {hasEnoughBalance ? (
                        <PurchaseButton 
                          productId={product.id}
                          productTitle={product.title}
                          price={product.price}
                        />
                      ) : (
                        <div className="space-y-3">
                          <p className="text-sm text-destructive text-center">
                            Insufficient balance. You need {formatCurrency(product.price - (profile?.balance || 0))} more.
                          </p>
                          <Button className="w-full" asChild>
                            <Link href="/dashboard/balance">
                              Add Funds
                            </Link>
                          </Button>
                        </div>
                      )}
                    </div>
                  </>
                )}
              </CardContent>
            </Card>

            {/* Seller Card */}
            <Card>
              <CardHeader>
                <CardTitle className="text-lg">Seller Information</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="flex items-center gap-3 mb-4">
                  <div className="h-12 w-12 rounded-full bg-primary/10 flex items-center justify-center">
                    <User className="h-6 w-6 text-primary" />
                  </div>
                  <div>
                    <p className="font-medium text-foreground flex items-center gap-2">
                      {product.seller?.username || "Unknown Seller"}
                      {product.seller?.is_verified && (
                        <Badge variant="success" className="text-xs">Verified</Badge>
                      )}
                    </p>
                    <p className="text-sm text-muted-foreground">
                      Member since {formatDate(product.seller?.created_at || new Date())}
                    </p>
                  </div>
                </div>
                <div className="grid grid-cols-2 gap-4 text-center pt-4 border-t border-border">
                  <div>
                    <p className="text-xl font-bold text-foreground">
                      {formatCurrency(product.seller?.total_sales || 0)}
                    </p>
                    <p className="text-xs text-muted-foreground">Total Sales</p>
                  </div>
                  <div>
                    <Package className="h-5 w-5 mx-auto text-primary mb-1" />
                    <p className="text-xs text-muted-foreground">View Store</p>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </div>
  )
}
