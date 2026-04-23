'use client'

import Link from 'next/link'
import { usePathname } from 'next/navigation'
import { cn } from '@/lib/utils'
import {
  LayoutDashboard,
  ShoppingBag,
  Package,
  Wallet,
  Settings,
  HelpCircle,
  AlertTriangle,
  Store,
  Users,
  CreditCard,
  BarChart3,
  Heart,
} from 'lucide-react'

interface Profile {
  id: string
  username: string
  role: string
  balance: number
}

const buyerNavItems = [
  { href: '/dashboard', label: 'Overview', icon: LayoutDashboard },
  { href: '/dashboard/orders', label: 'My Orders', icon: ShoppingBag },
  { href: '/dashboard/favorites', label: 'Favorites', icon: Heart },
  { href: '/dashboard/balance', label: 'Balance', icon: Wallet },
  { href: '/dashboard/tickets', label: 'Support', icon: HelpCircle },
  { href: '/dashboard/settings', label: 'Settings', icon: Settings },
]

const sellerNavItems = [
  { href: '/dashboard', label: 'Overview', icon: LayoutDashboard },
  { href: '/dashboard/products', label: 'My Products', icon: Package },
  { href: '/dashboard/orders', label: 'Orders', icon: ShoppingBag },
  { href: '/dashboard/balance', label: 'Balance', icon: Wallet },
  { href: '/dashboard/withdrawals', label: 'Withdrawals', icon: CreditCard },
  { href: '/dashboard/tickets', label: 'Support', icon: HelpCircle },
  { href: '/dashboard/settings', label: 'Settings', icon: Settings },
]

const adminNavItems = [
  { href: '/admin', label: 'Overview', icon: LayoutDashboard },
  { href: '/admin/users', label: 'Users', icon: Users },
  { href: '/admin/products', label: 'Products', icon: Package },
  { href: '/admin/payments', label: 'Payments', icon: CreditCard },
  { href: '/admin/withdrawals', label: 'Withdrawals', icon: Wallet },
  { href: '/admin/reports', label: 'Reports', icon: AlertTriangle },
  { href: '/admin/tickets', label: 'Tickets', icon: HelpCircle },
  { href: '/admin/analytics', label: 'Analytics', icon: BarChart3 },
  { href: '/admin/settings', label: 'Settings', icon: Settings },
]

export function DashboardSidebar({ profile }: { profile: Profile | null }) {
  const pathname = usePathname()
  const role = profile?.role || 'buyer'

  let navItems = buyerNavItems
  if (role === 'seller' || role === 'reseller') {
    navItems = sellerNavItems
  } else if (role === 'admin') {
    navItems = adminNavItems
  }

  return (
    <aside className="hidden lg:flex w-64 flex-col border-r border-border min-h-[calc(100vh-65px)]">
      <nav className="flex-1 p-4 space-y-1">
        {navItems.map((item) => {
          const isActive = pathname === item.href || 
            (item.href !== '/dashboard' && item.href !== '/admin' && pathname.startsWith(item.href))
          
          return (
            <Link
              key={item.href}
              href={item.href}
              className={cn(
                'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                isActive
                  ? 'bg-primary/10 text-primary'
                  : 'text-muted-foreground hover:text-foreground hover:bg-accent'
              )}
            >
              <item.icon className="h-5 w-5" />
              {item.label}
            </Link>
          )
        })}
      </nav>

      {/* Become Seller CTA for Buyers */}
      {role === 'buyer' && (
        <div className="p-4 border-t border-border">
          <Link
            href="/dashboard/become-seller"
            className="flex items-center gap-3 px-3 py-2 rounded-lg bg-primary/10 text-primary text-sm font-medium hover:bg-primary/20 transition-colors"
          >
            <Store className="h-5 w-5" />
            Become a Seller
          </Link>
        </div>
      )}
    </aside>
  )
}
