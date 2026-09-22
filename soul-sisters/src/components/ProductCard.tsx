import { Heart } from 'lucide-react'
import { Link } from 'react-router-dom'
import type { Product } from '../types'
import { inr, useStore } from '../store'

export function ProductCard({ p }: { p: Product }) {
  const wishlist = useStore((s) => s.wishlist)
  const toggle = useStore((s) => s.toggleWish)
  const on = wishlist.includes(p.id)
  return (
    <article className="card">
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
          <Heart size={16} fill={on ? '#8b2e4a' : 'none'} color="#8b2e4a" />
        </button>
      </Link>
      <div className="meta">
        <div className="tiny">{p.category}</div>
        <h3>{p.name}</h3>
        <div className="price">
          {inr(p.price)}
          <span className="mrp">{inr(p.mrp)}</span>
        </div>
      </div>
    </article>
  )
}
