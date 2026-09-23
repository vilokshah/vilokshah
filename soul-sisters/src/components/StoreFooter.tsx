import { Mail, MapPin, Phone } from 'lucide-react'
import { useStore } from '../store'

export function StoreFooter() {
  const info = useStore((s) => s.storeInfo)
  return (
    <footer className="store-footer">
      <div className="store-footer-inner">
        <div className="sf-brand">
          <img src={`${import.meta.env.BASE_URL}logo.png`} alt="" />
          <div>
            <strong>{info.brand}</strong>
            <p>@{info.instagram}</p>
          </div>
        </div>
        <div className="sf-rows">
          <p><MapPin size={14} /> {info.address}</p>
          <p><Phone size={14} /> {info.phone}</p>
          <p><Mail size={14} /> {info.email}</p>
          <p>{info.hours}</p>
          <a href={`https://instagram.com/${info.instagram}`} target="_blank" rel="noreferrer">
            instagram.com/{info.instagram}
          </a>
        </div>
      </div>
    </footer>
  )
}
