import { Link } from 'react-router-dom'

export function Welcome() {
  return (
    <div className="welcome">
      <div className="veil">
        <div className="tiny" style={{ color: '#f3e6d8' }}>Women only · Est. 2024</div>
        <h1>Soul<br />Sisters</h1>
        <p className="muted" style={{ color: 'rgba(255,255,255,.82)', maxWidth: 320 }}>
          An atelier for women who dress with intention — silk slips, festive lehengas, linen sets, and the jewellery that finishes them.
        </p>
        <Link className="btn gold full" to="/login" style={{ marginTop: 8 }}>
          Enter the maison
        </Link>
        <Link className="btn ghost full" to="/signup" style={{ marginTop: 10, color: 'white', borderColor: 'rgba(255,255,255,.35)' }}>
          Create an account
        </Link>
      </div>
    </div>
  )
}
