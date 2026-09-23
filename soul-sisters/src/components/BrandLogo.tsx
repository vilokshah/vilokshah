import { Link } from 'react-router-dom'

export function BrandLogo({ compact = false }: { compact?: boolean }) {
  return (
    <Link to="/home" className={`brand-logo ${compact ? 'compact' : ''}`} aria-label="Soul Sisters home">
      <img src={`${import.meta.env.BASE_URL}logo.png`} alt="" />
      <span className="brand-word">
        Soul Sisters
        <small>Womenswear</small>
      </span>
    </Link>
  )
}
