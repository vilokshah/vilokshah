import { Heart, Search } from 'lucide-react'
import { Link } from 'react-router-dom'
import { ProductCard } from '../components/ProductCard'
import { Top } from '../components/Layout'
import { CATEGORIES } from '../data/catalog'
import { useSession, useStore } from '../store'

export function Home() {
  const products = useStore((s) => s.products)
  const user = useSession()
  const featured = products.filter((p) => p.featured)
  const fresh = products.filter((p) => p.newIn)

  return (
    <>
      <Top
        right={
          <div style={{ display: 'flex', gap: 8 }}>
            <Link className="icon-btn" to="/search"><Search size={18} /></Link>
            <Link className="icon-btn" to="/wishlist"><Heart size={18} /></Link>
          </div>
        }
      />
      <div className="pad" style={{ paddingTop: 0 }}>
        <p className="muted" style={{ margin: '0 0 8px' }}>
          Hello{user ? `, ${user.name.split(' ')[0]}` : ''} — dressing women, only.
        </p>
      </div>
      <div className="hero">
        <img src="https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=1400&q=80" alt="Campaign" />
        <div className="copy">
          <div className="tiny" style={{ color: '#f3e6d8' }}>Autumn atelier</div>
          <h1>Sisters in silk</h1>
          <p>New slips, festive velvets, and linen that lives with you.</p>
          <Link className="btn gold sm" to="/shop">Shop the edit</Link>
        </div>
      </div>
      <div className="section-title">
        <h2>Rooms</h2>
        <Link className="muted" to="/shop">See all</Link>
      </div>
      <div className="chip-row">
        {CATEGORIES.map((c) => (
          <Link key={c.id} className="chip" to={`/shop/${c.id}`}>
            {c.name}
          </Link>
        ))}
      </div>
      <div className="section-title">
        <h2>New in</h2>
      </div>
      <div className="pad">
        <div className="grid2">
          {fresh.map((p) => (
            <ProductCard key={p.id} p={p} />
          ))}
        </div>
      </div>
      <div className="section-title">
        <h2>House favourites</h2>
      </div>
      <div className="pad">
        <div className="grid2">
          {featured.map((p) => (
            <ProductCard key={p.id} p={p} />
          ))}
        </div>
      </div>
    </>
  )
}
