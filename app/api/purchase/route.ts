import { createClient } from "@/lib/supabase/server"
import { NextResponse } from "next/server"

export async function POST(request: Request) {
  try {
    const supabase = await createClient()
    
    // Get current user
    const { data: { user }, error: authError } = await supabase.auth.getUser()
    
    if (authError || !user) {
      return NextResponse.json(
        { error: "Please sign in to make a purchase" },
        { status: 401 }
      )
    }

    const body = await request.json()
    const { productId, quantity = 1 } = body

    if (!productId) {
      return NextResponse.json(
        { error: "Product ID is required" },
        { status: 400 }
      )
    }

    if (quantity < 1 || quantity > 10) {
      return NextResponse.json(
        { error: "Quantity must be between 1 and 10" },
        { status: 400 }
      )
    }

    // Get buyer profile
    const { data: buyerProfile, error: buyerError } = await supabase
      .from("profiles")
      .select("*")
      .eq("id", user.id)
      .single()

    if (buyerError || !buyerProfile) {
      return NextResponse.json(
        { error: "User profile not found" },
        { status: 404 }
      )
    }

    // Get product with seller info
    const { data: product, error: productError } = await supabase
      .from("products")
      .select("*")
      .eq("id", productId)
      .single()

    if (productError || !product) {
      return NextResponse.json(
        { error: "Product not found" },
        { status: 404 }
      )
    }

    // Validation checks
    if (product.seller_id === user.id) {
      return NextResponse.json(
        { error: "You cannot purchase your own product" },
        { status: 400 }
      )
    }

    if (!product.is_active) {
      return NextResponse.json(
        { error: "This product is no longer available" },
        { status: 400 }
      )
    }

    if (product.stock < quantity) {
      return NextResponse.json(
        { error: `Only ${product.stock} items available` },
        { status: 400 }
      )
    }

    const totalPrice = product.price * quantity

    if (buyerProfile.balance < totalPrice) {
      return NextResponse.json(
        { error: "Insufficient balance" },
        { status: 400 }
      )
    }

    // For instant delivery, get available product files
    let deliveryData: string | null = null
    let fileIds: string[] = []

    if (product.delivery_type === "instant") {
      const { data: files } = await supabase
        .from("product_files")
        .select("id, content")
        .eq("product_id", productId)
        .eq("is_sold", false)
        .limit(quantity)

      if (!files || files.length < quantity) {
        return NextResponse.json(
          { error: "Not enough stock available for instant delivery" },
          { status: 400 }
        )
      }

      deliveryData = files.map(f => f.content).join("\n---\n")
      fileIds = files.map(f => f.id)
    }

    // Create the order
    const { data: order, error: orderError } = await supabase
      .from("orders")
      .insert({
        buyer_id: user.id,
        seller_id: product.seller_id,
        product_id: productId,
        quantity,
        unit_price: product.price,
        total_price: totalPrice,
        status: product.delivery_type === "instant" ? "completed" : "pending",
        delivery_data: deliveryData,
        completed_at: product.delivery_type === "instant" ? new Date().toISOString() : null,
      })
      .select()
      .single()

    if (orderError || !order) {
      console.error("Order creation error:", orderError)
      return NextResponse.json(
        { error: "Failed to create order" },
        { status: 500 }
      )
    }

    // Update buyer balance
    const { error: buyerUpdateError } = await supabase
      .from("profiles")
      .update({
        balance: buyerProfile.balance - totalPrice,
        total_purchases: (buyerProfile.total_purchases || 0) + totalPrice,
      })
      .eq("id", user.id)

    if (buyerUpdateError) {
      console.error("Buyer update error:", buyerUpdateError)
      // Note: In production, you'd want to rollback the order here
    }

    // Update seller balance
    const { data: sellerProfile } = await supabase
      .from("profiles")
      .select("balance, total_sales")
      .eq("id", product.seller_id)
      .single()

    if (sellerProfile) {
      const { error: sellerUpdateError } = await supabase
        .from("profiles")
        .update({
          balance: (sellerProfile.balance || 0) + totalPrice,
          total_sales: (sellerProfile.total_sales || 0) + totalPrice,
        })
        .eq("id", product.seller_id)

      if (sellerUpdateError) {
        console.error("Seller update error:", sellerUpdateError)
      }
    }

    // Update product stock
    const { error: stockError } = await supabase
      .from("products")
      .update({
        stock: product.stock - quantity,
        total_sales: (product.total_sales || 0) + quantity,
      })
      .eq("id", productId)

    if (stockError) {
      console.error("Stock update error:", stockError)
    }

    // Mark product files as sold (for instant delivery)
    if (fileIds.length > 0) {
      const { error: fileUpdateError } = await supabase
        .from("product_files")
        .update({
          is_sold: true,
          sold_at: new Date().toISOString(),
          order_id: order.id,
        })
        .in("id", fileIds)

      if (fileUpdateError) {
        console.error("File update error:", fileUpdateError)
      }
    }

    return NextResponse.json({
      success: true,
      orderId: order.id,
      deliveryData: deliveryData,
      message: product.delivery_type === "instant" 
        ? "Purchase complete! Check your delivery data."
        : "Purchase complete! The seller will deliver your product soon.",
    })

  } catch (error) {
    console.error("Purchase error:", error)
    return NextResponse.json(
      { error: "An unexpected error occurred" },
      { status: 500 }
    )
  }
}
