import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Top } from '../components/Layout'
import { createRazorpayOrder, loadRazorpay } from '../lib/razorpay'
import { inr, useSession, useStore } from '../store'

export function Checkout() {
  const user = useSession()
  const cart = useStore((s) => s.cart)
  const products = useStore((s) => s.products)
  const checkout = useStore((s) => s.checkout)
  const update = useStore((s) => s.updateProfile)
  const razorpayKeyId = useStore((s) => s.razorpayKeyId)
  const nav = useNavigate()
  const [address, setAddress] = useState(
    user ? `${user.address}${user.city ? `, ${user.city}` : ''} ${user.pincode}`.trim() : '',
  )
  const [paying, setPaying] = useState(false)
  const [done, setDone] = useState<string | null>(null)
  const [paymentId, setPaymentId] = useState('')
  const [err, setErr] = useState('')

  const sub = cart.reduce((a, c) => {
    const p = products.find((x) => x.id === c.productId)
    return a + (p?.price || 0) * c.qty
  }, 0)
  const ship = sub >= 2990 ? 0 : 99
  const disc = sub >= 8000 ? 400 : 0
  const total = sub + ship - disc
  const key = razorpayKeyId || (import.meta.env.VITE_RAZORPAY_KEY_ID as string | undefined) || ''

  if (done) {
    return (
      <>
        <Top title="Payment" />
        <div className="pad" style={{ textAlign: 'center', paddingTop: 48 }}>
          <div className="tiny">Payment received</div>
          <h1 className="serif" style={{ fontSize: 40 }}>Order {done}</h1>
          <p className="muted">
            Razorpay payment {paymentId} was captured. We’re packing your order.
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
        <div className="card" style={{ padding: 14, marginBottom: 14 }}>
          <div className="row"><span>Subtotal</span><b>{inr(sub)}</b></div>
          <div className="row"><span>Shipping</span><b>{ship ? inr(ship) : 'Free'}</b></div>
          <div className="row"><span>To pay</span><b>{inr(total)}</b></div>
        </div>
        <p className="muted">
          Pay with Razorpay. In the payment window you can choose <b>UPI</b> and complete with <b>Google Pay</b>,{' '}
          <b>PhonePe</b>, Paytm, or any UPI app, plus cards, net banking, and wallets. Cash on delivery is not available.
        </p>
        {!key && (
          <p className="err">
            Payments are not live yet. Sign in as admin and add your Razorpay Key ID under Brand studio → Payments. Then return here to pay.
          </p>
        )}
        {err && <p className="err">{err}</p>}
        <button
          className="btn primary full"
          disabled={paying || !key}
          onClick={async () => {
            if (!address || address.length < 8) {
              setErr('Please add a delivery address.')
              return
            }
            if (!key) {
              setErr('Razorpay Key ID is missing.')
              return
            }
            setErr('')
            setPaying(true)
            update({ address, city: user?.city || 'Mumbai', pincode: user?.pincode || '' })
            try {
              const Razorpay = await loadRazorpay()
              const orderId = await createRazorpayOrder(total * 100, `ss-${Date.now()}`)
              const rzp = new Razorpay({
                key,
                amount: total * 100,
                currency: 'INR',
                name: 'Soul Sisters',
                description: `${cart.reduce((a, c) => a + c.qty, 0)} item(s)`,
                image: `${import.meta.env.BASE_URL}logo.png`,
                order_id: orderId || undefined,
                prefill: {
                  name: user?.name,
                  email: user?.email,
                  contact: user?.phone,
                },
                theme: { color: '#1F4D3A' },
                handler: (response) => {
                  const order = checkout('razorpay', address, response.razorpay_payment_id)
                  setPaying(false)
                  if (order) {
                    setPaymentId(response.razorpay_payment_id)
                    setDone(order.id)
                  } else setErr('Payment succeeded but the bag was empty. Contact support with your payment ID.')
                },
                modal: {
                  ondismiss: () => setPaying(false),
                },
              })
              rzp.open()
            } catch (e) {
              setPaying(false)
              setErr(e instanceof Error ? e.message : 'Could not open Razorpay.')
            }
          }}
        >
          {paying ? 'Opening Razorpay…' : `Pay ${inr(total)} with Razorpay`}
        </button>
        <p className="muted" style={{ marginTop: 10, textAlign: 'center' }}>
          You will finish GPay / PhonePe / card in the Razorpay window. The order is created only after payment succeeds.
        </p>
      </div>
    </>
  )
}
