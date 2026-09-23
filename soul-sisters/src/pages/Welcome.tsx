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
        <h1>soulsisters</h1>
        <p className="welcome-tag">Designing &amp; Creating Trends</p>
        <p className="welcome-sub">Specializing in Digital &amp; Hand Printing</p>
        <p className="welcome-cats">Western · Ethnic · Indo Western</p>
        <Link className="btn gold full" to="/login" style={{ marginTop: 14 }}>
          Shop now
        </Link>
        <Link className="btn ghost full" to="/signup" style={{ marginTop: 10, color: 'white', borderColor: 'rgba(255,255,255,.35)' }}>
          Create an account
        </Link>
      </div>
    </div>
  )
}
