import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { BrandLogo } from '../components/BrandLogo'
import { useStore } from '../store'

export function Login() {
  const login = useStore((s) => s.login)
  const nav = useNavigate()
  const [email, setEmail] = useState('ananya@soulsisters.com')
  const [password, setPassword] = useState('sisters123')
  const [err, setErr] = useState<string | null>(null)

  return (
    <div className="app-scroll">
      <div className="pad" style={{ paddingTop: 28 }}>
        <BrandLogo />
        <div className="tiny" style={{ marginTop: 18 }}>Welcome back</div>
        <h1 className="serif" style={{ fontSize: 40, margin: '6px 0 18px' }}>Sign in</h1>
        <form
          onSubmit={(e) => {
            e.preventDefault()
            const msg = login(email, password)
            if (msg) setErr(msg)
            else nav('/home')
          }}
        >
          <div className="field">
            <label>Email</label>
            <input value={email} onChange={(e) => setEmail(e.target.value)} type="email" autoComplete="username" required />
          </div>
          <div className="field">
            <label>Password</label>
            <input value={password} onChange={(e) => setPassword(e.target.value)} type="password" autoComplete="current-password" required />
          </div>
          {err && <p className="err">{err}</p>}
          <button className="btn primary full" type="submit">Continue</button>
        </form>
        <p className="muted" style={{ marginTop: 18 }}>
          New to Soul Sisters? <Link to="/signup"><b>Join the sisterhood</b></Link>
        </p>
        <div className="card" style={{ padding: 14, marginTop: 24 }}>
          <div className="tiny">Demo access</div>
          <p className="muted" style={{ margin: '6px 0 0' }}>
            Shopper · ananya@soulsisters.com<br />
            Admin · admin@soulsisters.com<br />
            Password · sisters123
          </p>
        </div>
      </div>
    </div>
  )
}

export function Signup() {
  const signup = useStore((s) => s.signup)
  const nav = useNavigate()
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [err, setErr] = useState<string | null>(null)

  return (
    <div className="app-scroll">
      <div className="pad" style={{ paddingTop: 28 }}>
        <BrandLogo />
        <div className="tiny" style={{ marginTop: 18 }}>Women’s house</div>
        <h1 className="serif" style={{ fontSize: 40, margin: '6px 0 18px' }}>Create profile</h1>
        <form
          onSubmit={(e) => {
            e.preventDefault()
            const msg = signup(name, email, password)
            if (msg) setErr(msg)
            else nav('/home')
          }}
        >
          <div className="field">
            <label>Full name</label>
            <input value={name} onChange={(e) => setName(e.target.value)} required />
          </div>
          <div className="field">
            <label>Email</label>
            <input value={email} onChange={(e) => setEmail(e.target.value)} type="email" required />
          </div>
          <div className="field">
            <label>Password</label>
            <input value={password} onChange={(e) => setPassword(e.target.value)} type="password" autoComplete="new-password" minLength={6} required />
          </div>
          {err && <p className="err">{err}</p>}
          <button className="btn primary full" type="submit">Join Soul Sisters</button>
        </form>
        <p className="muted" style={{ marginTop: 18 }}>
          Already a sister? <Link to="/login"><b>Sign in</b></Link>
        </p>
      </div>
    </div>
  )
}
