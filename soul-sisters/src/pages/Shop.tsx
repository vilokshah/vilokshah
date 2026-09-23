import { useMemo, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { Search } from 'lucide-react'
import { ProductCard } from '../components/ProductCard'
import { Top } from '../components/Layout'
import { CATEGORIES } from '../data/catalog'
import { categoryLabel, useStore } from '../store'
import type { CategoryId } from '../types'

export function SearchField({
  value,
  onChange,
  placeholder = 'Search dresses, silk, co-ords…',
}: {
  value: string
  onChange: (v: string) => void
  placeholder?: string
}) {
  return (
    <div className="search-bar">
      <Search size={18} color="#8b2e4a" />
      <input
        type="search"
        enterKeyHint="search"
        autoComplete="off"
        placeholder={placeholder}
        value={value}
        onChange={(e) => onChange(e.target.value)}
        aria-label="Search products"
      />
    </div>
  )
}

export function Shop() {
  const { category } = useParams()
  const products = useStore((s) => s.products)
  const [sort, setSort] = useState<'featured' | 'price' | 'new'>('featured')
  const [q, setQ] = useState('')
  const cat = (category as CategoryId | undefined) || 'all'
  const list = useMemo(() => {
    let xs = cat === 'all' ? products : products.filter((p) => p.category === cat)
    const n = q.trim().toLowerCase()
    if (n) {
      xs = xs.filter((p) =>
        `${p.name} ${p.subtitle} ${p.category} ${p.fabric} ${p.colors.join(' ')}`.toLowerCase().includes(n),
      )
    }
    if (sort === 'price') xs = [...xs].sort((a, b) => a.price - b.price)
    if (sort === 'new') xs = [...xs].sort((a, b) => b.createdAt.localeCompare(a.createdAt))
    return xs
  }, [products, cat, sort, q])

  return (
    <>
      <Top title={cat === 'all' ? 'Shop' : categoryLabel(cat as CategoryId)} back={!!category} />
      <SearchField value={q} onChange={setQ} />
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
        <p className="muted">{list.length} pieces</p>
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
    `${p.name} ${p.subtitle} ${p.category} ${p.fabric} ${p.colors.join(' ')}`.toLowerCase().includes(q.toLowerCase()),
  )
  return (
    <>
      <Top title="Search" back />
      <SearchField value={q} onChange={setQ} />
      <div className="pad">
        {!q && <p className="muted">Type a name, fabric, or colour to find a piece.</p>}
        {q && list.length === 0 && (
          <div className="empty">
            <h2 className="serif">No matches</h2>
            <p>Try “silk”, “lehenga”, or “dress”.</p>
          </div>
        )}
        <div className="grid2">
          {(q ? list : products.slice(0, 8)).map((p) => (
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
