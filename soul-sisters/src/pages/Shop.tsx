import { useMemo, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { ProductCard } from '../components/ProductCard'
import { Top } from '../components/Layout'
import { CATEGORIES } from '../data/catalog'
import { categoryLabel, useStore } from '../store'
import type { CategoryId } from '../types'

export function Shop() {
  const { category } = useParams()
  const products = useStore((s) => s.products)
  const [sort, setSort] = useState<'featured' | 'price' | 'new'>('featured')
  const cat = (category as CategoryId | undefined) || 'all'
  const list = useMemo(() => {
    let xs = cat === 'all' ? products : products.filter((p) => p.category === cat)
    if (sort === 'price') xs = [...xs].sort((a, b) => a.price - b.price)
    if (sort === 'new') xs = [...xs].sort((a, b) => b.createdAt.localeCompare(a.createdAt))
    return xs
  }, [products, cat, sort])

  return (
    <>
      <Top title={cat === 'all' ? 'The shop' : categoryLabel(cat as CategoryId)} back={!!category} />
      <div className="chip-row">
        <Link className={`chip ${cat === 'all' ? 'on' : ''}`} to="/shop">
          All
        </Link>
        {CATEGORIES.map((c) => (
          <Link key={c.id} className={`chip ${cat === c.id ? 'on' : ''}`} to={`/shop/${c.id}`}>
            {c.name}
          </Link>
        ))}
      </div>
      <div className="chip-row">
        {(['featured', 'new', 'price'] as const).map((s) => (
          <button key={s} className={`chip ${sort === s ? 'on' : ''}`} onClick={() => setSort(s)}>
            {s === 'price' ? 'Price' : s === 'new' ? 'Newest' : 'Featured'}
          </button>
        ))}
      </div>
      <div className="pad">
        <p className="muted">{list.length} pieces for women</p>
        <div className="grid2">
          {list.map((p) => (
            <ProductCard key={p.id} p={p} />
          ))}
        </div>
      </div>
    </>
  )
}

export function SearchPage() {
  const products = useStore((s) => s.products)
  const [q, setQ] = useState('')
  const list = products.filter((p) =>
    `${p.name} ${p.subtitle} ${p.category} ${p.fabric}`.toLowerCase().includes(q.toLowerCase()),
  )
  return (
    <>
      <Top title="Search" back />
      <div className="search-bar">
        <input autoFocus placeholder="Dresses, silk, co-ords…" value={q} onChange={(e) => setQ(e.target.value)} />
      </div>
      <div className="pad">
        <div className="grid2">
          {(q ? list : products.slice(0, 6)).map((p) => (
            <ProductCard key={p.id} p={p} />
          ))}
        </div>
      </div>
    </>
  )
}

export function Wishlist() {
  const products = useStore((s) => s.products)
  const ids = useStore((s) => s.wishlist)
  const list = products.filter((p) => ids.includes(p.id))
  return (
    <>
      <Top title="Wishlist" back />
      <div className="pad">
        {list.length === 0 ? (
          <div className="empty">
            <h2 className="serif">Nothing saved yet</h2>
            <p>Tap the heart on a piece you love.</p>
          </div>
        ) : (
          <div className="grid2">
            {list.map((p) => (
              <ProductCard key={p.id} p={p} />
            ))}
          </div>
        )}
      </div>
    </>
  )
}
