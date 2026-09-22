import { useMemo, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { Heart } from 'lucide-react'
import { BarcodeMark } from '../components/BarcodeMark'
import { Top } from '../components/Layout'
import { inr, useStore } from '../store'

const colorHex: Record<string, string> = {
  Blush: '#e8b4c4',
  Ivory: '#f3ead8',
  Midnight: '#1c2740',
  Wine: '#6b1d33',
  Sage: '#8aa58a',
  Black: '#1a1a1a',
  Sable: '#4a3b32',
  Camel: '#c4a574',
  Lotus: '#d98ba3',
  Indigo: '#2c3a6b',
  Champagne: '#e6d3b3',
  Berry: '#8b2e4a',
  Cocoa: '#5c4033',
  Stone: '#c5b8aa',
  Cloud: '#ece7e1',
  Rose: '#d4a5b0',
  Gold: '#c4a574',
  Terracotta: '#c46b4a',
  Noir: '#161616',
  Emerald: '#1f5c45',
  White: '#f7f4ef',
  Sand: '#d8c3a5',
  Olive: '#6b6b3a',
}

export function ProductPage() {
  const { id } = useParams()
  const nav = useNavigate()
  const product = useStore((s) => s.products.find((p) => p.id === id))
  const add = useStore((s) => s.addToCart)
  const wish = useStore((s) => s.wishlist.includes(id || ''))
  const toggle = useStore((s) => s.toggleWish)
  const [img, setImg] = useState(0)
  const [size, setSize] = useState(product?.sizes[1] || product?.sizes[0] || '')
  const [color, setColor] = useState(product?.colors[0] || '')
  const [toast, setToast] = useState('')
  const [showCode, setShowCode] = useState(false)

  const variant = useMemo(
    () => product?.variants.find((v) => v.size === size && v.color === color),
    [product, size, color],
  )

  if (!product) {
    return (
      <div className="empty">
        <h2>Piece not found</h2>
      </div>
    )
  }

  return (
    <>
      <Top
        back
        title=" "
        right={
          <button className="icon-btn" type="button" onClick={() => toggle(product.id)}>
            <Heart size={18} fill={wish ? '#8b2e4a' : 'none'} />
          </button>
        }
      />
      <div className="pdp-hero" onClick={() => setImg((img + 1) % product.images.length)}>
        <img src={product.images[img]} alt={product.name} />
        <div className="dots">
          {product.images.map((_, i) => (
            <i key={i} className={i === img ? 'on' : ''} />
          ))}
        </div>
      </div>
      <div className="pad" style={{ paddingTop: 16 }}>
        <div className="tiny">{product.category} · {product.rating} ★ ({product.reviews})</div>
        <h1 className="serif" style={{ fontSize: 32, margin: '4px 0 6px' }}>{product.name}</h1>
        <p className="muted">{product.subtitle}</p>
        <p className="price" style={{ fontSize: 18 }}>
          {inr(product.price)} <span className="mrp">{inr(product.mrp)}</span>
        </p>
        <p>{product.description}</p>
        <p className="muted">Fabric · {product.fabric}<br />Care · {product.care}</p>
        <div className="tiny" style={{ margin: '12px 0 8px' }}>Colour</div>
        <div className="color-row">
          {product.colors.map((c) => (
            <button
              key={c}
              type="button"
              title={c}
              className={`swatch ${color === c ? 'on' : ''}`}
              style={{ background: colorHex[c] || '#ddd' }}
              onClick={() => setColor(c)}
            />
          ))}
        </div>
        <div className="tiny" style={{ margin: '14px 0 8px' }}>Size</div>
        <div className="size-grid">
          {product.sizes.map((s) => (
            <button key={s} type="button" className={`size ${size === s ? 'on' : ''}`} onClick={() => setSize(s)}>
              {s}
            </button>
          ))}
        </div>
        <p className="muted" style={{ marginTop: 10 }}>
          SKU {variant?.sku} · {variant && variant.stock > 0 ? `${variant.stock} in atelier` : 'Waitlist'}
          {variant && variant.stock < 4 && variant.stock > 0 && <span className="low"> · low stock</span>}
        </p>
        <button className="btn ghost full" type="button" onClick={() => setShowCode(true)} style={{ marginBottom: 10 }}>
          View product barcode
        </button>
        <button
          className="btn primary full"
          type="button"
          disabled={!variant || variant.stock < 1}
          onClick={() => {
            if (!variant) return
            add({ productId: product.id, sku: variant.sku, size, color, qty: 1 })
            setToast('Added to bag')
            setTimeout(() => setToast(''), 1600)
          }}
        >
          Add to bag
        </button>
        <button className="btn dark full" type="button" style={{ marginTop: 8 }} onClick={() => nav('/cart')}>
          Go to bag
        </button>
      </div>
      {showCode && variant && (
        <div className="sheet" onClick={() => setShowCode(false)}>
          <div className="panel" onClick={(e) => e.stopPropagation()}>
            <div className="tiny">In-store scan</div>
            <h2 className="serif" style={{ marginTop: 4 }}>{product.name}</h2>
            <p className="muted">{color} · {size}</p>
            <BarcodeMark value={variant.barcode} label={variant.sku} />
            <button className="btn primary full" style={{ marginTop: 14 }} onClick={() => setShowCode(false)}>
              Close
            </button>
          </div>
        </div>
      )}
      {toast && <div className="toast">{toast}</div>}
    </>
  )
}
