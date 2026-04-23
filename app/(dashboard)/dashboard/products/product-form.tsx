'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Loader2, Plus, Trash2 } from 'lucide-react'
import { createClient } from '@/lib/supabase/client'

interface Category {
  id: string
  name: string
  slug: string
}

interface Product {
  id: string
  title: string
  description: string | null
  price: number
  stock: number
  category_id: string | null
  delivery_type: string
  is_active: boolean
}

interface ProductFormProps {
  categories: Category[]
  product?: Product
  existingFiles?: string[]
}

export function ProductForm({ categories, product, existingFiles = [] }: ProductFormProps) {
  const router = useRouter()
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const [title, setTitle] = useState(product?.title || '')
  const [description, setDescription] = useState(product?.description || '')
  const [price, setPrice] = useState(product?.price?.toString() || '')
  const [categoryId, setCategoryId] = useState(product?.category_id || '')
  const [deliveryType, setDeliveryType] = useState(product?.delivery_type || 'instant')
  const [stock, setStock] = useState(product?.stock?.toString() || '0')
  const [files, setFiles] = useState<string[]>(existingFiles)
  const [newFile, setNewFile] = useState('')

  const isEditing = !!product

  const addFile = () => {
    if (newFile.trim()) {
      setFiles([...files, newFile.trim()])
      setNewFile('')
    }
  }

  const removeFile = (index: number) => {
    setFiles(files.filter((_, i) => i !== index))
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setLoading(true)
    setError(null)

    try {
      const supabase = createClient()
      const { data: { user } } = await supabase.auth.getUser()

      if (!user) {
        setError('Please sign in to continue')
        return
      }

      const productData = {
        seller_id: user.id,
        title: title.trim(),
        description: description.trim() || null,
        price: parseFloat(price),
        category_id: categoryId || null,
        delivery_type: deliveryType,
        stock: deliveryType === 'instant' ? files.length : parseInt(stock),
        is_active: true,
        is_approved: false, // Needs admin approval
      }

      let productId = product?.id

      if (isEditing) {
        const { error: updateError } = await supabase
          .from('products')
          .update(productData)
          .eq('id', product.id)

        if (updateError) throw updateError
      } else {
        const { data: newProduct, error: insertError } = await supabase
          .from('products')
          .insert(productData)
          .select()
          .single()

        if (insertError) throw insertError
        productId = newProduct.id
      }

      // Handle product files for instant delivery
      if (deliveryType === 'instant' && productId) {
        // Delete existing files if editing
        if (isEditing) {
          await supabase
            .from('product_files')
            .delete()
            .eq('product_id', productId)
            .eq('is_sold', false)
        }

        // Add new files
        if (files.length > 0) {
          const fileRecords = files.map(content => ({
            product_id: productId,
            content,
            is_sold: false,
          }))

          const { error: filesError } = await supabase
            .from('product_files')
            .insert(fileRecords)

          if (filesError) throw filesError
        }
      }

      router.push('/dashboard/products')
      router.refresh()
    } catch (err) {
      console.error('Error saving product:', err)
      setError(err instanceof Error ? err.message : 'Failed to save product')
    } finally {
      setLoading(false)
    }
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      {error && (
        <div className="p-4 rounded-lg bg-destructive/10 border border-destructive/20 text-destructive text-sm">
          {error}
        </div>
      )}

      <div className="grid gap-4 md:grid-cols-2">
        <div className="space-y-2">
          <Label htmlFor="title">Product Title *</Label>
          <Input
            id="title"
            value={title}
            onChange={(e) => setTitle(e.target.value)}
            placeholder="e.g., Netflix Premium Account"
            required
          />
        </div>

        <div className="space-y-2">
          <Label htmlFor="category">Category</Label>
          <Select value={categoryId} onValueChange={setCategoryId}>
            <SelectTrigger>
              <SelectValue placeholder="Select a category" />
            </SelectTrigger>
            <SelectContent>
              {categories.map((category) => (
                <SelectItem key={category.id} value={category.id}>
                  {category.name}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      </div>

      <div className="space-y-2">
        <Label htmlFor="description">Description</Label>
        <Textarea
          id="description"
          value={description}
          onChange={(e) => setDescription(e.target.value)}
          placeholder="Describe your product in detail..."
          rows={4}
        />
      </div>

      <div className="grid gap-4 md:grid-cols-3">
        <div className="space-y-2">
          <Label htmlFor="price">Price (USD) *</Label>
          <Input
            id="price"
            type="number"
            step="0.01"
            min="0"
            value={price}
            onChange={(e) => setPrice(e.target.value)}
            placeholder="0.00"
            required
          />
        </div>

        <div className="space-y-2">
          <Label htmlFor="deliveryType">Delivery Type</Label>
          <Select value={deliveryType} onValueChange={setDeliveryType}>
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="instant">Instant (Auto-delivery)</SelectItem>
              <SelectItem value="manual">Manual Delivery</SelectItem>
            </SelectContent>
          </Select>
        </div>

        {deliveryType === 'manual' && (
          <div className="space-y-2">
            <Label htmlFor="stock">Stock Quantity</Label>
            <Input
              id="stock"
              type="number"
              min="0"
              value={stock}
              onChange={(e) => setStock(e.target.value)}
              placeholder="0"
            />
          </div>
        )}
      </div>

      {deliveryType === 'instant' && (
        <div className="space-y-4 p-4 rounded-lg bg-muted">
          <div>
            <Label>Product Files (Auto-delivery) *</Label>
            <p className="text-sm text-muted-foreground mt-1">
              Add the content to be delivered instantly to buyers. Each entry is one unit of stock.
            </p>
          </div>

          {files.length > 0 && (
            <div className="space-y-2">
              {files.map((file, index) => (
                <div key={index} className="flex items-center gap-2">
                  <Input
                    value={file}
                    readOnly
                    className="font-mono text-sm"
                  />
                  <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    onClick={() => removeFile(index)}
                  >
                    <Trash2 className="h-4 w-4 text-destructive" />
                  </Button>
                </div>
              ))}
            </div>
          )}

          <div className="flex gap-2">
            <Input
              value={newFile}
              onChange={(e) => setNewFile(e.target.value)}
              placeholder="e.g., email:password or license key"
              onKeyDown={(e) => {
                if (e.key === 'Enter') {
                  e.preventDefault()
                  addFile()
                }
              }}
            />
            <Button type="button" variant="outline" onClick={addFile}>
              <Plus className="h-4 w-4" />
            </Button>
          </div>

          <p className="text-sm text-muted-foreground">
            Current stock: {files.length} items
          </p>
        </div>
      )}

      <div className="flex gap-4">
        <Button type="submit" disabled={loading}>
          {loading && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
          {isEditing ? 'Update Product' : 'Create Product'}
        </Button>
        <Button
          type="button"
          variant="outline"
          onClick={() => router.push('/dashboard/products')}
        >
          Cancel
        </Button>
      </div>

      <p className="text-sm text-muted-foreground">
        * Your product will be reviewed by our team before going live.
      </p>
    </form>
  )
}
