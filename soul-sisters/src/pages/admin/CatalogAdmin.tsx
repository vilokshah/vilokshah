import { useMemo, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { Top } from '../../components/Layout'
import { BarcodeMark } from '../../components/BarcodeMark'
import { buildVariants, categoryLabel, inr, useStore } from '../../store'
import type { CategoryId, Product } from '../../types'
import { CATEGORIES } from '../../data/catalog'

const cats = CATEGORIES.map((c) => c.id)

export function AdminProducts() {
  const products = useStore((s) => s.products)
  const del = useStore((s) => s.deleteProduct)
  return (
    <div className="app-scroll">
      <Top title="Catalog" back />
      <div className="pad">
        <Link className="btn primary full" to="/admin/products/new">Add a new piece</Link>
        {products.map((p) => (
          <div className="list-item" key={p.id}>
            <img className="thumb-sm" src={p.images[0]} alt="" />
            <div style={{ flex: 1 }}>
              <b>{p.name}</b>
              <div className="muted">{categoryLabel(p.category)} · {inr(p.price)}</div>
              <div className="row" style={{ marginTop: 6 }}>
                <Link className="btn sm ghost" to={`/admin/products/${p.id}`}>Edit</Link>
                <button className="btn sm ghost" type="button" onClick={() => del(p.id)}>Delete</button>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  )
}

export function AdminProductForm() {
  const { id } = useParams()
  const nav = useNavigate()
  const existing = useStore((s) => s.products.find((p) => p.id === id))
  const upsert = useStore((s) => s.upsertProduct)
  const isNew = id === 'new' || !existing
  const [name, setName] = useState(existing?.name || '')
  const [subtitle, setSubtitle] = useState(existing?.subtitle || '')
  const [description, setDescription] = useState(existing?.description || '')
  const [category, setCategory] = useState<CategoryId>(existing?.category || 'dresses')
  const [price, setPrice] = useState(String(existing?.price || 2990))
  const [mrp, setMrp] = useState(String(existing?.mrp || 3990))
  const [fabric, setFabric] = useState(existing?.fabric || '')
  const [care, setCare] = useState(existing?.care || 'Gentle wash')
  const [images, setImages] = useState(existing?.images.join('\n') || 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=1200&q=80')
  const [colors, setColors] = useState(existing?.colors.join(', ') || 'Blush')
  const [sizes, setSizes] = useState(existing?.sizes.join(', ') || 'XS, S, M, L, XL')
  const [stock, setStock] = useState('8')
  const [featured, setFeatured] = useState(existing?.featured || false)

  return (
    <div className="app-scroll">
      <Top title={isNew ? 'New piece' : 'Edit piece'} back />
      <div className="pad">
        <div className="field"><label>Name</label><input value={name} onChange={(e) => setName(e.target.value)} /></div>
        <div className="field"><label>Subtitle</label><input value={subtitle} onChange={(e) => setSubtitle(e.target.value)} /></div>
        <div className="field"><label>Story</label><textarea rows={4} value={description} onChange={(e) => setDescription(e.target.value)} /></div>
        <div className="field">
          <label>Category</label>
          <select value={category} onChange={(e) => setCategory(e.target.value as CategoryId)}>
            {cats.map((c) => (
              <option key={c} value={c}>{categoryLabel(c)}</option>
            ))}
          </select>
        </div>
        <div className="row">
          <div className="field" style={{ flex: 1 }}><label>Price ₹</label><input value={price} onChange={(e) => setPrice(e.target.value)} /></div>
          <div className="field" style={{ flex: 1 }}><label>MRP ₹</label><input value={mrp} onChange={(e) => setMrp(e.target.value)} /></div>
        </div>
        <div className="field"><label>Fabric</label><input value={fabric} onChange={(e) => setFabric(e.target.value)} /></div>
        <div className="field"><label>Care</label><input value={care} onChange={(e) => setCare(e.target.value)} /></div>
        <div className="field"><label>Image URLs (one per line)</label><textarea rows={3} value={images} onChange={(e) => setImages(e.target.value)} /></div>
        <div className="field"><label>Colours (comma)</label><input value={colors} onChange={(e) => setColors(e.target.value)} /></div>
        <div className="field"><label>Sizes (comma)</label><input value={sizes} onChange={(e) => setSizes(e.target.value)} /></div>
        {isNew && (
          <div className="field"><label>Opening stock per SKU</label><input value={stock} onChange={(e) => setStock(e.target.value)} /></div>
        )}
        <label className="muted" style={{ display: 'flex', gap: 8, marginBottom: 12 }}>
          <input type="checkbox" checked={featured} onChange={(e) => setFeatured(e.target.checked)} /> Featured on home
        </label>
        <button
          className="btn primary full"
          type="button"
          onClick={() => {
            const colorList = colors.split(',').map((s) => s.trim()).filter(Boolean)
            const sizeList = sizes.split(',').map((s) => s.trim()).filter(Boolean)
            const imgs = images.split('\n').map((s) => s.trim()).filter(Boolean)
            const prefix = `SS${category.slice(0, 3).toUpperCase()}${String(Date.now()).slice(-4)}`
            const product: Product = {
              id: existing?.id || `p-${Date.now()}`,
              name,
              subtitle,
              description,
              category,
              price: Number(price) || 0,
              mrp: Number(mrp) || 0,
              fabric,
              care,
              images: imgs,
              colors: colorList,
              sizes: sizeList,
              variants: existing?.variants?.length
                ? existing.variants
                : buildVariants(prefix, sizeList, colorList, Number(stock) || 0),
              rating: existing?.rating || 5,
              reviews: existing?.reviews || 0,
              featured,
              newIn: isNew,
              createdAt: existing?.createdAt || new Date().toISOString().slice(0, 10),
            }
            upsert(product)
            nav('/admin/products')
          }}
        >
          Save to atelier
        </button>
      </div>
    </div>
  )
}

export function AdminInventory() {
  const products = useStore((s) => s.products)
  const setStock = useStore((s) => s.setStock)
  const [q, setQ] = useState('')
  const rows = useMemo(
    () =>
      products.flatMap((p) =>
        p.variants.map((v) => ({ ...v, name: p.name, category: p.category, id: p.id })),
      ).filter((r) => `${r.name} ${r.sku} ${r.color}`.toLowerCase().includes(q.toLowerCase())),
    [products, q],
  )
  return (
    <div className="app-scroll">
      <Top title="Inventory" back />
      <div className="search-bar">
        <input placeholder="Search SKU or piece" value={q} onChange={(e) => setQ(e.target.value)} />
      </div>
      <div className="pad">
        <table className="table">
          <thead>
            <tr><th>Piece</th><th>SKU</th><th>Stock</th></tr>
          </thead>
          <tbody>
            {rows.slice(0, 80).map((r) => (
              <tr key={r.sku}>
                <td>
                  {r.name}
                  <div className="muted">{r.color} · {r.size}</div>
                </td>
                <td>{r.sku}</td>
                <td>
                  <input
                    className={`stock-input ${r.stock < 4 ? 'low' : ''}`}
                    type="number"
                    value={r.stock}
                    onChange={(e) => setStock(r.sku, Number(e.target.value))}
                  />
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}

export function AdminBarcodes() {
  const products = useStore((s) => s.products)
  const [sku, setSku] = useState(products[0]?.variants[0]?.sku || '')
  const found = products.flatMap((p) => p.variants.map((v) => ({ ...v, name: p.name }))).find((v) => v.sku === sku)
  const [custom, setCustom] = useState('')
  const [made, setMade] = useState('')
  return (
    <div className="app-scroll">
      <Top title="Barcodes" back />
      <div className="pad">
        <p className="muted">Every SKU already has a CODE128 barcode. Generate extras for new lots or hang-tags.</p>
        <div className="field">
          <label>Existing SKU</label>
          <select value={sku} onChange={(e) => setSku(e.target.value)}>
            {products.flatMap((p) =>
              p.variants.map((v) => (
                <option key={v.sku} value={v.sku}>{p.name} · {v.color} · {v.size}</option>
              )),
            )}
          </select>
        </div>
        {found && <BarcodeMark value={found.barcode} label={`${found.name} · ${found.sku}`} />}
        <h3 className="serif">Generate a new code</h3>
        <div className="field">
          <label>Lot / SKU text</label>
          <input value={custom} onChange={(e) => setCustom(e.target.value)} placeholder="SS-DRS-LOT42" />
        </div>
        <button
          className="btn gold full"
          type="button"
          onClick={() => {
            const raw = custom.trim() || `SS${Date.now()}`
            setMade(raw.replace(/\s/g, '').toUpperCase())
          }}
        >
          Generate barcode
        </button>
        {made && (
          <div style={{ marginTop: 12 }}>
            <BarcodeMark value={made} label="New hang-tag" />
          </div>
        )}
      </div>
    </div>
  )
}
