import type { ReactNode } from 'react'
import { NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom'
import { Bell, Home, Search, ShoppingBag, UserRound, Sparkles } from 'lucide-react'
import { useSession, useStore, visibleNotes } from '../store'

export function Layout() {
  const location = useLocation()
  const cart = useStore((s) => s.cart)
  const user = useSession()
  const notes = useStore((s) => visibleNotes(user, s.notifications))
  const unread = notes.filter((n) => user && !n.readBy.includes(user.id)).length
  const qty = cart.reduce((a, c) => a + c.qty, 0)
  const hideTabs = location.pathname.startsWith('/checkout') || location.pathname.startsWith('/admin')
  return (
    <>
      <div className="app-scroll">
        <Outlet />
      </div>
      {!hideTabs && (
        <nav className="tabbar">
          <NavLink to="/home" className={({ isActive }) => (isActive ? 'active' : '')}>
            <Home size={20} />
            Home
          </NavLink>
          <NavLink to="/shop" className={({ isActive }) => (isActive ? 'active' : '')}>
            <Search size={20} />
            Shop
          </NavLink>
          <NavLink to="/cart" className={({ isActive }) => (isActive ? 'active' : '')} style={{ position: 'relative' }}>
            <span style={{ position: 'relative' }}>
              <ShoppingBag size={20} />
              {qty > 0 && <span className="badge">{qty}</span>}
            </span>
            Bag
          </NavLink>
          <NavLink to="/alerts" className={({ isActive }) => (isActive ? 'active' : '')} style={{ position: 'relative' }}>
            <span style={{ position: 'relative' }}>
              <Bell size={20} />
              {unread > 0 && <span className="badge">{unread}</span>}
            </span>
            Alerts
          </NavLink>
          <NavLink to="/profile" className={({ isActive }) => (isActive ? 'active' : '')}>
            {user?.role === 'admin' ? <Sparkles size={20} /> : <UserRound size={20} />}
            {user?.role === 'admin' ? 'Studio' : 'Me'}
          </NavLink>
        </nav>
      )}
    </>
  )
}

export function Top({
  title,
  back,
  right,
}: {
  title?: string
  back?: boolean
  right?: ReactNode
}) {
  const nav = useNavigate()
  return (
    <header className="topbar">
      {back ? (
        <button className="icon-btn" type="button" onClick={() => nav(-1)}>
          ←
        </button>
      ) : (
        <div className="brand-mark">
          <strong>Soul Sisters</strong>
          <span>Atelier</span>
        </div>
      )}
      {title && back && <b style={{ flex: 1, textAlign: 'center' }}>{title}</b>}
      <div>{right}</div>
    </header>
  )
}
