# BACKEND PRD
# Multi-Vendor E-Commerce Platform

**Versi:** 1.0.0  
**Status:** MVP  
**Backend:** Laravel 13  
**Frontend:** React.js  
**Bridge:** Inertia.js  
**Database:** Supabase PostgreSQL  
**Storage:** Supabase Storage  
**Authentication:** Laravel Authentication  
**ORM:** Eloquent ORM

---

# 1. Tujuan Dokumen

Dokumen ini mendefinisikan aturan backend aplikasi multi-vendor e-commerce.

Backend bertanggung jawab terhadap:

- authentication
- authorization
- role management
- validation
- business logic
- database interaction
- product management
- cart
- checkout
- stock
- order
- vendor isolation
- admin management
- Supabase integration

---

# 2. Backend Principle

Laravel adalah **source of truth** aplikasi.

React hanya bertanggung jawab terhadap UI dan client interaction.

Semua operasi penting harus divalidasi oleh Laravel.

Contoh:

```text id="7f91hl"
React
  ↓
Inertia Request
  ↓
Laravel
  ↓
Validation
  ↓
Authorization
  ↓
Business Logic
  ↓
Database
```

---

# 3. Backend Architecture

Gunakan struktur:

```text id="xk8y3a"
Request
  ↓
Controller
  ↓
Form Request
  ↓
Policy / Authorization
  ↓
Service
  ↓
Model / Eloquent
  ↓
PostgreSQL
```

Tidak semua operasi wajib menggunakan Service.

Gunakan Service ketika business logic cukup kompleks atau digunakan kembali.

---

# 4. Backend Folder Structure

Target:

```text id="v5f4yc"
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   ├── Buyer/
│   │   ├── Vendor/
│   │   └── Admin/
│   │
│   ├── Requests/
│   │   ├── Auth/
│   │   ├── Product/
│   │   ├── Cart/
│   │   ├── Checkout/
│   │   ├── Order/
│   │   └── Store/
│   │
│   └── Middleware/
│
├── Models/
│
├── Policies/
│
├── Services/
│   ├── CartService.php
│   ├── CheckoutService.php
│   ├── OrderService.php
│   └── ProductService.php
│
└── Providers/
```

Folder dibuat sesuai kebutuhan.

Jangan membuat abstraction yang belum diperlukan.

---

# 5. Authentication

MVP membutuhkan:

```text id="e4y6u9"
Register
Login
Logout
```

Authentication menggunakan Laravel.

User yang login dapat diakses melalui:

```text id="kw1n8h"
auth()->user()
```

---

# 6. Registration

Public registration mendukung Buyer dan Vendor.

`RegisterRequest` harus menerima role melalui whitelist berikut:

```text id="5m2z1f"
buyer
vendor
```

Role lain harus ditolak. Admin tidak tersedia pada public registration.

Data registration:

```text id="l2v1aw"
name
email
phone
password
role
```

Password divalidasi oleh backend menggunakan aturan password Laravel dan disimpan menggunakan mekanisme hashing Laravel. Password strength indicator frontend hanya feedback visual, bukan pengganti validasi backend.

Flow registration:

```text
Public Registration
        ↓
Validate role
        ↓
buyer / vendor
```

---

# 7. Vendor Creation

Jika role yang dipilih adalah `buyer`, sistem hanya membuat User dengan role `buyer`.

Jika role yang dipilih adalah `vendor`, sistem membuat User dan Vendor dalam satu transaction:

```text
DB::transaction()
├── Create User(role=vendor)
└── Create Vendor(status=active, user_id=User.id)
        ↓
      Commit
```

Vendor menggunakan `user_id` sebagai relasi ke User. Jika proses Vendor gagal, pembuatan User juga harus rollback.

Store tidak dibuat saat registration Vendor. Store dibuat kemudian melalui fitur atau proses terpisah setelah Vendor berhasil membuat akun.

Vendor creation internal tetap dapat digunakan jika diperlukan untuk proses administratif, tetapi bukan satu-satunya cara membuat akun Vendor.

---

# 8. Admin Account

Admin tidak dibuat melalui registration publik.

Admin harus dibuat melalui:

- seeder
- database setup
- administrative process

Admin credential development tidak boleh digunakan sebagai production credential.

---

# 9. Password Security

Password harus menggunakan hashing Laravel.

Contoh:

```text id="s4h2l8"
Hash::make($password)
```

Jangan:

```text id="h5k9s2"
$password
```

disimpan langsung.

---

# 10. Authentication Middleware

Page yang membutuhkan login harus dilindungi:

```text id="8a9jcx"
auth
```

Guest tidak boleh mengakses dashboard authenticated.

---

# 11. Role Middleware

Role-based access dapat menggunakan middleware.

Contoh:

```text id="2zv8lq"
role:admin
role:vendor
role:buyer
```

Implementasi final dapat menggunakan middleware custom atau Laravel authorization mechanism.

---

# 12. Authorization

Authentication menjawab:

```text id="t1v9ra"
"Siapa user ini?"
```

Authorization menjawab:

```text id="j7m0lp"
"Apa yang boleh dilakukan user ini?"
```

Keduanya harus dipisahkan.

---

# 13. Policies

Gunakan Laravel Policies untuk resource-level authorization.

Contoh:

```text id="6m4tws"
ProductPolicy
OrderPolicy
StorePolicy
UserPolicy
VendorPolicy
```

Tidak semua resource harus memiliki policy jika tidak diperlukan.

---

# 14. Vendor Isolation

Vendor hanya dapat mengakses resource miliknya.

Contoh:

```text id="u8e6kd"
Vendor A
```

tidak boleh:

```text id="c0x3zy"
Edit Product Vendor B
View Product Vendor B
Delete Product Vendor B
View private order data Vendor B
```

Authorization harus dilakukan backend.

---

# 15. Vendor Product Query

Ketika vendor membuka product management:

```text id="f0n8st"
Product::where('vendor_id', $vendor->id)
```

atau equivalent query melalui relationship.

Jangan mengambil seluruh product lalu memfilter hanya di React.

---

# 16. Vendor Order Query

Vendor hanya boleh melihat order item yang berkaitan dengan vendor tersebut.

Contoh konsep:

```text id="h2x9pd"
OrderItem::where('vendor_id', $vendor->id)
```

Query harus dilakukan server-side.

---

# 17. Admin Authorization

Admin memiliki akses management platform sesuai scope MVP:

```text id="m5b2kw"
Users
Vendors
Products
Orders
```

Admin tetap harus menggunakan authorization.

Jangan membuat semua authenticated user otomatis memiliki admin access.

---

# 18. Buyer Authorization

Buyer hanya boleh:

- melihat product publik
- mengelola cart miliknya
- melihat order miliknya
- membuat order
- mengelola profile/address miliknya

Buyer tidak boleh mengakses:

```text id="8v3w2e"
Admin
Vendor Management
Other Buyer Orders
Other User Address
```

---

# 19. Form Requests

Gunakan Laravel Form Request untuk validasi input kompleks.

Contoh:

```text id="bq2g2k"
LoginRequest
RegisterRequest
StoreProductRequest
UpdateProductRequest
CheckoutRequest
UpdateStoreRequest
```

Nama dapat disesuaikan.

---

# 20. Validation Principle

Validasi dilakukan:

```text id="2a0qyg"
Frontend
+
Backend
```

Frontend:

```text id="j8b7sz"
React Hook Form + Zod
```

Backend:

```text id="y3gq1s"
Laravel Form Request
```

Backend selalu menjadi final validation authority.

---

# 21. Product Validation

Create/update product minimal memvalidasi:

```text id="h4k9mv"
name
category_id
price
stock
description
status
images
```

Rules harus memastikan:

- name tidak kosong
- category valid
- price >= 0
- stock >= 0
- status valid

---

# 22. Product Ownership

Saat vendor membuat product:

```text id="9c8a5r"
vendor_id
```

tidak boleh diambil dari input user secara bebas.

Backend harus mengambil vendor berdasarkan authenticated user.

Contoh konsep:

```text id="a8v6w1"
auth user
    ↓
vendor profile
    ↓
vendor_id
```

Hal ini mencegah vendor membuat product atas nama vendor lain.

---

# 23. Product CRUD

Vendor MVP:

```text id="7g6m5k"
Create
Read
Update
Change Status
```

Delete dapat dibatasi jika product memiliki histori transaksi.

---

# 24. Product Service

Jika logic product mulai kompleks, gunakan:

```text id="2k8d7a"
ProductService
```

Contoh responsibility:

- create product
- update product
- process product images
- manage product status

Controller tetap tipis.

---

# 25. Product Image Upload

Flow:

```text id="w7c2fh"
Vendor
 ↓
Select Image
 ↓
Inertia Upload
 ↓
Laravel Validation
 ↓
Supabase Storage
 ↓
Save Path
 ↓
product_images
```

---

# 26. Storage Security

Supabase Storage credential rahasia tidak boleh berada di frontend.

Service credentials hanya digunakan server-side.

---

# 27. Category Management

Admin dapat:

```text id="p8v4fz"
View Categories
Create Category
Update Category
Change Category Status
```

Category dapat digunakan product.

Category inactive tidak boleh dipakai untuk product baru.

---

# 28. Store Management

Vendor dapat mengelola:

```text id="h9n3l1"
Store Name
Description
Logo
Phone
```

Vendor hanya boleh mengubah store miliknya.

---

# 29. Cart Service

Cart logic dapat ditempatkan di:

```text id="n5t0mc"
CartService
```

Responsibilities:

- get active cart
- add item
- update quantity
- remove item
- clear cart
- validate cart

---

# 30. Add To Cart

Flow:

```text id="k8w4jv"
Buyer
 ↓
POST Add Cart
 ↓
Authenticate
 ↓
Validate Product
 ↓
Check Product Status
 ↓
Check Quantity
 ↓
Check Stock
 ↓
Create/Update Cart Item
```

---

# 31. Cart Ownership

Buyer hanya dapat mengakses cart miliknya.

Jangan menerima:

```text id="y7b2j3"
cart_id
```

sebagai satu-satunya authorization mechanism.

Backend harus memastikan cart tersebut milik authenticated user.

---

# 32. Cart Quantity

Quantity harus:

```text id="v3g8z1"
> 0
```

Quantity tidak boleh melebihi stock yang tersedia.

Namun stock final tetap harus diperiksa kembali saat checkout.

---

# 33. Checkout Service

Checkout merupakan salah satu business logic paling penting.

Gunakan:

```text id="j5p8s3"
CheckoutService
```

jika logic cukup kompleks.

---

# 34. Checkout Flow

```text id="0q5rj7"
Buyer
 ↓
Checkout
 ↓
Authenticate
 ↓
Get Cart
 ↓
Validate Address
 ↓
Validate Products
 ↓
Validate Product Status
 ↓
Validate Stock
 ↓
Calculate Subtotal
 ↓
Calculate Shipping
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
 ↓
Commit Transaction
```

---

# 35. Database Transaction

Checkout wajib menggunakan database transaction.

Konsep:

```text id="6h1kq9"
DB::transaction(function () {
    ...
});
```

Jika salah satu operasi gagal:

```text id="0f6tqp"
ROLLBACK
```

---

# 36. Checkout Price

Final price harus diambil dari database.

Frontend tidak boleh mengirim:

```text id="y1q7a2"
total_amount = 100000
```

lalu Laravel mempercayainya.

Laravel harus menghitung ulang:

```text id="3m8x4k"
price × quantity
```

untuk setiap order item.

---

# 37. Checkout Stock

Stock harus diperiksa ketika checkout.

Contoh:

```text id="8y4q5m"
Stock = 10
Requested = 3
```

Valid.

Jika:

```text id="n2d5z7"
Stock = 2
Requested = 3
```

checkout harus gagal.

---

# 38. Prevent Negative Stock

Backend tidak boleh menghasilkan:

```text id="1x4p8q"
stock = -1
```

Jika stock tidak cukup:

```text id="k0m7s2"
Throw Validation/Business Exception
```

dan rollback transaction.

---

# 39. Order Creation

Order harus dibuat setelah seluruh validasi penting berhasil.

Contoh data:

```text id="x4j7c2"
order_number
user_id
address
total_amount
payment_method
payment_status
order_status
```

---

# 40. Order Item Creation

Setiap cart item menjadi order item.

Data snapshot:

```text id="j8v4k6"
product_id
vendor_id
product_name
price
quantity
subtotal
```

---

# 41. Order Item Vendor

`vendor_id` harus berasal dari product database.

Jangan menerima:

```text id="c9s3w7"
vendor_id
```

dari buyer sebagai source of truth.

Backend mengambil:

```text id="x8n5q2"
product.vendor_id
```

---

# 42. Stock Reduction

Setelah order berhasil dibuat:

```text id="b7h3k9"
product.stock
-
order_item.quantity
```

Stock reduction harus berada dalam transaction checkout.

---

# 43. Cart Clearing

Setelah order berhasil:

```text id="q5r2m8"
cart_items
```

harus dikosongkan.

Jika checkout gagal:

```text id="s7x1p4"
cart
```

tidak boleh kehilangan item.

---

# 44. Order Number Generation

Order number dibuat oleh backend.

Contoh:

```text id="j1m9c5"
ORD-20260914-000001
```

Harus unique.

Jangan menggunakan order number dari frontend.

---

# 45. Payment MVP

Payment method:

```text id="f3w8k2"
bank_transfer
cod
```

MVP tidak menggunakan payment gateway kompleks.

---

# 46. Payment Status

```text id="z6n4b8"
pending
paid
failed
```

Payment status hanya boleh berubah melalui backend logic yang valid.

---

# 47. Order Status

```text id="c8m3v7"
pending
processing
shipped
completed
cancelled
```

Backend harus mengontrol transition.

---

# 48. Order Status Transition

Valid flow:

```text id="e2k9r5"
pending
   ↓
processing
   ↓
shipped
   ↓
completed
```

Cancellation dapat dilakukan sesuai business rule.

Tidak boleh sembarang:

```text id="q3h7w1"
completed → pending
```

tanpa business rule yang jelas.

---

# 49. Order Service

Gunakan:

```text id="n4j8p2"
OrderService
```

jika order logic sudah kompleks.

Responsibilities:

- create order
- calculate total
- update order status
- retrieve order
- process cancellation

---

# 50. Buyer Order Access

Buyer hanya boleh melihat order miliknya.

Query harus menggunakan authenticated user.

Contoh konsep:

```text id="a2v6s9"
Order::where('user_id', auth()->id())
```

---

# 51. Vendor Order Access

Vendor hanya melihat order item miliknya.

Contoh:

```text id="m5q9x3"
OrderItem::where('vendor_id', $vendor->id)
```

Data buyer yang tidak diperlukan tidak boleh diekspos secara berlebihan.

---

# 52. Admin Order Access

Admin dapat melihat order platform sesuai scope MVP.

Admin dapat melihat:

```text id="k4s8w2"
Order
Order Items
Buyer
Vendor
Product
Payment
Status
```

sesuai authorization.

---

# 53. Address Management

Buyer dapat:

```text id="z7p3c1"
Create Address
Update Address
Set Default Address
```

Buyer hanya boleh mengelola address miliknya.

---

# 54. Order Address Snapshot

Ketika checkout, backend mengambil address yang dipilih lalu menyimpan snapshot pada order.

Jangan hanya bergantung pada:

```text id="h6n2v8"
order.address_id
```

karena address dapat berubah setelah transaksi.

---

# 55. Dashboard Backend

Dashboard data harus dihitung oleh backend.

Contoh vendor:

```text id="q1w5e9"
product_count
order_count
sales_total
```

Contoh admin:

```text id="r4t8y2"
user_count
vendor_count
product_count
order_count
```

Jangan menggunakan hardcoded statistics.

---

# 56. Inertia Shared Data

Data umum dapat dibagikan melalui Inertia middleware.

Contoh:

```text id="m9c4x7"
auth.user
flash
```

Jangan mengirim data besar secara global ke semua page.

---

# 57. Flash Message

Backend dapat mengirim flash message:

```text id="s2v6k8"
success
error
warning
info
```

Contoh:

```text id="b5n1q9"
Product created successfully.
```

Frontend menampilkannya melalui toast/notification UI.

---

# 58. Error Handling

Backend harus menangani:

```text id="p3r7x1"
Validation Error
Authorization Error
Not Found
Business Logic Error
Database Error
```

User tidak boleh menerima raw stack trace pada production.

---

# 59. HTTP Response

Karena menggunakan Inertia, response harus mengikuti flow Inertia.

Contoh:

```text id="g8m2v5"
Redirect
Flash Message
Validation Errors
Inertia Props
```

Tidak perlu membuat JSON API untuk setiap operasi MVP.

---

# 60. Controllers

Controller harus tetap tipis.

Controller bertugas:

```text id="w4k9c2"
Receive Request
 ↓
Validate
 ↓
Authorize
 ↓
Call Service/Model
 ↓
Return Inertia Redirect/Response
```

Hindari memasukkan ratusan baris business logic ke Controller.

---

# 61. Service Usage

Service digunakan ketika:

- logic kompleks
- transaction
- proses multi-step
- logic dipakai lebih dari satu tempat

Jangan membuat service untuk setiap CRUD sederhana tanpa alasan.

---

# 62. Models

Eloquent Model bertanggung jawab terhadap:

- relationships
- casts
- scopes sederhana
- model configuration

Jangan memasukkan seluruh application workflow ke Model.

---

# 63. Query Scopes

Gunakan query scope jika filter sering digunakan.

Contoh:

```text id="z7q3m5"
Product::active()
Product::byVendor($vendorId)
```

Namun jangan membuat scope untuk query yang hanya digunakan sekali.

---

# 64. N+1 Prevention

Gunakan eager loading ketika diperlukan.

Contoh:

```text id="y4c8k2"
Product::with(['vendor', 'category', 'images'])
```

Hindari query berulang dalam loop.

---

# 65. Pagination

Gunakan Laravel pagination untuk:

```text id="f5n9x3"
Products
Users
Vendors
Orders
```

sesuai kebutuhan.

---

# 66. Search

Search dilakukan server-side.

Contoh:

```text id="k2m7p4"
Product name
Category
Vendor
```

Query harus menggunakan parameter yang divalidasi.

---

# 67. Database Transaction

Transaction wajib digunakan pada operasi yang memengaruhi beberapa table sekaligus.

Contoh:

```text id="d8r1w6"
Checkout
Vendor creation
Complex product update
```

---

# 68. Mass Assignment

Model harus menggunakan:

```text id="s5v2k8"
$fillable
```

atau:

```text id="n3q7x1"
$guarded
```

secara aman.

Jangan membiarkan field sensitif dapat diisi user secara bebas.

---

# 69. Protected Fields

Field seperti:

```text id="j6m9p3"
role
vendor_id
payment_status
order_status
total_amount
```

tidak boleh dipercayakan langsung dari request user.

Nilai tersebut harus ditentukan backend.

---

# 70. Role Security

User tidak boleh mengubah role dirinya sendiri melalui endpoint profile.

Contoh request berbahaya:

```text id="r2x8k4"
PATCH /profile

role=admin
```

Backend harus mengabaikan/menolak field tersebut.

---

# 71. Vendor Security

Vendor tidak boleh mengubah:

```text id="v5n1q7"
vendor_id
owner_id
user_id
```

melalui request product/store.

Ownership berasal dari authenticated context.

---

# 72. Order Security

Buyer tidak boleh mengubah:

```text id="q8m4c2"
user_id
vendor_id
total_amount
payment_status
order_status
```

secara bebas.

---

# 73. CSRF

Gunakan protection bawaan Laravel/Inertia.

Jangan membuat mekanisme CSRF manual jika tidak diperlukan.

---

# 74. Authentication Session

Gunakan Laravel session-based authentication untuk aplikasi web.

Jangan membuat JWT authentication untuk MVP jika tidak diperlukan.

---

# 75. Authorization Testing

Test minimal:

```text id="x3p7n9"
Buyer cannot access Admin
Buyer cannot access Vendor
Vendor cannot access Admin
Vendor cannot edit another Vendor product
Vendor cannot view another Vendor private order
Buyer cannot view another Buyer order
```

---

# 76. Product Testing

Test:

```text id="m8q2v5"
Vendor can create product
Vendor can update own product
Vendor cannot update another vendor product
Admin can manage product
Inactive product cannot be purchased
```

---

# 77. Cart Testing

Test:

```text id="k4r9x1"
Buyer can add product
Buyer can update quantity
Buyer can remove product
Buyer cannot access another user's cart
Quantity cannot exceed stock
```

---

# 78. Checkout Testing

Test:

```text id="w6p3c8"
Valid cart creates order
Stock decreases
Cart is cleared
Order item stores price snapshot
Multiple vendors can exist in one order
Insufficient stock fails
Failed checkout rolls back
```

---

# 79. Order Testing

Test:

```text id="s9m2v6"
Buyer sees own order
Buyer cannot see another buyer order
Vendor sees own order items
Vendor cannot see another vendor items
Admin can access platform orders
```

---

# 80. Supabase PostgreSQL

Laravel menggunakan Supabase PostgreSQL sebagai database.

Connection configuration berada di:

```text id="g4x8n2"
.env
```

Jangan hardcode credentials.

---

# 81. Supabase Storage

Product images menggunakan Supabase Storage.

Backend bertanggung jawab terhadap:

- upload
- validation
- path
- deletion jika diperlukan

---

# 82. Supabase Service Credentials

Credential privileged Supabase hanya boleh digunakan server-side.

Tidak boleh berada di:

```text id="n7q1m5"
resources/js/
```

atau dikirim kepada browser.

---

# 83. Environment Variables

Environment sensitive data disimpan dalam:

```text id="p5v9c3"
.env
```

Contoh konsep:

```text id="w2k6r8"
DB_CONNECTION=pgsql
DB_HOST=...
DB_PORT=...
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
```

Nilai aktual tidak ditulis ke repository.

---

# 84. Route Organization

Routes dapat dipisahkan secara logical:

```text id="z3x7m1"
routes/
├── web.php
├── admin.php
├── vendor.php
└── buyer.php
```

Jika Laravel configuration tidak membutuhkan file terpisah, gunakan struktur route yang tetap jelas.

---

# 85. Route Naming

Gunakan named route.

Contoh:

```text id="c8q4v6"
buyer.products.index
buyer.products.show

vendor.products.index
vendor.products.create

admin.users.index
admin.orders.index
```

Nama route harus konsisten berdasarkan role dan resource.

---

# 86. Route Protection

Contoh konsep:

```text id="f7m2x9"
auth
role
policy
```

Semua protected routes harus memiliki authorization yang sesuai.

---

# 87. Backend MVP Scope

Backend hanya mengimplementasikan:

```text id="j4p8s2"
Authentication
Authorization
Users
Vendors
Stores
Categories
Products
Product Images
Addresses
Cart
Checkout
Orders
```

---

# 88. Out of Scope

Jangan implementasikan:

```text id="v6n3q9"
Wishlist
Reviews
Chat
Coupons
Affiliate
Flash Sale
Loyalty Points
Subscription
Advanced Analytics
Real-time Notifications
Payment Gateway
Shipping API
```

kecuali diminta secara eksplisit.

---

# 89. Development Principle

Implementasi backend harus dilakukan bertahap.

Urutan:

```text id="m1x7c4"
Authentication
      ↓
Role Authorization
      ↓
Users / Vendors / Stores
      ↓
Categories
      ↓
Products
      ↓
Product Images
      ↓
Buyer Product Browsing
      ↓
Cart
      ↓
Checkout
      ↓
Orders
      ↓
Vendor Management
      ↓
Admin Management
```

---

# 90. No Big Bang Implementation

Cline tidak boleh mengimplementasikan seluruh backend sekaligus.

Setiap tahap harus:

```text id="q8v3n6"
Implement
 ↓
Test
 ↓
Fix
 ↓
Verify
 ↓
Commit
 ↓
Next Feature
```

---

# 91. Code Quality

Backend harus:

- mengikuti Laravel conventions
- menggunakan type hints jika sesuai
- menggunakan dependency injection jika diperlukan
- menghindari duplicate logic
- menghindari giant controller
- menghindari giant model
- menggunakan meaningful naming

---

# 92. Testing

Feature penting harus memiliki automated tests.

Prioritas:

```text id="y5k2p8"
Authentication
Authorization
Product ownership
Cart ownership
Checkout
Stock
Order ownership
Vendor isolation
```

---

# 93. Definition of Done

Backend feature dianggap selesai jika:

```text id="r7m4x1"
[ ] Validation
[ ] Authorization
[ ] Business logic
[ ] Database interaction
[ ] Error handling
[ ] Inertia integration
[ ] Security check
[ ] Automated test jika relevan
[ ] No unnecessary duplication
```

---

# 94. Final Backend Architecture

```text id="n2q8v5"
Laravel 13
│
├── Routes
│
├── Controllers
│
├── Form Requests
│
├── Policies
│
├── Services
│
├── Models
│
└── PostgreSQL
        │
        ▼
     Supabase
```

Storage:

```text id="c6x3m9"
Laravel
   ↓
Supabase Storage
```

Frontend:

```text id="p4w7k2"
Laravel
   ↓
Inertia
   ↓
React
```

---

# 95. Status Dokumen

```text id="v9m3q7"
Master PRD:
Completed

Architecture PRD:
Completed

Database PRD:
Completed

Design PRD:
Completed

Frontend PRD:
Completed

Backend PRD:
Completed

AGENTS.md:
Pending

Implementation:
Not Started
```

---

# 96. Source of Truth

Backend implementation harus mengikuti:

```text id="x5n8c2"
master-prd.md
architecture-prd.md
database-prd.md
backend-prd.md
```

Frontend implementation harus mengikuti:

```text id="m7q4v1"
master-prd.md
design-prd.md
frontend-prd.md
Google Stitch
```

Jika terdapat konflik, keputusan harus diperiksa kembali sebelum implementasi.

---

# 97. Multi-Vendor Order Status Foundation

Satu parent `Order` dapat memiliki beberapa `order_vendor_groups`, satu group untuk setiap vendor.
Vendor ownership selalu berasal dari authenticated vendor dan item group, bukan dari input client.
`order_vendor_groups.status` menjadi source of truth fulfillment vendor dengan status:

```text
pending, processing, shipped, completed, cancelled
```

`orders.order_status` tetap ada sebagai derived/cached overall status dengan tambahan
`partially_cancelled`. Parent status merupakan aggregate/cache dari seluruh
`order_vendor_groups.status` dengan aturan:

- semua group `cancelled` menghasilkan `cancelled`;
- ada `cancelled`, tetapi tidak semua group `cancelled`, menghasilkan `partially_cancelled`;
- jika tidak ada `cancelled`, maka `processing` memiliki prioritas di atas `pending`,
  `shipped`, dan `completed`;
- tanpa `cancelled` atau `processing`, `pending` memiliki prioritas di atas `shipped` dan
  `completed`;
- tanpa `cancelled`, `processing`, atau `pending`, `shipped` memiliki prioritas di atas
  `completed`;
- semua group `completed` menghasilkan `completed`.

Karena itu, kombinasi `cancelled` dengan `pending`, `processing`, `shipped`, atau `completed`
menghasilkan `partially_cancelled`, sedangkan `cancelled` + `cancelled` menghasilkan
`cancelled`.
Status mutation, authorization flow, aggregator service, dan controller belum dibuat pada phase schema.

Payment tetap parent-level pada `orders.payment_status` (`pending`, `paid`, `failed`). Checkout
membuat satu payment obligation untuk seluruh order. Payment confirmation tidak boleh dilakukan
oleh vendor pada phase ini dan tidak otomatis mengikuti fulfillment status.

`order_status_histories` dan `payment_status_histories` menjadi audit foundation dengan actor,
previous status, new status, optional reason/source, dan timestamps. Penulisan history dilakukan
pada phase service berikutnya.

Migration harus additive. Existing orders dibackfill menjadi group per distinct order/vendor,
subtotal group dihitung dari item subtotal, status awal memakai parent order status, dan item
ditautkan ke group secara idempotent. `CheckoutService` tetap mempertahankan transaction,
locking, pricing, stock, dan cart clearing sampai integration phase berikutnya.