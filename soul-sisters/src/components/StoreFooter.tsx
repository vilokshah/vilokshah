import { Mail, MapPin, Phone } from 'lucide-react'
import { useStore } from '../store'

export function StoreFooter() {
  const info = useStore((s) => s.storeInfo)
  return (
    <footer className="store-footer">
      <div className="store-footer-inner">
        <strong>{info.brand}</strong>
        <span><MapPin size={12} /> {info.address}</span>
        <span><Phone size={12} /> Contact: {info.phone}</span>
        <span><Mail size={12} /> {info.email}</span>
        <a href={`https://instagram.com/${info.instagram}`} target="_blank" rel="noreferrer">@{info.instagram}</a>
      </div>
    </footer>
  )
}
