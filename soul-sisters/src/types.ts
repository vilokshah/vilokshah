export type Role = 'customer' | 'admin'

export type CategoryId =
  | 'dresses'
  | 'ethnic'
  | 'coords'
  | 'tops'
  | 'bottoms'
  | 'active'
  | 'lounge'
  | 'accessories'

export interface Category {
  id: CategoryId
  name: string
  tagline: string
  image: string
}

export interface Variant {
  size: string
  color: string
  sku: string
  barcode: string
  stock: number
}

export interface Product {
  id: string
  name: string
  subtitle: string
  description: string
  category: CategoryId
  price: number
  mrp: number
  fabric: string
  care: string
  images: string[]
  colors: string[]
  sizes: string[]
  variants: Variant[]
  rating: number
  reviews: number
  featured: boolean
  newIn: boolean
  createdAt: string
}

export interface User {
  id: string
  name: string
  email: string
  password: string
  role: Role
  phone: string
  avatar?: string
  address: string
  city: string
  pincode: string
}

export interface CartItem {
  productId: string
  sku: string
  size: string
  color: string
  qty: number
}

export type OrderStatus = 'placed' | 'paid' | 'packed' | 'shipped' | 'delivered' | 'cancelled'
export type PayMethod = 'upi' | 'card' | 'wallet' | 'cod'

export interface OrderLine {
  productId: string
  name: string
  sku: string
  size: string
  color: string
  qty: number
  price: number
  image: string
}

export interface Order {
  id: string
  userId: string
  lines: OrderLine[]
  subtotal: number
  shipping: number
  discount: number
  total: number
  status: OrderStatus
  payMethod: PayMethod
  paid: boolean
  createdAt: string
  address: string
}

export interface AppNotification {
  id: string
  title: string
  body: string
  audience: 'all' | 'admin' | string
  createdAt: string
  readBy: string[]
}

export interface PaymentDraft {
  method: PayMethod
  upiId?: string
  cardLast4?: string
}
