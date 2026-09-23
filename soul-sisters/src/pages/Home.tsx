import { useState } from 'react'
import { Heart } from 'lucide-react'
import { Link, useNavigate } from 'react-router-dom'
import { ProductCard } from '../components/ProductCard'
import { Top } from '../components/Layout'
import { CATEGORIES } from '../data/catalog'
import { useSession, useStore } from '../store'
import { SearchField } from './Shop'

export function Home() {
  const products = useStore((s) => s.products)
  const user = useSession()
  const featured = products.filter((p) => p.featured)
  const fresh = products.filter((p) => p.newIn)
  const [q, setQ] = useState('')
  const nav = useNavigate()
  const hits = q.trim()
    ? products.filter((p) =>
        `${p.name} ${p.subtitle} ${p.fabric} ${p.category}`.toLowerCase().includes(q.trim().toLowerCase()),
      )
    : []

  return (
    <>
      <Top
        right={
          <Link className="icon-btn" to="/wishlist" aria-label="Wishlist">
            <Heart size={18} />
          </Link>
        }
      />
      <SearchField value={q} onChange={setQ} placeholder="Search the collection…" />
      {q.trim() ? (
        <div className="pad">
          <p className="muted">{hits.length} match{hits.length === 1 ? '' : 'es'}</p>
          <div className="grid2">
            {hits.slice(0, 6).map((p) => (
              <ProductCard key={p.id} p={p} />
            ))}
          </div>
          <button className="btn ghost full" type="button" style={{ marginTop: 12 }} onClick={() => nav('/search')}>
            Open full search
          </button>
        </div>
      ) : (
        <>
          <div className="pad" style={{ paddingTop: 0 }}>
            <p className="muted" style={{ margin: '0 0 8px' }}>
              Hello{user ? `, ${user.name.split(' ')[0]}` : ''}
            </p>
          </div>
          <div className="hero">
            <img src={`${import.meta.env.BASE_URL}banner.jpg`} alt="soulsisters collection" />
            <div className="copy">
              <div className="tiny" style={{ color: '#f3e6d8' }}>soulsisters</div>
              <h1>Designing &amp; Creating Trends</h1>
              <p>Specializing in Digital &amp; Hand Printing · Western · Ethnic · Indo Western</p>
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
      )}
    </>
  )
}
