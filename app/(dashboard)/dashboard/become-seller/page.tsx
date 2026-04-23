import { createClient } from "@/lib/supabase/server"
import { redirect } from "next/navigation"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Store, CheckCircle, Shield, Zap, DollarSign, Users } from "lucide-react"
import { BecomeSellerButton } from "./become-seller-button"

export default async function BecomeSellerPage() {
  const supabase = await createClient()
  const { data: { user } } = await supabase.auth.getUser()

  if (!user) {
    redirect("/auth/login")
  }

  // Check if user is already a seller
  const { data: profile } = await supabase
    .from("profiles")
    .select("role")
    .eq("id", user.id)
    .single()

  if (profile?.role === "seller" || profile?.role === "reseller" || profile?.role === "admin") {
    redirect("/dashboard/products")
  }

  const benefits = [
    {
      icon: DollarSign,
      title: "Competitive Fees",
      description: "Only 5% commission on sales. Keep more of what you earn.",
    },
    {
      icon: Zap,
      title: "Instant Payouts",
      description: "Withdraw your earnings anytime to your preferred payment method.",
    },
    {
      icon: Shield,
      title: "Seller Protection",
      description: "Protection against fraudulent disputes and chargebacks.",
    },
    {
      icon: Users,
      title: "Growing Marketplace",
      description: "Access to thousands of active buyers looking for digital products.",
    },
  ]

  return (
    <div className="max-w-3xl mx-auto space-y-8">
      <div className="text-center">
        <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary/10 text-primary mb-4">
          <Store className="h-8 w-8" />
        </div>
        <h1 className="text-3xl font-bold text-foreground">Become a Seller</h1>
        <p className="text-muted-foreground mt-2 max-w-lg mx-auto">
          Start selling digital products on NullNet and reach thousands of potential customers.
        </p>
      </div>

      {/* Benefits */}
      <div className="grid sm:grid-cols-2 gap-4">
        {benefits.map((benefit) => (
          <Card key={benefit.title}>
            <CardHeader className="pb-2">
              <div className="flex items-center gap-3">
                <div className="p-2 rounded-lg bg-primary/10">
                  <benefit.icon className="h-5 w-5 text-primary" />
                </div>
                <CardTitle className="text-base">{benefit.title}</CardTitle>
              </div>
            </CardHeader>
            <CardContent>
              <p className="text-sm text-muted-foreground">
                {benefit.description}
              </p>
            </CardContent>
          </Card>
        ))}
      </div>

      {/* Requirements */}
      <Card>
        <CardHeader>
          <CardTitle>Requirements</CardTitle>
          <CardDescription>
            Make sure you meet these requirements before applying
          </CardDescription>
        </CardHeader>
        <CardContent>
          <ul className="space-y-3">
            {[
              "Verified email address",
              "Complete profile information",
              "Agree to seller terms and conditions",
              "Provide valid payment information for withdrawals",
            ].map((requirement) => (
              <li key={requirement} className="flex items-center gap-3">
                <CheckCircle className="h-5 w-5 text-success" />
                <span className="text-foreground">{requirement}</span>
              </li>
            ))}
          </ul>
        </CardContent>
      </Card>

      {/* Apply Section */}
      <Card>
        <CardHeader>
          <CardTitle>Ready to Start Selling?</CardTitle>
          <CardDescription>
            Click below to upgrade your account to a seller account
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="rounded-lg bg-muted p-4">
            <div className="flex items-center justify-between mb-2">
              <span className="font-medium text-foreground">Seller Account</span>
              <Badge variant="success">Free</Badge>
            </div>
            <ul className="space-y-1 text-sm text-muted-foreground">
              <li>List unlimited products</li>
              <li>Automatic and manual delivery options</li>
              <li>Real-time sales analytics</li>
              <li>Priority customer support</li>
            </ul>
          </div>
          
          <BecomeSellerButton />
          
          <p className="text-xs text-muted-foreground text-center">
            By becoming a seller, you agree to our seller terms and conditions.
          </p>
        </CardContent>
      </Card>
    </div>
  )
}
