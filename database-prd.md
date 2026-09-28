# DATABASE PRD
# Multi-Vendor E-Commerce Platform

**Versi:** 1.0.0  
**Status:** Draft / MVP  
**Database:** PostgreSQL  
**Database Provider:** Supabase  
**Backend:** Laravel 13  
**ORM:** Eloquent ORM

---

# 1. Tujuan Dokumen

Dokumen ini mendefinisikan struktur database untuk aplikasi multi-vendor e-commerce.

Dokumen mencakup:

- entity
- table
- column
- data type
- primary key
- foreign key
- relationship
- constraint
- index
- status
- database rules
- cart
- order
- stock
- multi-vendor relationship

Dokumen ini menjadi acuan untuk:

- Laravel migrations
- Eloquent Models
- Factory
- Seeder
- Database testing

---

# 2. Database Technology

Database menggunakan:

```text
PostgreSQL
```

Database disediakan melalui:

```text
Supabase
```

Laravel menggunakan:

```text
Eloquent ORM
```

Connection:

```text
Laravel
    ↓
PostgreSQL
    ↓
Supabase
```

---

# 3. Database Principles

Database harus mengikuti prinsip:

- relational database
- normalized structure
- referential integrity
- foreign key
- appropriate indexes
- unique constraints
- database transaction
- secure access

Jangan menyimpan data yang sama berulang kali jika dapat direpresentasikan menggunakan relationship.

---

# 4. Entity Utama

MVP memiliki entity:

```text
users
vendors
stores
categories
products
product_images
addresses
carts
cart_items
orders
order_items
```

---

# 5. ERD Konseptual

```text
┌─────────────┐
│    users    │
└──────┬──────┘
       │
       │ 1:1
       ▼
┌─────────────┐
│   vendors   │
└──────┬──────┘
       │
       │ 1:1
       ▼
┌─────────────┐
│   stores    │
└─────────────┘


┌─────────────┐
│ categories  │
└──────┬──────┘
       │
       │ 1:N
       ▼
┌─────────────┐
│  products   │
└──────┬──────┘
       │
       │ 1:N
       ▼
┌────────────────┐
│ product_images │
└────────────────┘

products
    │
    │ N:1
    ▼
 vendors


users
  │
  ├───────────────┐
  │               │
  │ 1:N           │ 1:1
  ▼               ▼
addresses        carts
                   │
                   │ 1:N
                   ▼
              cart_items
                   │
                   │ N:1
                   ▼
                products


users
  │
  │ 1:N
  ▼
orders
  │
  │ 1:N
  ▼
order_items
  │
  │ N:1
  ▼
products
```

---

# 6. Users Table

Table:

```text
users
```

Laravel authentication menggunakan table `users`.

## Columns

| Column | Type | Constraint |
|---|---|---|
| id | bigint | PK |
| name | varchar | NOT NULL |
| email | varchar | UNIQUE, NOT NULL |
| password | varchar | NOT NULL |
| role | varchar | NOT NULL |
| phone | varchar | NULL |
| avatar | varchar | NULL |
| email_verified_at | timestamp | NULL |
| remember_token | varchar | NULL |
| created_at | timestamp | |
| updated_at | timestamp | |

---

# 7. User Role

Role minimal:

```text
admin
vendor
buyer
```

Role harus memiliki nilai yang valid.

Jika menggunakan enum PostgreSQL atau string dengan validation, keputusan final disesuaikan dengan implementasi Laravel.

Untuk MVP, penggunaan string dengan application validation diperbolehkan.

---

# 8. User Rules

Email harus unique.

Satu user memiliki satu role utama.

Contoh:

```text
User
name: John
email: john@example.com
role: buyer
```

Password harus disimpan menggunakan hashing Laravel.

Password plaintext tidak boleh disimpan.

---

# 9. Vendors Table

Table:

```text
vendors
```

Vendor merupakan profil bisnis yang terhubung dengan user.

## Columns

| Column | Type | Constraint |
|---|---|---|
| id | bigint | PK |
| user_id | bigint | FK users.id, UNIQUE |
| status | varchar | NOT NULL |
| created_at | timestamp | |
| updated_at | timestamp | |

Relationship:

```text
users
  1
  │
  │
  1
  ▼
vendors
```

Satu user dengan role `vendor` memiliki satu vendor profile.

---

# 10. Vendor Status

Status vendor:

```text
active
inactive
```

Vendor inactive tidak boleh menjalankan aktivitas penjualan normal.

Detail behavior akan ditentukan pada backend PRD.

---

# 11. Stores Table

Table:

```text
stores
```

Store merupakan informasi toko milik vendor.

## Columns

| Column | Type | Constraint |
|---|---|---|
| id | bigint | PK |
| vendor_id | bigint | FK vendors.id, UNIQUE |
| name | varchar | NOT NULL |
| slug | varchar | UNIQUE |
| description | text | NULL |
| logo | varchar | NULL |
| phone | varchar | NULL |
| created_at | timestamp | |
| updated_at | timestamp | |

Relationship:

```text
Vendor
   1
   │
   │
   1
   ▼
 Store
```

---

# 12. Store Rules

Setiap vendor memiliki maksimal satu store pada MVP.

Nama store wajib diisi.

Slug harus unique.

Slug digunakan untuk URL jika halaman store publik ditambahkan.

---

# 13. Categories Table

Table:

```text
categories
```

## Columns

| Column | Type | Constraint |
|---|---|---|
| id | bigint | PK |
| name | varchar | NOT NULL |
| slug | varchar | UNIQUE |
| description | text | NULL |
| status | varchar | NOT NULL |
| created_at | timestamp | |
| updated_at | timestamp | |

---

# 14. Category Status

```text
active
inactive
```

Category inactive tidak dapat digunakan untuk product baru.

Behavior product lama terhadap category inactive harus ditangani dengan aman dan tidak boleh menyebabkan data historis rusak.

---

# 15. Products Table

Table:

```text
products
```

## Columns

| Column | Type | Constraint |
|---|---|---|
| id | bigint | PK |
| vendor_id | bigint | FK vendors.id |
| category_id | bigint | FK categories.id |
| name | varchar | NOT NULL |
| slug | varchar | UNIQUE |
| description | text | NULL |
| price | numeric(15,2) | NOT NULL |
| stock | integer | NOT NULL |
| status | varchar | NOT NULL |
| created_at | timestamp | |
| updated_at | timestamp | |

---

# 16. Product Relationship

```text
Vendor
  │
  │ 1:N
  ▼
Products
  │
  │ N:1
  ▼
Category
```

Satu vendor dapat memiliki banyak product.

Satu category dapat memiliki banyak product.

Satu product memiliki satu vendor.

Satu product memiliki satu category pada MVP.

---

# 17. Product Price

Harga menggunakan:

```text
numeric(15,2)
```

Jangan menggunakan floating point untuk nilai uang.

Contoh:

```text
150000.00
```

Backend harus memastikan harga tidak negatif.

---

# 18. Product Stock

Stock menggunakan integer.

Constraint:

```text
stock >= 0
```

Stock tidak boleh bernilai negatif.

Stock harus divalidasi pada backend sebelum checkout.

---

# 19. Product Status

```text
active
inactive
```

Product inactive tidak boleh dibeli.

---

# 20. Product Images Table

Table:

```text
product_images
```

## Columns

| Column | Type | Constraint |
|---|---|---|
| id | bigint | PK |
| product_id | bigint | FK products.id |
| path | varchar | NOT NULL |
| is_primary | boolean | DEFAULT false |
| created_at | timestamp | |
| updated_at | timestamp | |

---

# 21. Product Image Rules

Satu product minimal memiliki satu gambar jika diwajibkan oleh UI.

MVP dapat mengizinkan satu atau beberapa gambar.

Salah satu gambar dapat ditandai:

```text
is_primary = true
```

Jika hanya terdapat satu gambar, gambar tersebut menjadi primary.

File fisik disimpan pada:

```text
Supabase Storage
```

Database hanya menyimpan reference/path file.

---

# 22. Addresses Table

Table:

```text
addresses
```

Digunakan untuk menyimpan alamat buyer.

## Columns

| Column | Type | Constraint |
|---|---|---|
| id | bigint | PK |
| user_id | bigint | FK users.id |
| label | varchar | NULL |
| recipient_name | varchar | NOT NULL |
| phone | varchar | NOT NULL |
| address | text | NOT NULL |
| city | varchar | NULL |
| province | varchar | NULL |
| postal_code | varchar | NULL |
| is_default | boolean | DEFAULT false |
| created_at | timestamp | |
| updated_at | timestamp | |

---

# 23. Address Rules

Satu buyer dapat memiliki beberapa address.

Satu address dimiliki satu user.

Untuk MVP, buyer dapat memiliki satu default address.

Backend harus menjaga agar tidak terdapat lebih dari satu default address untuk user yang sama jika business rule tersebut diterapkan.

---

# 24. Carts Table

Table:

```text
carts
```

Cart merupakan keranjang aktif buyer.

## Columns

| Column | Type | Constraint |
|---|---|---|
| id | bigint | PK |
| user_id | bigint | FK users.id, UNIQUE |
| created_at | timestamp | |
| updated_at | timestamp | |

Relationship:

```text
User
  1
  │
  ▼
Cart
```

Satu buyer memiliki satu cart aktif pada MVP.

---

# 25. Cart Items Table

Table:

```text
cart_items
```

## Columns

| Column | Type | Constraint |
|---|---|---|
| id | bigint | PK |
| cart_id | bigint | FK carts.id |
| product_id | bigint | FK products.id |
| quantity | integer | NOT NULL |
| created_at | timestamp | |
| updated_at | timestamp | |

---

# 26. Cart Item Rules

Quantity harus:

```text
quantity > 0
```

Satu cart tidak boleh memiliki duplicate product item.

Gunakan unique constraint:

```text
(cart_id, product_id)
```

Ketika buyer menambahkan product yang sudah ada:

```text
existing quantity
+
new quantity
=
updated quantity
```

Stock tetap harus divalidasi.

---

# 27. Orders Table

Table:

```text
orders
```

Order menyimpan informasi transaksi.

## Columns

| Column | Type | Constraint |
|---|---|---|
| id | bigint | PK |
| order_number | varchar | UNIQUE |
| user_id | bigint | FK users.id |
| address_id | bigint | FK addresses.id, NULL |
| total_amount | numeric(15,2) | NOT NULL |
| payment_method | varchar | NOT NULL |
| payment_status | varchar | NOT NULL |
| order_status | varchar | NOT NULL |
| shipping_address | text | NOT NULL |
| created_at | timestamp | |
| updated_at | timestamp | |

---

# 28. Order Number

Order harus memiliki identifier yang mudah dibaca.

Contoh:

```text
ORD-20260914-000001
```

Format final dapat ditentukan saat implementasi.

Order number harus unique.

Database tidak boleh mengandalkan format timestamp saja jika terdapat risiko collision.

---

# 29. Order Total

`total_amount` merupakan nilai total transaksi pada saat order dibuat.

Total dihitung backend.

Frontend tidak boleh menjadi sumber kebenaran total order.

---

# 30. Payment Method

MVP menggunakan:

```text
bank_transfer
cod
```

Nilai final dapat disesuaikan dengan implementasi.

---

# 31. Payment Status

Minimal:

```text
pending
paid
failed
```

Untuk COD, payment status dapat tetap `pending` sampai aturan pembayaran ditentukan.

---

# 32. Order Status

Minimal:

```text
pending
processing
shipped
completed
cancelled
```

Flow utama:

```text
pending
   ↓
processing
   ↓
shipped
   ↓
completed
```

Cancellation:

```text
pending
   ↓
cancelled
```

Aturan perubahan status wajib divalidasi backend.

---

# 33. Order Items Table

Table:

```text
order_items
```

## Columns

| Column | Type | Constraint |
|---|---|---|
| id | bigint | PK |
| order_id | bigint | FK orders.id |
| product_id | bigint | FK products.id |
| vendor_id | bigint | FK vendors.id |
| product_name | varchar | NOT NULL |
| price | numeric(15,2) | NOT NULL |
| quantity | integer | NOT NULL |
| subtotal | numeric(15,2) | NOT NULL |
| created_at | timestamp | |
| updated_at | timestamp | |

---

# 34. Mengapa Order Item Menyimpan Snapshot?

`order_items` menyimpan:

```text
product_name
price
quantity
subtotal
```

Karena product dapat berubah setelah transaksi.

Contoh:

Saat checkout:

```text
Product:
Laptop
Harga:
Rp10.000.000
```

Kemudian vendor mengubah harga:

```text
Rp12.000.000
```

Order lama tetap harus menunjukkan:

```text
Laptop
Rp10.000.000
```

bukan harga baru.

---

# 35. Mengapa vendor_id Disimpan di Order Items?

Karena platform adalah multi-vendor.

Contoh:

```text
Order #001

Order Item 1
Product A
Vendor A

Order Item 2
Product B
Vendor B
```

Dengan menyimpan `vendor_id` pada `order_items`, sistem dapat dengan mudah menentukan item yang harus ditampilkan kepada masing-masing vendor.

---

# 36. Multi-Vendor Order

Contoh buyer membeli:

```text
Product A → Vendor A → Rp100.000
Product B → Vendor B → Rp200.000
Product C → Vendor A → Rp50.000
```

Maka:

```text
Order
Total = Rp350.000

Order Items:
├── Product A
│   └── Vendor A
│
├── Product B
│   └── Vendor B
│
└── Product C
    └── Vendor A
```

MVP menggunakan satu `orders` record dengan beberapa `order_items`.

Vendor melihat hanya item yang berkaitan dengan vendor tersebut.

---

# 37. Order Address Snapshot

Order memiliki:

```text
shipping_address
```

Selain memiliki reference ke `addresses`.

Hal ini penting karena buyer dapat mengubah alamat setelah order dibuat.

Order historis tidak boleh berubah hanya karena alamat user berubah.

Contoh:

```text
Saat checkout:

Jl. ABC No. 10
Padang
```

Kemudian user mengubah profile address.

Order lama tetap menyimpan:

```text
Jl. ABC No. 10
Padang
```

---

# 38. Foreign Key Rules

Relationship harus menggunakan foreign key.

Contoh:

```text
vendors.user_id
→ users.id

stores.vendor_id
→ vendors.id

products.vendor_id
→ vendors.id

products.category_id
→ categories.id

product_images.product_id
→ products.id

addresses.user_id
→ users.id

carts.user_id
→ users.id

cart_items.cart_id
→ carts.id

cart_items.product_id
→ products.id

orders.user_id
→ users.id

orders.address_id
→ addresses.id

order_items.order_id
→ orders.id

order_items.product_id
→ products.id

order_items.vendor_id
→ vendors.id
```

---

# 39. Delete Strategy

Jangan menggunakan cascade delete secara sembarangan pada data transaksi.

Data historis order harus dipertahankan.

Contoh:

```text
Order
Order Items
```

tidak boleh hilang hanya karena product dihapus.

Untuk product yang pernah digunakan dalam transaksi, lebih aman menggunakan:

```text
status = inactive
```

daripada hard delete.

Detail final delete strategy ditentukan saat migration berdasarkan dependency.

---

# 40. Product Deletion

Vendor dapat meminta penghapusan product.

Namun jika product telah digunakan dalam order:

```text
Jangan menghapus histori transaksi.
```

Gunakan pendekatan:

```text
inactive
```

atau soft delete jika diperlukan.

---

# 41. User Deletion

Penghapusan user harus memperhatikan:

- order
- cart
- address
- vendor
- store

User yang memiliki histori transaksi tidak boleh menyebabkan histori transaksi rusak.

Strategi final dapat menggunakan:

- soft delete
- inactive status

sesuai implementasi.

---

# 42. Index

Index harus diberikan pada kolom yang sering digunakan untuk:

- foreign key
- filtering
- searching
- sorting

Minimal kandidat index:

```text
users.email
users.role

vendors.user_id
vendors.status

stores.vendor_id
stores.slug

categories.slug
categories.status

products.vendor_id
products.category_id
products.slug
products.status

cart_items.cart_id
cart_items.product_id

orders.user_id
orders.order_number
orders.order_status
orders.payment_status

order_items.order_id
order_items.product_id
order_items.vendor_id
```

Tidak perlu membuat index berlebihan.

---

# 43. Unique Constraints

Minimal:

```text
users.email
vendors.user_id
stores.vendor_id
stores.slug
categories.slug
products.slug
carts.user_id
cart_items(cart_id, product_id)
orders.order_number
```

---

# 44. Money Rules

Semua nilai uang menggunakan:

```text
numeric(15,2)
```

Jangan menggunakan:

```text
float
double
```

untuk monetary value.

Contoh:

```text
price
total_amount
subtotal
```

---

# 45. Quantity Rules

Quantity product dan cart harus:

```text
>= 0
```

Quantity order item harus:

```text
> 0
```

Stock tidak boleh negatif.

---

# 46. Checkout Transaction

Checkout harus menggunakan database transaction.

Flow:

```text
BEGIN TRANSACTION

Validate Cart
      ↓
Validate Product
      ↓
Validate Product Status
      ↓
Validate Stock
      ↓
Calculate Total
      ↓
Create Order
      ↓
Create Order Items
      ↓
Reduce Stock
      ↓
Clear Cart

COMMIT
```

Jika gagal:

```text
ROLLBACK
```

---

# 47. Stock Consistency

Stock harus diperiksa di backend.

Tidak boleh:

```text
Frontend:
stock = 5
quantity = 5

langsung create order
```

Backend harus memeriksa current database state.

Hal ini penting untuk mengurangi risiko overselling.

---

# 48. Cart Price

Cart tidak perlu menjadi sumber histori harga.

Harga cart dapat mengambil harga product terbaru ketika checkout.

Pada checkout:

```text
Product current price
        ↓
OrderItem price snapshot
```

Setelah order dibuat, harga pada order item tidak berubah.

---

# 49. Database Seed

Seeder MVP minimal menyediakan:

```text
1 Admin
1 Vendor
1 Buyer
Beberapa Categories
Beberapa Products
```

Seeder digunakan untuk development/testing.

Password dummy hanya digunakan untuk local development dan tidak boleh menggunakan credential production.

---

# 50. Factory

Factory dapat dibuat untuk entity yang membutuhkan data testing.

Minimal:

```text
UserFactory
VendorFactory
StoreFactory
CategoryFactory
ProductFactory
OrderFactory
OrderItemFactory
```

Factory dibuat jika memang diperlukan untuk testing atau development.

---

# 51. Migration Order

Migration harus memperhatikan dependency.

Urutan konseptual:

```text
users
   ↓
vendors
   ↓
stores

users
   ↓
addresses

categories

products
   ↓
product_images

users
   ↓
carts
   ↓
cart_items
      ↓
   products

users
   ↓
orders
   ↓
order_items
      ↓
products
      ↓
vendors
```

---

# 52. Eloquent Relationships

Minimal relationship:

## User

```text
User
hasOne Vendor
hasOne Cart
hasMany Addresses
hasMany Orders
```

## Vendor

```text
Vendor
belongsTo User
hasOne Store
hasMany Products
hasMany OrderItems
```

## Store

```text
Store
belongsTo Vendor
```

## Category

```text
Category
hasMany Products
```

## Product

```text
Product
belongsTo Vendor
belongsTo Category
hasMany ProductImages
hasMany CartItems
hasMany OrderItems
```

## ProductImage

```text
ProductImage
belongsTo Product
```

## Address

```text
Address
belongsTo User
hasMany Orders
```

## Cart

```text
Cart
belongsTo User
hasMany CartItems
```

## CartItem

```text
CartItem
belongsTo Cart
belongsTo Product
```

## Order

```text
Order
belongsTo User
belongsTo Address
hasMany OrderItems
```

## OrderItem

```text
OrderItem
belongsTo Order
belongsTo Product
belongsTo Vendor
```

---

# 53. Database Security

Database credentials tidak boleh disimpan dalam repository.

Gunakan:

```text
.env
```

Supabase credentials harus diperlakukan sebagai secret.

Service role key tidak boleh masuk frontend.

---

# 54. Supabase Storage

Database PostgreSQL hanya menyimpan:

```text
path
```

atau reference file.

File gambar disimpan di:

```text
Supabase Storage
```

Contoh:

```text
product-images/
    product-1/
        image.webp
```

Struktur final dapat disesuaikan saat implementasi.

---

# 55. Database Backup

Production database harus memiliki backup sesuai kemampuan Supabase plan/environment yang digunakan.

Development tidak boleh bergantung pada database production.

---

# 56. Database Testing

Testing harus memastikan:

### User

- email unique
- role valid

### Vendor

- vendor terhubung dengan user
- vendor tidak dapat duplicate user

### Product

- product memiliki vendor
- product memiliki category
- price valid
- stock valid

### Cart

- user memiliki cart
- duplicate product dalam cart dicegah

### Order

- order number unique
- total valid
- order item memiliki product
- order item memiliki vendor
- histori harga tersimpan

### Checkout

- stock divalidasi
- stock dikurangi
- cart dikosongkan
- transaction rollback jika gagal

---

# 57. Data Integrity

Database harus menjaga:

- foreign key integrity
- unique constraint
- non-null requirement
- valid status
- valid numeric values
- valid quantity

Business rule tetap harus divalidasi pada backend Laravel.

Database constraint digunakan sebagai lapisan perlindungan tambahan.

---

# 58. Scope MVP

Database MVP hanya mencakup:

```text
users
vendors
stores
categories
products
product_images
addresses
carts
cart_items
orders
order_items
```

Tidak membuat table untuk:

```text
wishlist
reviews
coupons
vouchers
notifications
chat
affiliate
payment_gateway
shipping_api
vendor_commissions
```

sampai fitur tersebut masuk scope proyek.

---

# 59. Future Extension

Database harus tetap dapat dikembangkan untuk:

```text
reviews
wishlists
payments
shipments
coupons
notifications
vendor commissions
refunds
```

Namun jangan membuat table tersebut sebelum diperlukan.

---

# 60. Definition of Done

Database PRD dianggap siap untuk implementasi jika:

- seluruh entity MVP telah ditentukan
- relationship telah ditentukan
- primary key telah ditentukan
- foreign key telah ditentukan
- unique constraint telah ditentukan
- status telah ditentukan
- monetary field telah ditentukan
- stock rules telah ditentukan
- checkout transaction telah ditentukan
- multi-vendor relationship telah ditentukan
- delete strategy telah dipertimbangkan
- index strategy telah ditentukan

---

# 61. Status Dokumen

```text
Master PRD:
Completed

Architecture PRD:
Completed

Database PRD:
Completed

Design PRD:
Pending

Frontend PRD:
Pending

Backend PRD:
Pending

AGENTS.md:
Pending

Implementation:
Not Started
```

---

# 62. Catatan Implementasi

Dokumen ini merupakan database specification tingkat aplikasi.

Implementasi migration harus:

1. mengikuti dokumen ini
2. mengikuti Laravel conventions
3. menggunakan foreign key
4. menggunakan index yang relevan
5. menggunakan transaction untuk operasi penting
6. tidak menghapus histori transaksi
7. menjaga integritas data

Jika terdapat kebutuhan perubahan database selama development, perubahan harus terlebih dahulu diperiksa terhadap:

```text
master-prd.md
architecture-prd.md
database-prd.md
backend-prd.md
```

Perubahan database yang signifikan harus didokumentasikan sebelum migration diubah.

---

# 63. Multi-Vendor Order Fulfillment Foundation

## 63.1 `order_vendor_groups`

`order_vendor_groups` menjadi aggregate fulfillment per vendor dalam satu parent order.
Satu `orders` record dapat memiliki satu atau beberapa vendor group.

| Column | Type | Constraint |
|---|---|---|
| id | bigint | PK |
| order_id | bigint | FK `orders.id`, NOT NULL |
| vendor_id | bigint | FK `vendors.id`, NOT NULL |
| status | varchar | `pending`, `processing`, `shipped`, `completed`, `cancelled` |
| subtotal | numeric(15,2) | NOT NULL, >= 0 |
| cancelled_at | timestamp | NULL |
| cancelled_by | bigint | FK `users.id`, NULL |
| cancellation_reason | text | NULL |
| created_at | timestamp | |
| updated_at | timestamp | |

`(order_id, vendor_id)` harus unique. Tabel memiliki index pada `order_id`, `vendor_id`, dan `status`.
Group memiliki relasi `orders`, `vendors`, dan `order_items`.

## 63.2 `order_items`

`order_items.order_vendor_group_id` adalah FK nullable pada tahap backfill. Setelah data historis terisi,
setiap item harus menunjuk group dengan `order_id` dan `vendor_id` yang sama dengan item tersebut.
Nullable dipertahankan selama migrasi additive agar historical rows tidak rusak.

## 63.3 Overall Order Status

`orders.order_status` tetap dipertahankan sebagai derived/cached aggregate dari seluruh
`order_vendor_groups.status`. Status parent yang valid:

```text
pending
processing
shipped
completed
cancelled
partially_cancelled
```

Aturan aggregation:

- semua group `cancelled` → `cancelled`;
- ada group `cancelled`, tetapi tidak semua group `cancelled` → `partially_cancelled`;
- jika tidak ada group `cancelled` dan ada `processing` → `processing`;
- jika tidak ada `cancelled`/`processing`, tetapi ada `pending` → `pending`;
- jika tidak ada `cancelled`/`processing`/`pending`, tetapi ada `shipped` → `shipped`;
- jika semua group `completed` → `completed`.

Contoh aggregation:

| Status group | Overall status |
|---|---|
| `pending` + `pending` | `pending` |
| `processing` + `pending` | `processing` |
| `shipped` + `processing` | `processing` |
| `completed` + `completed` | `completed` |
| `cancelled` + `pending` | `partially_cancelled` |
| `cancelled` + `processing` | `partially_cancelled` |
| `cancelled` + `shipped` | `partially_cancelled` |
| `cancelled` + `completed` | `partially_cancelled` |
| `cancelled` + `cancelled` | `cancelled` |

Status group menjadi source of truth; parent status menjadi nilai turunan/cache.

## 63.4 Payment

Payment tetap parent-order-level pada `orders.payment_method` dan `orders.payment_status`.
Status valid: `pending`, `paid`, `failed`.
Checkout menghasilkan satu payment obligation untuk seluruh checkout. COD dan bank transfer
tetap `pending` saat checkout. Payment status terpisah dari fulfillment status dan tidak berubah
otomatis hanya karena order `shipped` atau `completed`. Payment confirmation diimplementasikan
pada phase berikutnya.

## 63.5 History

`order_status_histories` menyimpan `order_id`, optional `order_vendor_group_id`, actor,
previous status, new status, optional reason, source, dan timestamps.
`payment_status_histories` menyimpan `order_id`, actor, previous status, new status,
optional reason, source, dan timestamps. API, UI, dan history-writing service belum termasuk phase ini.

## 63.6 Backward Compatibility

Existing orders harus memiliki vendor groups setelah backfill. Backfill mengambil distinct
`vendor_id` dari `order_items`, membuat satu group per order/vendor, memakai existing
`orders.order_status` sebagai initial group status, menghitung subtotal dari `SUM(order_items.subtotal)`,
dan mengisi `order_items.order_vendor_group_id`. Backfill idempotent dan tidak menghapus data.