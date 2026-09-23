import { Link } from 'react-router-dom'

export function BrandLogo({ compact = false }: { compact?: boolean }) {
  return (
    <Link to="/home" className={`brand-logo ${compact ? 'compact' : ''}`} aria-label="soulsisters home">
      <img src={`${import.meta.env.BASE_URL}logo.png`} alt="SS" />
      <span className="brand-word">
        soulsisters
        <small>SS</small>
      </span>
    </Link>
  )
}
