import { createClient } from "@/lib/supabase/server"
import { redirect, notFound } from "next/navigation"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import Link from "next/link"
import { ArrowLeft } from "lucide-react"
import { ProductForm } from "../product-form"

interface EditProductPageProps {
  params: Promise<{ id: string }>
}

export default async function EditProductPage({ params }: EditProductPageProps) {
  const { id } = await params
  const supabase = await createClient()
  const { data: { user } } = await supabase.auth.getUser()

  if (!user) {
    redirect("/auth/login")
  }

  // Get the product
  const { data: product, error } = await supabase
    .from("products")
    .select("*")
    .eq("id", id)
    .eq("seller_id", user.id)
    .single()

  if (error || !product) {
    notFound()
  }

  // Get categories
  const { data: categories } = await supabase
    .from("categories")
    .select("*")
    .eq("is_active", true)
    .order("sort_order")

  // Get existing product files
  const { data: files } = await supabase
    .from("product_files")
    .select("content")
    .eq("product_id", id)
    .eq("is_sold", false)

  const existingFiles = files?.map(f => f.content) || []

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
        <h1 className="text-3xl font-bold text-foreground">Edit Product</h1>
        <p className="text-muted-foreground mt-1">
          Update your product details
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Product Details</CardTitle>
          <CardDescription>
            Make changes to your product. Changes may require re-approval.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <ProductForm 
            categories={categories || []} 
            product={product}
            existingFiles={existingFiles}
          />
        </CardContent>
      </Card>
    </div>
  )
}
