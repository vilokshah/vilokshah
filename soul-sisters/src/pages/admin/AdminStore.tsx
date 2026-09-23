import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Top } from '../../components/Layout'
import { useStore } from '../../store'

export function AdminStore() {
  const info = useStore((s) => s.storeInfo)
  const update = useStore((s) => s.updateStoreInfo)
  const nav = useNavigate()
  const [ok, setOk] = useState(false)
  return (
    <div className="app-scroll">
      <Top title="Store details" back />
      <div className="pad">
        <p className="muted">Shown in the footer. Edit anytime.</p>
        <div className="field"><label>Brand name</label><input value={info.brand} onChange={(e) => update({ brand: e.target.value })} /></div>
        <div className="field"><label>Phone</label><input value={info.phone} onChange={(e) => update({ phone: e.target.value })} /></div>
        <div className="field"><label>Email</label><input value={info.email} onChange={(e) => update({ email: e.target.value })} /></div>
        <div className="field"><label>Address</label><textarea rows={2} value={info.address} onChange={(e) => update({ address: e.target.value })} /></div>
        <div className="field"><label>Instagram handle</label><input value={info.instagram} onChange={(e) => update({ instagram: e.target.value.replace('@', '') })} /></div>
        <div className="field"><label>Hours</label><input value={info.hours} onChange={(e) => update({ hours: e.target.value })} /></div>
        <button className="btn primary full" type="button" onClick={() => setOk(true)}>Save details</button>
        {ok && <p className="muted">Footer updated.</p>}
        <button className="btn ghost full" style={{ marginTop: 8 }} type="button" onClick={() => nav('/admin')}>Back</button>
      </div>
    </div>
  )
}
