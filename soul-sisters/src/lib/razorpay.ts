export function loadRazorpay(): Promise<new (opts: RazorpayOptions) => RazorpayInstance> {
  const w = window as unknown as { Razorpay?: new (opts: RazorpayOptions) => RazorpayInstance }
  if (w.Razorpay) return Promise.resolve(w.Razorpay)
  return new Promise((resolve, reject) => {
    const s = document.createElement('script')
    s.src = 'https://checkout.razorpay.com/v1/checkout.js'
    s.async = true
    s.onload = () => {
      const rz = (window as unknown as { Razorpay?: new (opts: RazorpayOptions) => RazorpayInstance }).Razorpay
      if (!rz) reject(new Error('Razorpay failed to load'))
      else resolve(rz)
    }
    s.onerror = () => reject(new Error('Could not load Razorpay'))
    document.body.appendChild(s)
  })
}

export interface RazorpayOptions {
  key: string
  amount: number
  currency: string
  name: string
  description?: string
  image?: string
  order_id?: string
  prefill?: { name?: string; email?: string; contact?: string }
  theme?: { color?: string }
  handler: (res: { razorpay_payment_id: string; razorpay_order_id?: string; razorpay_signature?: string }) => void
  modal?: { ondismiss?: () => void }
}

export interface RazorpayInstance {
  open: () => void
}

export async function createRazorpayOrder(amountPaise: number, receipt: string) {
  try {
    const res = await fetch('/.netlify/functions/create-razorpay-order', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ amount: amountPaise, receipt }),
    })
    if (!res.ok) return null
    const data = (await res.json()) as { id?: string }
    return data.id || null
  } catch {
    return null
  }
}
