---
paths:
  - app/Services/ProductImageStorage.php
  - 'app/Services/*Checkout*.php'
  - 'app/Services/*Inventory*.php'
---

# Services

## Product photos must survive serverless deployments
Vercel local storage is ephemeral. Store newly uploaded product photos in product_image_contents and serve them through the product image route; keep legacy public-disk URLs readable for older image rows. Limit uploads with image validation and preserve the uploaded MIME type.

## Shipping is included in displayed prices
Do not add a separate shipping fee at checkout. Product and package prices are final customer prices including delivery, entered manually by admins; new orders record shipping_fee=0. Preserve actual fees on historical order records.

## Stock is internal, optional, and does not limit orders
Product stock_quantity may be null (untracked). Storefront availability and checkout must never depend on its count; active products and complete active packages remain orderable at zero stock. For tracked products, subtract ordered quantities (negative represents procurement demand) and restore on cancellation.
