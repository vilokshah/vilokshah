import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import type {
  User,
  Product,
  CartItem,
  Order,
  AppNotification,
  PayMethod,
  OrderStatus,
  Variant,
  CategoryId,
} from './types'
import { PRODUCTS, USERS, SAMPLE_ORDERS, SAMPLE_NOTES } from './data/catalog'

interface State {
  users: User[]
  sessionId: string | null
  products: Product[]
  cart: CartItem[]
  wishlist: string[]
  orders: Order[]
  notifications: AppNotification[]
  login: (email: string, password: string) => string | null
  signup: (name: string, email: string, password: string) => string | null
  logout: () => void
  updateProfile: (patch: Partial<User>) => void
  addToCart: (item: CartItem) => void
  setQty: (sku: string, qty: number) => void
  removeFromCart: (sku: string) => void
  clearCart: () => void
  toggleWish: (id: string) => void
  checkout: (payMethod: PayMethod, address: string) => Order | null
  upsertProduct: (p: Product) => void
  deleteProduct: (id: string) => void
  setStock: (sku: string, stock: number) => void
  setOrderStatus: (id: string, status: OrderStatus) => void
  sendNotification: (title: string, body: string, audience: 'all' | 'admin') => void
  markRead: (id: string) => void
}

function skuBarcode(prefix: string, color: string, size: string) {
  const sku = `${prefix}-${color.slice(0, 3).toUpperCase()}-${size}`
  const digits = sku.replace(/\D/g, '').padEnd(10, '8').slice(0, 10)
  return { sku, barcode: `890${digits}`.slice(0, 13) }
}

export function buildVariants(prefix: string, sizes: string[], colors: string[], stock: number): Variant[] {
  return colors.flatMap((color) =>
    sizes.map((size) => {
      const { sku, barcode } = skuBarcode(prefix, color, size)
      return { size, color, sku, barcode, stock }
    }),
  )
}

export function inr(n: number) {
  return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(n)
}

export function nextOrderId(orders: Order[]) {
  const nums = orders.map((o) => Number(o.id.replace(/\D/g, ''))).filter(Boolean)
  const max = nums.length ? Math.max(...nums) : 10520
  return `SS-${max + 1}`
}

export const useStore = create<State>()(
  persist(
    (set, get) => ({
      users: USERS,
      sessionId: null,
      products: PRODUCTS,
      cart: [],
      wishlist: ['p1', 'p10'],
      orders: SAMPLE_ORDERS,
      notifications: SAMPLE_NOTES,

      login: (email, password) => {
        const u = get().users.find(
          (x) => x.email.toLowerCase() === email.trim().toLowerCase() && x.password === password,
        )
        if (!u) return 'Email or password is incorrect.'
        set({ sessionId: u.id })
        return null
      },

      signup: (name, email, password) => {
        if (get().users.some((u) => u.email.toLowerCase() === email.toLowerCase())) {
          return 'An account with this email already exists.'
        }
        const user: User = {
          id: `u-${Date.now()}`,
          name,
          email,
          password,
          role: 'customer',
          phone: '',
          address: '',
          city: '',
          pincode: '',
        }
        set((s) => ({ users: [...s.users, user], sessionId: user.id }))
        return null
      },

      logout: () => set({ sessionId: null, cart: [] }),

      updateProfile: (patch) => {
        const id = get().sessionId
        if (!id) return
        set((s) => ({
          users: s.users.map((u) => (u.id === id ? { ...u, ...patch } : u)),
        }))
      },

      addToCart: (item) => {
        set((s) => {
          const existing = s.cart.find((c) => c.sku === item.sku)
          if (existing) {
            return {
              cart: s.cart.map((c) => (c.sku === item.sku ? { ...c, qty: c.qty + item.qty } : c)),
            }
          }
          return { cart: [...s.cart, item] }
        })
      },

      setQty: (sku, qty) => {
        if (qty <= 0) {
          set((s) => ({ cart: s.cart.filter((c) => c.sku !== sku) }))
          return
        }
        set((s) => ({ cart: s.cart.map((c) => (c.sku === sku ? { ...c, qty } : c)) }))
      },

      removeFromCart: (sku) => set((s) => ({ cart: s.cart.filter((c) => c.sku !== sku) })),
      clearCart: () => set({ cart: [] }),

      toggleWish: (id) =>
        set((s) => ({
          wishlist: s.wishlist.includes(id) ? s.wishlist.filter((x) => x !== id) : [...s.wishlist, id],
        })),

      checkout: (payMethod, address) => {
        const { cart, products, sessionId, orders } = get()
        if (!sessionId || cart.length === 0) return null
        const lines = cart.map((c) => {
          const p = products.find((x) => x.id === c.productId)!
          return {
            productId: p.id,
            name: p.name,
            sku: c.sku,
            size: c.size,
            color: c.color,
            qty: c.qty,
            price: p.price,
            image: p.images[0],
          }
        })
        const subtotal = lines.reduce((a, l) => a + l.price * l.qty, 0)
        const shipping = subtotal >= 2990 ? 0 : 99
        const discount = subtotal >= 8000 ? 400 : 0
        const order: Order = {
          id: nextOrderId(orders),
          userId: sessionId,
          lines,
          subtotal,
          shipping,
          discount,
          total: subtotal + shipping - discount,
          status: payMethod === 'cod' ? 'placed' : 'paid',
          payMethod,
          paid: payMethod !== 'cod',
          createdAt: new Date().toISOString(),
          address,
        }
        set((s) => ({
          orders: [order, ...s.orders],
          cart: [],
          products: s.products.map((p) => ({
            ...p,
            variants: p.variants.map((v) => {
              const line = lines.find((l) => l.sku === v.sku)
              return line ? { ...v, stock: Math.max(0, v.stock - line.qty) } : v
            }),
          })),
          notifications: [
            {
              id: `n-${Date.now()}`,
              title: `Order ${order.id} ${order.paid ? 'paid' : 'placed'}`,
              body: `${lines.length} piece${lines.length > 1 ? 's' : ''} · ${inr(order.total)} via ${payMethod.toUpperCase()}`,
              audience: 'admin',
              createdAt: new Date().toISOString(),
              readBy: [],
            },
            {
              id: `n-${Date.now()}-u`,
              title: `We’ve got your order ${order.id}`,
              body: 'The atelier is packing your pieces with tissue and a handwritten note.',
              audience: sessionId,
              createdAt: new Date().toISOString(),
              readBy: [],
            },
            ...s.notifications,
          ],
        }))
        return order
      },

      upsertProduct: (p) =>
        set((s) => {
          const i = s.products.findIndex((x) => x.id === p.id)
          if (i === -1) return { products: [p, ...s.products] }
          const next = [...s.products]
          next[i] = p
          return { products: next }
        }),

      deleteProduct: (id) => set((s) => ({ products: s.products.filter((p) => p.id !== id) })),

      setStock: (sku, stock) =>
        set((s) => ({
          products: s.products.map((p) => ({
            ...p,
            variants: p.variants.map((v) => (v.sku === sku ? { ...v, stock } : v)),
          })),
        })),

      setOrderStatus: (id, status) =>
        set((s) => {
          const order = s.orders.find((o) => o.id === id)
          const notes = [...s.notifications]
          if (order) {
            notes.unshift({
              id: `n-${Date.now()}`,
              title: `Order ${id} is ${status}`,
              body: `Your Soul Sisters order is now marked ${status}.`,
              audience: order.userId,
              createdAt: new Date().toISOString(),
              readBy: [],
            })
          }
          return {
            orders: s.orders.map((o) => (o.id === id ? { ...o, status, paid: status === 'cancelled' ? o.paid : true } : o)),
            notifications: notes,
          }
        }),

      sendNotification: (title, body, audience) =>
        set((s) => ({
          notifications: [
            {
              id: `n-${Date.now()}`,
              title,
              body,
              audience,
              createdAt: new Date().toISOString(),
              readBy: [],
            },
            ...s.notifications,
          ],
        })),

      markRead: (id) => {
        const uid = get().sessionId
        if (!uid) return
        set((s) => ({
          notifications: s.notifications.map((n) =>
            n.id === id && !n.readBy.includes(uid) ? { ...n, readBy: [...n.readBy, uid] } : n,
          ),
        }))
      },
    }),
    { name: 'soul-sisters-store' },
  ),
)

export function useSession() {
  return useStore((s) => s.users.find((u) => u.id === s.sessionId) ?? null)
}

export function visibleNotes(user: User | null, notes: AppNotification[]) {
  if (!user) return []
  return notes.filter(
    (n) => n.audience === 'all' || n.audience === user.id || (n.audience === 'admin' && user.role === 'admin'),
  )
}

export function categoryLabel(id: CategoryId) {
  const map: Record<CategoryId, string> = {
    dresses: 'Dresses',
    ethnic: 'Ethnic Wear',
    coords: 'Co-ords',
    tops: 'Tops',
    bottoms: 'Bottoms',
    active: 'Activewear',
    lounge: 'Lounge',
    accessories: 'Accessories',
  }
  return map[id]
}
