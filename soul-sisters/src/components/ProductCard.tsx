import { Heart } from 'lucide-react'
import { Link } from 'react-router-dom'
import type { Product } from '../types'
import { inr, useStore } from '../store'

export function ProductCard({ p }: { p: Product }) {
  const wishlist = useStore((s) => s.wishlist)
  const toggle = useStore((s) => s.toggleWish)
  const on = wishlist.includes(p.id)
  return (
    <article className="card rise">
      <Link to={`/product/${p.id}`} className="thumb">
        <img src={p.images[0]} alt={p.name} />
        <button
          className="wish"
          type="button"
          aria-label="wishlist"
          onClick={(e) => {
            e.preventDefault()
            toggle(p.id)
          }}
        >
          <Heart size={16} fill={on ? 'currentColor' : 'none'} />
        </button>
        <span className="thumb-name">{p.name}</span>
      </Link>
      <div className="meta">
        <h3>{p.name}</h3>
        <p className="muted" style={{ margin: '4px 0 8px' }}>{p.subtitle}</p>
        <div className="price">
          {inr(p.price)}
          <span className="mrp">{inr(p.mrp)}</span>
        </div>
      </div>
    </article>
  )
}
