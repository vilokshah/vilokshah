import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { BrandLogo } from '../components/BrandLogo'
import { ThemeToggle } from '../components/Layout'
import { useStore } from '../store'

export function Login() {
  const login = useStore((s) => s.login)
  const nav = useNavigate()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [err, setErr] = useState<string | null>(null)

  return (
    <div className="app-scroll">
      <div className="pad" style={{ paddingTop: 28 }}>
        <div className="row">
          <BrandLogo />
          <ThemeToggle />
        </div>
        <div className="auth-hero">
          <img
            src={`${import.meta.env.BASE_URL}banner.jpg`}
            alt="soulsisters collection"
          />
          <div className="auth-hero-copy">
            <p className="lead">Designing &amp; Creating Trends</p>
            <p className="sub">Specializing in Digital &amp; Hand Printing</p>
            <p className="cats">Western · Ethnic · Indo Western</p>
          </div>
        </div>
        <div className="tiny" style={{ marginTop: 18 }}>Welcome back</div>
        <h1 className="serif" style={{ fontSize: 36, margin: '6px 0 18px' }}>Sign in</h1>
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
          New here? <Link to="/signup"><b>Create an account</b></Link>
        </p>
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
        <div className="row">
          <BrandLogo />
          <ThemeToggle />
        </div>
        <div className="auth-hero">
          <img
            src={`${import.meta.env.BASE_URL}banner.jpg`}
            alt="soulsisters collection"
          />
          <div className="auth-hero-copy">
            <p className="lead">Designing &amp; Creating Trends</p>
            <p className="sub">Specializing in Digital &amp; Hand Printing</p>
            <p className="cats">Western · Ethnic · Indo Western</p>
          </div>
        </div>
        <div className="tiny" style={{ marginTop: 18 }}>soulsisters</div>
        <h1 className="serif" style={{ fontSize: 36, margin: '6px 0 18px' }}>Create profile</h1>
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
          <button className="btn primary full" type="submit">Join soulsisters</button>
        </form>
        <p className="muted" style={{ marginTop: 18 }}>
          Already have an account? <Link to="/login"><b>Sign in</b></Link>
        </p>
      </div>
    </div>
  )
}
