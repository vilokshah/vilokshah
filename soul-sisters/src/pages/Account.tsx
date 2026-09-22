import { Link, useNavigate } from 'react-router-dom'
import { Top } from '../components/Layout'
import { useSession, useStore, visibleNotes } from '../store'

export function Profile() {
  const user = useSession()
  const update = useStore((s) => s.updateProfile)
  const logout = useStore((s) => s.logout)
  const nav = useNavigate()
  if (!user) return null
  return (
    <>
      <Top />
      <div className="pad">
        <div className="tiny">{user.role === 'admin' ? 'Founder studio' : 'Member'}</div>
        <h1 className="serif" style={{ fontSize: 36, margin: '4px 0 4px' }}>{user.name}</h1>
        <p className="muted">{user.email}</p>
        {user.role === 'admin' && (
          <Link className="btn primary full" to="/admin">Open brand studio</Link>
        )}
        <div style={{ height: 12 }} />
        <div className="field">
          <label>Name</label>
          <input value={user.name} onChange={(e) => update({ name: e.target.value })} />
        </div>
        <div className="field">
          <label>Phone</label>
          <input value={user.phone} onChange={(e) => update({ phone: e.target.value })} />
        </div>
        <div className="field">
          <label>Address</label>
          <textarea rows={2} value={user.address} onChange={(e) => update({ address: e.target.value })} />
        </div>
        <div className="row">
          <div className="field" style={{ flex: 1 }}>
            <label>City</label>
            <input value={user.city} onChange={(e) => update({ city: e.target.value })} />
          </div>
          <div className="field" style={{ flex: 1 }}>
            <label>PIN</label>
            <input value={user.pincode} onChange={(e) => update({ pincode: e.target.value })} />
          </div>
        </div>
        <Link className="btn ghost full" to="/orders">My orders</Link>
        <Link className="btn ghost full" to="/wishlist" style={{ marginTop: 8 }}>Wishlist</Link>
        <button
          className="btn dark full"
          style={{ marginTop: 16 }}
          onClick={() => {
            logout()
            nav('/')
          }}
        >
          Log out
        </button>
      </div>
    </>
  )
}

export function Alerts() {
  const user = useSession()
  const all = useStore((s) => s.notifications)
  const mark = useStore((s) => s.markRead)
  const notes = visibleNotes(user, all)
  return (
    <>
      <Top title="Alerts" />
      <div className="pad">
        {notes.length === 0 && <div className="empty"><h2 className="serif">You’re all caught up</h2></div>}
        {notes.map((n) => {
          const unread = user && !n.readBy.includes(user.id)
          return (
            <button
              key={n.id}
              type="button"
              className={`note ${unread ? 'unread' : ''}`}
              style={{ width: '100%', textAlign: 'left' }}
              onClick={() => mark(n.id)}
            >
              <div className="row">
                <b>{n.title}</b>
                {unread && <span className="pill">new</span>}
              </div>
              <p className="muted" style={{ margin: '6px 0 0' }}>{n.body}</p>
              <div className="tiny" style={{ marginTop: 8 }}>{new Date(n.createdAt).toLocaleString('en-IN')}</div>
            </button>
          )
        })}
      </div>
    </>
  )
}
