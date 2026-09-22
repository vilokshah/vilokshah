import { Link, useNavigate } from 'react-router-dom'
import { Top } from '../components/Layout'
import { inr, useStore } from '../store'

export function Cart() {
  const cart = useStore((s) => s.cart)
  const products = useStore((s) => s.products)
  const setQty = useStore((s) => s.setQty)
  const remove = useStore((s) => s.removeFromCart)
  const nav = useNavigate()
  const lines = cart.map((c) => ({ ...c, product: products.find((p) => p.id === c.productId)! }))
  const sub = lines.reduce((a, l) => a + l.product.price * l.qty, 0)
  const ship = sub >= 2990 || sub === 0 ? 0 : 99
  const disc = sub >= 8000 ? 400 : 0

  return (
    <>
      <Top title="Bag" back />
      <div className="pad">
        {lines.length === 0 ? (
          <div className="empty">
            <h2 className="serif">Your bag is empty</h2>
            <p>The sisterhood is waiting in the shop.</p>
            <Link className="btn primary" to="/shop">Start dressing</Link>
          </div>
        ) : (
          <>
            {lines.map((l) => (
              <div className="list-item" key={l.sku}>
                <img className="thumb-sm" src={l.product.images[0]} alt="" />
                <div style={{ flex: 1 }}>
                  <b>{l.product.name}</b>
                  <div className="muted">{l.color} · {l.size} · {l.sku}</div>
                  <div className="price">{inr(l.product.price)}</div>
                  <div className="row" style={{ marginTop: 6 }}>
                    <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
                      <button className="icon-btn" type="button" onClick={() => setQty(l.sku, l.qty - 1)}>−</button>
                      {l.qty}
                      <button className="icon-btn" type="button" onClick={() => setQty(l.sku, l.qty + 1)}>+</button>
                    </div>
                    <button className="btn ghost sm" type="button" onClick={() => remove(l.sku)}>Remove</button>
                  </div>
                </div>
              </div>
            ))}
            <div className="card" style={{ padding: 14, marginTop: 8 }}>
              <div className="row"><span>Subtotal</span><b>{inr(sub)}</b></div>
              <div className="row"><span>Shipping</span><b>{ship ? inr(ship) : 'Complimentary'}</b></div>
              <div className="row"><span>Sister discount</span><b>{disc ? `− ${inr(disc)}` : '—'}</b></div>
              <hr style={{ border: 0, borderTop: '1px solid var(--line)' }} />
              <div className="row"><span>Total</span><b>{inr(sub + ship - disc)}</b></div>
            </div>
            <button className="btn primary full" style={{ marginTop: 16 }} onClick={() => nav('/checkout')}>
              Checkout
            </button>
          </>
        )}
      </div>
    </>
  )
}
