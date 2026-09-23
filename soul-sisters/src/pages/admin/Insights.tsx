import { useMemo, useState } from 'react'
import { Bar, BarChart, CartesianGrid, Cell, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { Top } from '../../components/Layout'
import { categoryLabel, inr, useStore } from '../../store'
import type { OrderStatus } from '../../types'

const COLORS = ['#8b2e4a', '#c45c7a', '#c4a574', '#4a3338', '#2f6f4e', '#1c2740', '#d4a5b0', '#6b1d33']

export function AdminAnalytics() {
  const orders = useStore((s) => s.orders)
  const products = useStore((s) => s.products)
  const live = orders.filter((o) => o.status !== 'cancelled')
  const revenue = live.reduce((a, o) => a + o.total, 0)
  const aov = live.length ? Math.round(revenue / live.length) : 0
  const byDay = useMemo(() => {
    const map = new Map<string, number>()
    live.forEach((o) => {
      const k = new Date(o.createdAt).toLocaleDateString('en-IN', { day: '2-digit', month: 'short' })
      map.set(k, (map.get(k) || 0) + o.total)
    })
    return [...map.entries()].map(([name, total]) => ({ name, total })).slice(-10)
  }, [live])
  const byCat = useMemo(() => {
    const map = new Map<string, number>()
    live.forEach((o) =>
      o.lines.forEach((l) => {
        const p = products.find((x) => x.id === l.productId)
        const k = p ? categoryLabel(p.category) : 'Other'
        map.set(k, (map.get(k) || 0) + l.price * l.qty)
      }),
    )
    return [...map.entries()].map(([name, value]) => ({ name, value }))
  }, [live, products])
  const top = useMemo(() => {
    const map = new Map<string, number>()
    live.forEach((o) => o.lines.forEach((l) => map.set(l.name, (map.get(l.name) || 0) + l.qty)))
    return [...map.entries()].sort((a, b) => b[1] - a[1]).slice(0, 5)
  }, [live])
  const maxTop = top[0]?.[1] || 1

  return (
    <div className="app-scroll">
      <Top title="Sales analysis" back />
      <div className="pad">
        <div className="grid2">
          <div className="kpi"><span className="tiny">Revenue</span><b>{inr(revenue)}</b></div>
          <div className="kpi"><span className="tiny">AOV</span><b>{inr(aov)}</b></div>
          <div className="kpi"><span className="tiny">Orders</span><b>{live.length}</b></div>
          <div className="kpi"><span className="tiny">Paid</span><b>{live.filter((o) => o.paid).length}</b></div>
        </div>
        <h3 className="serif">Daily take</h3>
        <div style={{ height: 200 }}>
          <ResponsiveContainer width="100%" height="100%">
            <BarChart data={byDay}>
              <CartesianGrid stroke="#eadfd6" vertical={false} />
              <XAxis dataKey="name" tick={{ fontSize: 10 }} />
              <YAxis tick={{ fontSize: 10 }} />
              <Tooltip formatter={(v) => inr(Number(v))} />
              <Bar dataKey="total" fill="#8b2e4a" radius={[6, 6, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        </div>
        <h3 className="serif">By category</h3>
        <div style={{ height: 200 }}>
          <ResponsiveContainer width="100%" height="100%">
            <PieChart>
              <Pie data={byCat} dataKey="value" nameKey="name" innerRadius={48} outerRadius={72}>
                {byCat.map((_, i) => (
                  <Cell key={i} fill={COLORS[i % COLORS.length]} />
                ))}
              </Pie>
              <Tooltip formatter={(v) => inr(Number(v))} />
            </PieChart>
          </ResponsiveContainer>
        </div>
        <h3 className="serif">Best sellers</h3>
        {top.map(([name, qty]) => (
          <div key={name} style={{ marginBottom: 10 }}>
            <div className="row"><span>{name}</span><b>{qty}</b></div>
            <div className="stat-line"><div style={{ width: `${(qty / maxTop) * 100}%` }} /></div>
          </div>
        ))}
      </div>
    </div>
  )
}

export function AdminOrders() {
  const orders = useStore((s) => s.orders)
  const setStatus = useStore((s) => s.setOrderStatus)
  const statuses: OrderStatus[] = ['placed', 'paid', 'packed', 'shipped', 'delivered', 'cancelled']
  return (
    <div className="app-scroll">
      <Top title="Orders desk" back />
      <div className="pad">
        {orders.map((o) => (
          <div className="card" key={o.id} style={{ padding: 12, marginBottom: 10 }}>
            <div className="row">
              <b>{o.id}</b>
              <span className={`pill ${o.status}`}>{o.status}</span>
            </div>
            <div className="muted">{o.lines.map((l) => l.name).join(', ')}</div>
            <div className="row" style={{ marginTop: 6 }}>
              <span>{inr(o.total)} · {o.payMethod}</span>
            </div>
            <select
              style={{ marginTop: 8, width: '100%', padding: 8, borderRadius: 10, border: '1px solid var(--line)' }}
              value={o.status}
              onChange={(e) => setStatus(o.id, e.target.value as OrderStatus)}
            >
              {statuses.map((s) => (
                <option key={s} value={s}>{s}</option>
              ))}
            </select>
          </div>
        ))}
      </div>
    </div>
  )
}

export function AdminAlerts() {
  const send = useStore((s) => s.sendNotification)
  const notes = useStore((s) => s.notifications)
  const [title, setTitle] = useState('Festive preview is live')
  const [body, setBody] = useState('Sisters, the velvet saree edit just dropped.')
  const [audience, setAudience] = useState<'all' | 'admin'>('all')
  const [sent, setSent] = useState(false)
  return (
    <div className="app-scroll">
      <Top title="Notifications" back />
      <div className="pad">
        <div className="field"><label>Title</label><input value={title} onChange={(e) => setTitle(e.target.value)} /></div>
        <div className="field"><label>Message</label><textarea rows={3} value={body} onChange={(e) => setBody(e.target.value)} /></div>
        <div className="field">
          <label>Audience</label>
          <select value={audience} onChange={(e) => setAudience(e.target.value as 'all' | 'admin')}>
            <option value="all">All sisters (customers + admin)</option>
            <option value="admin">Studio only</option>
          </select>
        </div>
        <button
          className="btn primary full"
          type="button"
          onClick={() => {
            send(title, body, audience)
            setSent(true)
          }}
        >
          Send notification
        </button>
        {sent && <p className="muted">Delivered to in-app inboxes.</p>}
        <h3 className="serif">Recent</h3>
        {notes.slice(0, 8).map((n) => (
          <div className="note" key={n.id}>
            <b>{n.title}</b>
            <p className="muted" style={{ margin: '4px 0 0' }}>{n.body}</p>
          </div>
        ))}
      </div>
    </div>
  )
}
