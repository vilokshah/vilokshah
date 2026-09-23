import { useMemo, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { Search } from 'lucide-react'
import { ProductCard } from '../components/ProductCard'
import { Top } from '../components/Layout'
import { CATEGORIES } from '../data/catalog'
import { categoryLabel, useStore } from '../store'
import type { CategoryId } from '../types'

type SortKey = 'featured' | 'newest' | 'price-asc' | 'price-desc'
type PriceBand = 'any' | 'u2' | '2to5' | '5to10' | 'over10'

const SORTS: { id: SortKey; label: string }[] = [
  { id: 'featured', label: 'Featured' },
  { id: 'newest', label: 'Newest first' },
  { id: 'price-asc', label: 'Price: low to high' },
  { id: 'price-desc', label: 'Price: high to low' },
]

const PRICE_BANDS: { id: PriceBand; label: string }[] = [
  { id: 'any', label: 'Any price' },
  { id: 'u2', label: 'Under ₹2,000' },
  { id: '2to5', label: '₹2,000 – ₹5,000' },
  { id: '5to10', label: '₹5,000 – ₹10,000' },
  { id: 'over10', label: 'Over ₹10,000' },
]

function inBand(price: number, band: PriceBand) {
  if (band === 'u2') return price < 2000
  if (band === '2to5') return price >= 2000 && price <= 5000
  if (band === '5to10') return price > 5000 && price <= 10000
  if (band === 'over10') return price > 10000
  return true
}

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
  const [sort, setSort] = useState<SortKey>('featured')
  const [band, setBand] = useState<PriceBand>('any')
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
    xs = xs.filter((p) => inBand(p.price, band))
    const sorted = [...xs]
    if (sort === 'newest') sorted.sort((a, b) => b.createdAt.localeCompare(a.createdAt))
    if (sort === 'price-asc') sorted.sort((a, b) => a.price - b.price)
    if (sort === 'price-desc') sorted.sort((a, b) => b.price - a.price)
    if (sort === 'featured') sorted.sort((a, b) => Number(b.featured) - Number(a.featured))
    return sorted
  }, [products, cat, sort, q, band])

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
      <div className="filter-block">
        <p className="filter-label">Sort by</p>
        <div className="chip-row flush">
          {SORTS.map((s) => (
            <button key={s.id} type="button" className={`chip ${sort === s.id ? 'on' : ''}`} onClick={() => setSort(s.id)}>
              {s.label}
            </button>
          ))}
        </div>
        <p className="filter-label">Price range</p>
        <div className="chip-row flush">
          {PRICE_BANDS.map((p) => (
            <button key={p.id} type="button" className={`chip ${band === p.id ? 'on' : ''}`} onClick={() => setBand(p.id)}>
              {p.label}
            </button>
          ))}
        </div>
      </div>
      <div className="pad">
        <p className="muted">{list.length} pieces</p>
        {list.length === 0 ? (
          <div className="empty">
            <h2 className="serif">No pieces in this range</h2>
            <p>Try another price band or clear the filters.</p>
            <button className="btn ghost sm" type="button" onClick={() => setBand('any')}>
              Show any price
            </button>
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
