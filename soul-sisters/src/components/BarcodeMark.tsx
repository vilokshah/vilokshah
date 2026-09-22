import { useEffect, useRef } from 'react'
import JsBarcode from 'jsbarcode'

export function BarcodeMark({ value, label }: { value: string; label?: string }) {
  const ref = useRef<SVGSVGElement>(null)
  useEffect(() => {
    if (!ref.current || !value) return
    try {
      JsBarcode(ref.current, value, {
        format: 'CODE128',
        lineColor: '#1a0f12',
        width: 1.6,
        height: 56,
        displayValue: true,
        font: 'Outfit',
        fontSize: 12,
        margin: 4,
      })
    } catch {
      /* invalid */
    }
  }, [value])
  return (
    <div className="barcode-wrap">
      {label && <div className="tiny" style={{ marginBottom: 6 }}>{label}</div>}
      <svg ref={ref} />
    </div>
  )
}
