import type { CategoryId, Product } from '../types'
import { buildVariants } from '../store'
import * as XLSX from 'xlsx'

const CAT: Record<string, CategoryId> = {
  dresses: 'dresses',
  dress: 'dresses',
  ethnic: 'ethnic',
  'ethnic wear': 'ethnic',
  coords: 'coords',
  'co-ords': 'coords',
  coord: 'coords',
  tops: 'tops',
  top: 'tops',
  bottoms: 'bottoms',
  bottom: 'bottoms',
  active: 'active',
  activewear: 'active',
  lounge: 'lounge',
  accessories: 'accessories',
  accessory: 'accessories',
}

function cell(row: Record<string, unknown>, keys: string[]) {
  for (const k of keys) {
    const hit = Object.keys(row).find((x) => x.toLowerCase().trim() === k)
    if (hit != null && row[hit] != null && String(row[hit]).trim() !== '') return String(row[hit]).trim()
  }
  return ''
}

function splitList(s: string, extra = /[,|/]/) {
  return s.split(extra).map((x) => x.trim()).filter(Boolean)
}

export const EXCEL_HEADERS = [
  'name',
  'subtitle',
  'description',
  'category',
  'price',
  'mrp',
  'fabric',
  'care',
  'images',
  'colors',
  'sizes',
  'stock',
  'featured',
]

export function downloadProductTemplate() {
  const sample = [
    EXCEL_HEADERS,
    [
      'Rose Garden Kurta',
      'Everyday cotton kurta',
      'Handloom cotton kurta with side slits.',
      'ethnic',
      '2790',
      '3490',
      'Cotton',
      'Gentle wash',
      'https://images.unsplash.com/photo-1583391733956-6c78276477e2?w=1200',
      'Blush, Ivory',
      'XS, S, M, L, XL',
      '10',
      'yes',
    ],
  ]
  const ws = XLSX.utils.aoa_to_sheet(sample)
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Products')
  XLSX.writeFile(wb, 'soul-sisters-products-template.xlsx')
}

export function parseProductWorkbook(data: ArrayBuffer): { products: Product[]; errors: string[] } {
  const wb = XLSX.read(data, { type: 'array' })
  const sheet = wb.Sheets[wb.SheetNames[0]]
  const rows = XLSX.utils.sheet_to_json<Record<string, unknown>>(sheet, { defval: '' })
  const errors: string[] = []
  const products: Product[] = []

  rows.forEach((row, i) => {
    const line = i + 2
    const name = cell(row, ['name', 'product', 'title'])
    if (!name) {
      errors.push(`Row ${line}: missing name`)
      return
    }
    const catRaw = cell(row, ['category']).toLowerCase()
    const category: CategoryId = CAT[catRaw] ?? 'dresses'
    if (catRaw && !CAT[catRaw]) {
      errors.push(`Row ${line}: unknown category "${catRaw}", used Dresses`)
    }
    const price = Number(cell(row, ['price', 'selling price', 'sp'])) || 0
    const mrp = Number(cell(row, ['mrp', 'rrp'])) || price
    const colors = splitList(cell(row, ['colors', 'colour', 'color']) || 'Blush')
    const sizes = splitList(cell(row, ['sizes', 'size']) || 'S, M, L')
    const images = splitList(cell(row, ['images', 'image', 'image url', 'image_url']), /[,|\n]/)
    const stock = Number(cell(row, ['stock', 'qty', 'quantity'])) || 0
    const featured = /^(yes|true|1|y)$/i.test(cell(row, ['featured']))
    const prefix = `SS${category.slice(0, 3).toUpperCase()}${String(Date.now() + i).slice(-5)}`
    products.push({
      id: `p-xls-${Date.now()}-${i}`,
      name,
      subtitle: cell(row, ['subtitle', 'tagline']) || category,
      description: cell(row, ['description', 'story']) || name,
      category,
      price,
      mrp,
      fabric: cell(row, ['fabric']) || '',
      care: cell(row, ['care']) || 'Follow the care label',
      images: images.length ? images : ['https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=1200&q=80'],
      colors,
      sizes,
      variants: buildVariants(prefix, sizes, colors, stock),
      rating: 5,
      reviews: 0,
      featured,
      newIn: true,
      createdAt: new Date().toISOString().slice(0, 10),
    })
  })

  return { products, errors }
}
