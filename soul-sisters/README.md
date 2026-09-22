# Soul Sisters — women’s atelier app

A Myntra-style mobile boutique for the **Soul Sisters** womenswear brand. Shoppers browse categories, wishlists, and checkout; founders run catalog, inventory, barcodes, orders, notifications, and sales analysis from a brand studio.

This is a mobile-first Progressive Web App (installable on a phone). Data lives in the browser so you can demo the full loop without a backend.

## Run locally

```bash
cd soul-sisters
npm install
npm run dev
```

Open the printed URL (default `http://localhost:5173`). On a laptop it appears inside a phone frame; on a real phone it goes edge-to-edge.

## Demo accounts

| Role | Email | Password |
| --- | --- | --- |
| Shopper | `ananya@soulsisters.com` | `sisters123` |
| Founder / admin | `admin@soulsisters.com` | `sisters123` |

## What is included

**Customer**
- Welcome, login, signup, logout, profile
- Home campaign, category rooms, search, product detail (size/colour, low-stock)
- Bag, wishlist, orders
- Checkout with UPI, card, wallet, and COD (demo gateway — no real charges)
- In-app notifications

**Brand studio (admin)**
- Add / edit / delete pieces by category
- Inventory by SKU, size, and colour
- CODE128 barcode view + generate hang-tags
- Order desk with status that notifies the customer
- Sales analysis (revenue, AOV, daily take, category mix, bestsellers)
- Broadcast notifications to all sisters or studio-only

Payments are a realistic checkout UI meant to sit in front of Razorpay or Stripe later. Inventory decrements on purchase.

## Stack

Vite, React, TypeScript, React Router, Zustand (persisted), Recharts, JsBarcode.
