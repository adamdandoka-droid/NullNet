import { createClient } from "@/lib/supabase/server"
import { redirect } from "next/navigation"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import Link from "next/link"
import { ArrowLeft } from "lucide-react"
import { ProductForm } from "../product-form"

export default async function NewProductPage() {
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

  // Get categories
  const { data: categories } = await supabase
    .from("categories")
    .select("*")
    .eq("is_active", true)
    .order("sort_order")

  return (
    <div className="space-y-6">
      <div>
        <Link 
          href="/dashboard/products" 
          className="inline-flex items-center gap-2 text-muted-foreground hover:text-foreground mb-4"
        >
          <ArrowLeft className="h-4 w-4" />
          Back to Products
        </Link>
        <h1 className="text-3xl font-bold text-foreground">Add New Product</h1>
        <p className="text-muted-foreground mt-1">
          Create a new product listing
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Product Details</CardTitle>
          <CardDescription>
            Fill in the details for your new product. It will be reviewed before going live.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <ProductForm categories={categories || []} />
        </CardContent>
      </Card>
    </div>
  )
}
