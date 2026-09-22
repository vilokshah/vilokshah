import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Top } from '../components/Layout'
import { inr, useSession, useStore } from '../store'
import type { PayMethod } from '../types'

export function Checkout() {
  const user = useSession()
  const cart = useStore((s) => s.cart)
  const products = useStore((s) => s.products)
  const checkout = useStore((s) => s.checkout)
  const update = useStore((s) => s.updateProfile)
  const nav = useNavigate()
  const [method, setMethod] = useState<PayMethod>('upi')
  const [upi, setUpi] = useState('ananya@okaxis')
  const [card, setCard] = useState('4111 1111 1111 1111')
  const [cvv, setCvv] = useState('123')
  const [exp, setExp] = useState('12/28')
  const [address, setAddress] = useState(
    user ? `${user.address}${user.city ? `, ${user.city}` : ''} ${user.pincode}`.trim() : '',
  )
  const [paying, setPaying] = useState(false)
  const [done, setDone] = useState<string | null>(null)
  const [err, setErr] = useState('')

  const sub = cart.reduce((a, c) => {
    const p = products.find((x) => x.id === c.productId)
    return a + (p?.price || 0) * c.qty
  }, 0)
  const ship = sub >= 2990 ? 0 : 99
  const disc = sub >= 8000 ? 400 : 0
  const total = sub + ship - disc

  if (done) {
    return (
      <>
        <Top title="Payment" />
        <div className="pad" style={{ textAlign: 'center', paddingTop: 48 }}>
          <div className="tiny">Thank you, sister</div>
          <h1 className="serif" style={{ fontSize: 40 }}>Order {done}</h1>
          <p className="muted">
            {method === 'cod' ? 'Pay when your pieces arrive.' : 'Payment captured. The atelier is packing with tissue and a note.'}
          </p>
          <button className="btn primary full" onClick={() => nav('/orders')}>Track order</button>
          <button className="btn ghost full" style={{ marginTop: 8 }} onClick={() => nav('/home')}>
            Continue shopping
          </button>
        </div>
      </>
    )
  }

  return (
    <>
      <Top title="Checkout" back />
      <div className="pad">
        <div className="tiny">Deliver to</div>
        <div className="field">
          <label>Address</label>
          <textarea rows={3} value={address} onChange={(e) => setAddress(e.target.value)} />
        </div>
        <div className="tiny">Pay {inr(total)}</div>
        {([
          ['upi', 'UPI — Instant'],
          ['card', 'Card — Visa / Mastercard / RuPay'],
          ['wallet', 'Soul Wallet'],
          ['cod', 'Cash on delivery'],
        ] as const).map(([id, label]) => (
          <div key={id} className={`pay-opt ${method === id ? 'on' : ''}`} onClick={() => setMethod(id)}>
            <input type="radio" checked={method === id} readOnly />
            {label}
          </div>
        ))}
        {method === 'upi' && (
          <div className="field">
            <label>UPI ID</label>
            <input value={upi} onChange={(e) => setUpi(e.target.value)} />
          </div>
        )}
        {method === 'card' && (
          <>
            <div className="field">
              <label>Card number</label>
              <input value={card} onChange={(e) => setCard(e.target.value)} />
            </div>
            <div className="row">
              <div className="field" style={{ flex: 1 }}>
                <label>Expiry</label>
                <input value={exp} onChange={(e) => setExp(e.target.value)} />
              </div>
              <div className="field" style={{ flex: 1 }}>
                <label>CVV</label>
                <input value={cvv} onChange={(e) => setCvv(e.target.value)} />
              </div>
            </div>
          </>
        )}
        {method === 'wallet' && <p className="muted">Soul Wallet balance · ₹12,400 (demo)</p>}
        {err && <p className="err">{err}</p>}
        <button
          className="btn primary full"
          disabled={paying}
          onClick={() => {
            if (!address || address.length < 8) {
              setErr('Please add a delivery address.')
              return
            }
            if (method === 'upi' && !upi.includes('@')) {
              setErr('Enter a valid UPI ID.')
              return
            }
            if (method === 'card' && card.replace(/\s/g, '').length < 12) {
              setErr('Enter a valid card number.')
              return
            }
            setPaying(true)
            update({ address, city: user?.city || 'Mumbai', pincode: user?.pincode || '' })
            setTimeout(() => {
              const order = checkout(method, address)
              setPaying(false)
              if (order) setDone(order.id)
            }, 900)
          }}
        >
          {paying ? 'Securing payment…' : method === 'cod' ? `Place order · ${inr(total)}` : `Pay ${inr(total)}`}
        </button>
        <p className="muted" style={{ marginTop: 10, textAlign: 'center' }}>
          Demo gateway — no real charge. PCI-ready UI for Razorpay / Stripe later.
        </p>
      </div>
    </>
  )
}
