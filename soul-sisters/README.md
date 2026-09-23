# Soul Sisters — womenswear shop

A boutique site for the **Soul Sisters** brand: shop, inventory, barcodes, Razorpay checkout, Excel catalog upload, and founder analytics.

## Run locally

```bash
cd soul-sisters
npm install
npm run dev
```

Open `http://localhost:5173`.

## Demo accounts

| Role | Email | Password |
| --- | --- | --- |
| Shopper | `ananya@soulsisters.com` | `sisters123` |
| Founder / admin | `admin@soulsisters.com` | `sisters123` |

## Razorpay (real payments)

Checkout **does not place an order until Razorpay confirms payment**. Cash on delivery is disabled.

1. Create an account at [dashboard.razorpay.com](https://dashboard.razorpay.com/signup).
2. Copy **Key ID** (`rzp_test_…` for testing, `rzp_live_…` after KYC).
3. Sign in as admin → Brand studio → **Payments** → paste Key ID → Save.
4. On checkout, Razorpay opens UPI / cards / net banking / wallets.

Optional (recommended for live): in Netlify add env vars `RAZORPAY_KEY_ID` and `RAZORPAY_KEY_SECRET`, and `VITE_RAZORPAY_KEY_ID`, then connect this repo so the `create-razorpay-order` function can run. Never put the secret in the website.

Test cards/UPI are listed in the Razorpay test-mode docs.

## Excel catalog

Admin → Catalog → download the template → fill rows → Upload Excel (`.xlsx` or `.csv`).

## Hosting

Build with `npm run build` and publish `dist`, or connect Git to Netlify using `netlify.toml`.
