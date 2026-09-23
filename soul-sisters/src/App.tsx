import { useEffect } from 'react'
import { HashRouter, Navigate, Outlet, Route, Routes } from 'react-router-dom'
import { Layout } from './components/Layout'
import { useSession, useStore } from './store'
import { Welcome } from './pages/Welcome'
import { Login, Signup } from './pages/Auth'
import { Home } from './pages/Home'
import { SearchPage, Shop, Wishlist } from './pages/Shop'
import { ProductPage } from './pages/Product'
import { Cart } from './pages/Cart'
import { Checkout } from './pages/Checkout'
import { OrderDetail, Orders } from './pages/Orders'
import { Alerts, Profile } from './pages/Account'
import { AdminHome, RequireAdmin } from './pages/admin/AdminHome'
import { AdminBarcodes, AdminInventory, AdminProductForm, AdminProducts } from './pages/admin/CatalogAdmin'
import { AdminAlerts, AdminAnalytics, AdminOrders } from './pages/admin/Insights'
import { AdminPayments } from './pages/admin/AdminPayments'
import { AdminStore } from './pages/admin/AdminStore'

function ThemeSync() {
  const theme = useStore((s) => s.theme)
  useEffect(() => {
    document.documentElement.dataset.theme = theme
  }, [theme])
  return null
}

function Gate() {
  const user = useSession()
  if (!user) return <Navigate to="/" replace />
  return <Outlet />
}

function GuestOnly() {
  const user = useSession()
  if (user) return <Navigate to="/home" replace />
  return <Outlet />
}

export default function App() {
  return (
    <div className="stage">
      <div className="phone">
        <HashRouter>
          <ThemeSync />
          <Routes>
            <Route element={<GuestOnly />}>
              <Route path="/" element={<Welcome />} />
              <Route path="/login" element={<Login />} />
              <Route path="/signup" element={<Signup />} />
            </Route>
            <Route element={<Gate />}>
              <Route element={<Layout />}>
                <Route path="/home" element={<Home />} />
                <Route path="/shop" element={<Shop />} />
                <Route path="/shop/:category" element={<Shop />} />
                <Route path="/search" element={<SearchPage />} />
                <Route path="/product/:id" element={<ProductPage />} />
                <Route path="/cart" element={<Cart />} />
                <Route path="/checkout" element={<Checkout />} />
                <Route path="/orders" element={<Orders />} />
                <Route path="/orders/:id" element={<OrderDetail />} />
                <Route path="/wishlist" element={<Wishlist />} />
                <Route path="/alerts" element={<Alerts />} />
                <Route path="/profile" element={<Profile />} />
              </Route>
              <Route element={<RequireAdmin />}>
                <Route path="/admin" element={<AdminHome />} />
                <Route path="/admin/products" element={<AdminProducts />} />
                <Route path="/admin/products/:id" element={<AdminProductForm />} />
                <Route path="/admin/inventory" element={<AdminInventory />} />
                <Route path="/admin/barcodes" element={<AdminBarcodes />} />
                <Route path="/admin/analytics" element={<AdminAnalytics />} />
                <Route path="/admin/orders" element={<AdminOrders />} />
                <Route path="/admin/alerts" element={<AdminAlerts />} />
                <Route path="/admin/payments" element={<AdminPayments />} />
                <Route path="/admin/store" element={<AdminStore />} />
              </Route>
            </Route>
            <Route path="*" element={<Navigate to="/" replace />} />
          </Routes>
        </HashRouter>
      </div>
    </div>
  )
}
