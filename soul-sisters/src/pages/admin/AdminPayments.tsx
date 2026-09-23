import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Top } from '../../components/Layout'
import { useStore } from '../../store'

export function AdminPayments() {
  const saved = useStore((s) => s.razorpayKeyId)
  const setKey = useStore((s) => s.setRazorpayKey)
  const nav = useNavigate()
  const [key, setLocal] = useState(saved || (import.meta.env.VITE_RAZORPAY_KEY_ID as string) || '')
  const [ok, setOk] = useState(false)

  return (
    <div className="app-scroll">
      <Top title="Payments" back />
      <div className="pad">
        <div className="tiny">Razorpay</div>
        <h1 className="serif" style={{ fontSize: 32, margin: '4px 0 8px' }}>Live checkout</h1>
        <p className="muted">
          Orders are created only after Razorpay confirms payment. Cash on delivery is off. Create a free account at{' '}
          <a href="https://dashboard.razorpay.com/signup" target="_blank" rel="noreferrer">
            dashboard.razorpay.com
          </a>
          , then paste your <b>Key ID</b> (starts with rzp_test_ or rzp_live_).
        </p>
        <ol className="steps">
          <li>Sign up on Razorpay and complete KYC for live collections.</li>
          <li>Open Settings → API Keys. Use test keys first.</li>
          <li>Paste Key ID below. Never paste the Key Secret here.</li>
          <li>
            For extra security, add <code>RAZORPAY_KEY_ID</code> and <code>RAZORPAY_KEY_SECRET</code> in Netlify → Site
            settings → Environment variables, then redeploy from Git so the server can create Razorpay orders.
          </li>
        </ol>
        <div className="field">
          <label>Razorpay Key ID</label>
          <input
            value={key}
            onChange={(e) => setLocal(e.target.value)}
            placeholder="rzp_test_xxxxxxxxxxxx"
            autoComplete="off"
          />
        </div>
        <button
          className="btn primary full"
          type="button"
          onClick={() => {
            setKey(key)
            setOk(true)
          }}
        >
          Save payment key
        </button>
        {ok && <p className="muted">Saved. Checkout will open the Razorpay window.</p>}
        <button className="btn ghost full" style={{ marginTop: 8 }} type="button" onClick={() => nav('/admin')}>
          Back to studio
        </button>
      </div>
    </div>
  )
}
