import { Link, Navigate, Outlet, useNavigate } from 'react-router-dom'
import {
  BarChart3,
  Boxes,
  ClipboardList,
  PackagePlus,
  ScanBarcode,
  BellRing,
  ArrowLeft,
} from 'lucide-react'
import { useSession, useStore, inr } from '../../store'

export function RequireAdmin() {
  const user = useSession()
  if (!user) return <Navigate to="/login" replace />
  if (user.role !== 'admin') return <Navigate to="/home" replace />
  return <Outlet />
}

export function AdminHome() {
  const products = useStore((s) => s.products)
  const orders = useStore((s) => s.orders)
  const nav = useNavigate()
  const revenue = orders.filter((o) => o.status !== 'cancelled').reduce((a, o) => a + o.total, 0)
  const units = products.reduce((a, p) => a + p.variants.reduce((x, v) => x + v.stock, 0), 0)
  const low = products.flatMap((p) => p.variants.filter((v) => v.stock < 4)).length

  const tiles = [
    { to: '/admin/products', icon: PackagePlus, title: 'Catalog', sub: 'Add & edit pieces' },
    { to: '/admin/inventory', icon: Boxes, title: 'Inventory', sub: `${units} units · ${low} low` },
    { to: '/admin/barcodes', icon: ScanBarcode, title: 'Barcodes', sub: 'Generate & print' },
    { to: '/admin/orders', icon: ClipboardList, title: 'Orders', sub: `${orders.length} tickets` },
    { to: '/admin/analytics', icon: BarChart3, title: 'Sales analysis', sub: inr(revenue) },
    { to: '/admin/alerts', icon: BellRing, title: 'Notify sisters', sub: 'Push to the app' },
  ]

  return (
    <div className="app-scroll">
      <header className="topbar">
        <button className="icon-btn" type="button" onClick={() => nav('/home')}>
          <ArrowLeft size={18} />
        </button>
        <b>Brand studio</b>
        <span className="tiny">Admin</span>
      </header>
      <div className="pad">
        <h1 className="serif" style={{ fontSize: 34, margin: '4px 0 8px' }}>Soul Sisters HQ</h1>
        <p className="muted">Manage the women’s maison — stock, stories, and sales.</p>
        <div className="grid2" style={{ marginTop: 8 }}>
          <div className="kpi"><span className="tiny">Revenue</span><b>{inr(revenue)}</b></div>
          <div className="kpi"><span className="tiny">Open orders</span><b>{orders.filter((o) => o.status !== 'delivered' && o.status !== 'cancelled').length}</b></div>
        </div>
      </div>
      <div className="admin-nav">
        {tiles.map((t) => (
          <Link key={t.to} to={t.to} className="admin-tile">
            <t.icon size={18} color="#8b2e4a" />
            <h3>{t.title}</h3>
            <div className="muted">{t.sub}</div>
          </Link>
        ))}
      </div>
    </div>
  )
}
