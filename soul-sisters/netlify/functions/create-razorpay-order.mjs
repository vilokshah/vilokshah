export async function handler(event) {
  if (event.httpMethod === 'OPTIONS') {
    return { statusCode: 204, headers: cors(), body: '' }
  }
  if (event.httpMethod !== 'POST') {
    return { statusCode: 405, headers: cors(), body: JSON.stringify({ error: 'Method not allowed' }) }
  }
  const key = process.env.RAZORPAY_KEY_ID
  const secret = process.env.RAZORPAY_KEY_SECRET
  if (!key || !secret) {
    return {
      statusCode: 501,
      headers: cors(),
      body: JSON.stringify({ error: 'Razorpay secret is not set on the server. Add RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET in Netlify environment variables.' }),
    }
  }
  let amount = 0
  let receipt = 'ss'
  try {
    const body = JSON.parse(event.body || '{}')
    amount = Number(body.amount)
    receipt = String(body.receipt || 'ss').slice(0, 40)
  } catch {
    return { statusCode: 400, headers: cors(), body: JSON.stringify({ error: 'Bad JSON' }) }
  }
  if (!amount || amount < 100) {
    return { statusCode: 400, headers: cors(), body: JSON.stringify({ error: 'Invalid amount' }) }
  }
  const auth = Buffer.from(`${key}:${secret}`).toString('base64')
  const rz = await fetch('https://api.razorpay.com/v1/orders', {
    method: 'POST',
    headers: { Authorization: `Basic ${auth}`, 'Content-Type': 'application/json' },
    body: JSON.stringify({ amount, currency: 'INR', receipt, payment_capture: 1 }),
  })
  const data = await rz.json()
  return { statusCode: rz.ok ? 200 : rz.status, headers: cors(), body: JSON.stringify(data) }
}

function cors() {
  return {
    'Access-Control-Allow-Origin': '*',
    'Access-Control-Allow-Headers': 'Content-Type',
    'Content-Type': 'application/json',
  }
}
