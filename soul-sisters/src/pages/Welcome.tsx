import { Link } from 'react-router-dom'
import { ThemeToggle } from '../components/Layout'

export function Welcome() {
  return (
    <div className="welcome">
      <div className="veil">
        <div className="row" style={{ justifyContent: 'flex-end' }}>
          <ThemeToggle />
        </div>
        <div style={{ flex: 1 }} />
        <img className="welcome-logo" src={`${import.meta.env.BASE_URL}logo.png`} alt="SS" />
        <div className="tiny" style={{ color: 'var(--gold)' }}>soulsisters</div>
        <h1>soul<br />sisters</h1>
        <p className="muted" style={{ color: 'rgba(255,255,255,.82)', maxWidth: 340 }}>
          Silk, festive sets, and everyday pieces from the soulsisters house.
        </p>
        <Link className="btn gold full" to="/login" style={{ marginTop: 8 }}>
          Shop now
        </Link>
        <Link className="btn ghost full" to="/signup" style={{ marginTop: 10, color: 'white', borderColor: 'rgba(255,255,255,.35)' }}>
          Create an account
        </Link>
      </div>
    </div>
  )
}
