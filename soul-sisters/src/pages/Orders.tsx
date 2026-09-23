import { Link, useParams } from 'react-router-dom'
import { Top } from '../components/Layout'
import { inr, useSession, useStore } from '../store'

export function Orders() {
  const user = useSession()
  const allOrders = useStore((s) => s.orders)
  const orders = allOrders.filter((o) => (user?.role === 'admin' ? true : o.userId === user?.id))
  return (
    <>
      <Top title="Orders" back />
      <div className="pad">
        {orders.length === 0 ? (
          <div className="empty">
            <h2 className="serif">No orders yet</h2>
          </div>
        ) : (
          orders.map((o) => (
            <Link key={o.id} className="card" style={{ display: 'block', padding: 12, marginBottom: 10 }} to={`/orders/${o.id}`}>
              <div className="row">
                <b>{o.id}</b>
                <span className={`pill ${o.status}`}>{o.status}</span>
              </div>
              <div className="muted">{new Date(o.createdAt).toLocaleString('en-IN')} · {o.payMethod.toUpperCase()}</div>
              <div className="row" style={{ marginTop: 6 }}>
                <span>{o.lines.length} piece(s)</span>
                <b>{inr(o.total)}</b>
              </div>
            </Link>
          ))
        )}
      </div>
    </>
  )
}

export function OrderDetail() {
  const { id } = useParams()
  const order = useStore((s) => s.orders.find((o) => o.id === id))
  if (!order) return <div className="empty">Missing order</div>
  return (
    <>
      <Top title={order.id} back />
      <div className="pad">
        <span className={`pill ${order.status}`}>{order.status}</span>
        <p className="muted">{new Date(order.createdAt).toLocaleString('en-IN')}</p>
        {order.lines.map((l) => (
          <div className="list-item" key={l.sku}>
            <img className="thumb-sm" src={l.image} alt="" />
            <div>
              <b>{l.name}</b>
              <div className="muted">{l.color} · {l.size} · {l.sku}</div>
              <div>{inr(l.price)} × {l.qty}</div>
            </div>
          </div>
        ))}
        <div className="card" style={{ padding: 14 }}>
          <div className="row"><span>Paid</span><b>{order.paid ? 'Yes' : 'Pending'}</b></div>
          <div className="row"><span>Method</span><b>{order.payMethod.toUpperCase()}</b></div>
          {order.paymentId && <div className="row"><span>Payment ID</span><span>{order.paymentId}</span></div>}
          <div className="row"><span>Ship to</span><span style={{ textAlign: 'right', maxWidth: 220 }}>{order.address}</span></div>
          <div className="row"><span>Total</span><b>{inr(order.total)}</b></div>
        </div>
      </div>
    </>
  )
}
