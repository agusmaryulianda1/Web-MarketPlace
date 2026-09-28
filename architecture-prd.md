# ARCHITECTURE PRD
# Multi-Vendor E-Commerce Platform

**Versi:** 1.0.0  
**Status:** Draft / MVP  
**Backend:** Laravel 13  
**Frontend:** React.js  
**Bridge:** Inertia.js  
**Database:** PostgreSQL  
**Database Provider:** Supabase  
**Styling:** Tailwind CSS  
**UI Components:** shadcn/ui  
**Icons:** Lucide React  
**AI Agent:** Cline  

---

# 1. Tujuan Dokumen

Dokumen ini menjelaskan architecture aplikasi multi-vendor e-commerce.

Dokumen digunakan sebagai panduan untuk:

- struktur aplikasi
- struktur folder
- komunikasi frontend dan backend
- routing
- authentication
- authorization
- database connection
- file storage
- business logic
- reusable components
- security
- development workflow

Dokumen ini merupakan turunan dari:

```text
master-prd.md
```

Jika terdapat konflik antara dokumen ini dengan `master-prd.md`, requirement pada `master-prd.md` menjadi acuan utama.

---

# 2. Arsitektur Utama

Aplikasi menggunakan arsitektur:

```text
┌──────────────────────────────┐
│           Browser            │
│                              │
│        React.js UI           │
└──────────────┬───────────────┘
               │
               │ Inertia
               ▼
┌──────────────────────────────┐
│         Laravel 13           │
│                              │
│ Routes                       │
│ Controllers                  │
│ Form Requests                │
│ Models                       │
│ Policies                     │
│ Services                     │
│ Business Logic               │
└──────────────┬───────────────┘
               │
       ┌───────┴────────┐
       │                │
       ▼                ▼
┌──────────────┐ ┌──────────────┐
│  Supabase    │ │   Supabase   │
│ PostgreSQL   │ │   Storage    │
└──────────────┘ └──────────────┘
```

Laravel menjadi backend utama.

React menjadi frontend.

Inertia menjadi bridge antara Laravel dan React.

Supabase digunakan sebagai PostgreSQL database dan storage.

---

# 3. Prinsip Architecture

Architecture harus mengikuti prinsip:

## 3.1 Separation of Concerns

Setiap bagian aplikasi memiliki tanggung jawab yang jelas.

```text
Controller
→ menerima request dan mengatur response

Form Request
→ validation

Model
→ representasi data/database

Policy
→ authorization

Service
→ business logic kompleks

React Page
→ UI/page composition

Component
→ reusable UI
```

---

# 4. Backend Architecture

Backend menggunakan Laravel 13.

Komponen utama:

```text
app/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Middleware/
│
├── Models/
│
├── Policies/
│
├── Services/
│
└── Providers/
```

Tidak semua business logic boleh ditempatkan langsung di Controller.

Controller harus tetap relatif sederhana.

---

# 5. Controller

Controller bertugas:

- menerima request
- memanggil validation
- memanggil model/service
- mengembalikan response Inertia
- redirect
- flash message jika diperlukan

Contoh konseptual:

```php
public function store(StoreProductRequest $request)
{
    $product = $this->productService->create(
        $request->validated()
    );

    return redirect()
        ->route('vendor.products.index')
        ->with('success', 'Produk berhasil dibuat.');
}
```

Controller tidak boleh berisi business logic panjang.

---

# 6. Form Request

Validation menggunakan Laravel Form Request.

Contoh:

```text
app/Http/Requests/
├── Auth/
├── Product/
├── Cart/
├── Checkout/
├── Order/
└── Profile/
```

Contoh:

```text
StoreProductRequest
UpdateProductRequest
CheckoutRequest
UpdateProfileRequest
```

Frontend validation dapat menggunakan Zod, tetapi backend Laravel tetap menjadi authority utama.

---

# 7. Model

Model berada di:

```text
app/Models/
```

Model merepresentasikan entity database.

Contoh:

```text
User
Vendor
Store
Category
Product
ProductImage
Cart
CartItem
Order
OrderItem
Address
```

Model harus memiliki relationship yang jelas.

---

# 8. Policy

Authorization menggunakan Laravel Policies.

Contoh:

```text
ProductPolicy
OrderPolicy
VendorPolicy
StorePolicy
UserPolicy
```

Contoh aturan:

```text
Vendor A
→ boleh mengubah Product milik Vendor A

Vendor A
→ tidak boleh mengubah Product Vendor B
```

Buyer:

```text
Buyer A
→ boleh melihat Order Buyer A

Buyer A
→ tidak boleh melihat Order Buyer B
```

---

# 9. Service Layer

Service digunakan untuk business logic yang cukup kompleks.

Contoh:

```text
app/Services/
├── ProductService.php
├── CartService.php
├── CheckoutService.php
├── OrderService.php
└── VendorService.php
```

Tidak semua operasi harus dibuat service.

Service digunakan jika logic:

- kompleks
- digunakan oleh beberapa controller
- membutuhkan transaction
- membutuhkan beberapa model sekaligus

---

# 10. Database Architecture

Database menggunakan:

**PostgreSQL melalui Supabase.**

Laravel menggunakan koneksi PostgreSQL.

Konfigurasi menggunakan `.env`.

Contoh environment:

```env
DB_CONNECTION=pgsql
DB_HOST=...
DB_PORT=5432
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
```

Credential tidak boleh hardcode.

---

# 11. Supabase

Supabase digunakan untuk:

```text
PostgreSQL
Storage
```

Laravel tetap menjadi backend utama.

React tidak boleh melakukan query database utama secara langsung untuk business operation.

Flow utama:

```text
React
 ↓
Inertia
 ↓
Laravel
 ↓
PostgreSQL Supabase
```

---

# 12. Supabase Storage

Supabase Storage digunakan untuk file yang membutuhkan penyimpanan.

MVP minimal:

```text
product-images
```

Opsional:

```text
vendor-images
avatars
```

Upload file dilakukan melalui backend Laravel atau mekanisme yang aman.

Service role key tidak boleh masuk ke frontend.

---

# 13. Frontend Architecture

Frontend menggunakan:

- React.js
- Inertia.js
- Tailwind CSS
- shadcn/ui
- Lucide React

Struktur utama:

```text
resources/js/
├── Components/
├── Layouts/
├── Pages/
├── Hooks/
├── Lib/
└── app.jsx
```

---

# 14. Page Architecture

Page dipisahkan berdasarkan role:

```text
resources/js/Pages/
│
├── Auth/
│
├── Buyer/
│   ├── Home.jsx
│   ├── Products/
│   ├── Cart/
│   ├── Checkout/
│   ├── Orders/
│   └── Profile/
│
├── Vendor/
│   ├── Dashboard.jsx
│   ├── Products/
│   ├── Orders/
│   └── Store/
│
└── Admin/
    ├── Dashboard.jsx
    ├── Users/
    ├── Vendors/
    ├── Products/
    └── Orders/
```

Struktur ini dapat disesuaikan jika hasil implementasi membutuhkan organisasi yang lebih baik.

---

# 15. Layout Architecture

Layout:

```text
resources/js/Layouts/
├── AuthLayout.jsx
├── BuyerLayout.jsx
├── VendorLayout.jsx
└── AdminLayout.jsx
```

## BuyerLayout

```text
Navbar
Main Content
Footer
```

## VendorLayout

```text
Sidebar
Topbar
Main Content
```

## AdminLayout

```text
Sidebar
Topbar
Main Content
```

Layout tidak boleh berisi business logic yang tidak berkaitan dengan UI layout.

---

# 16. Component Architecture

Reusable component berada di:

```text
resources/js/Components/
```

Contoh:

```text
Components/
├── UI/
├── Forms/
├── Navigation/
├── Product/
├── Order/
└── Common/
```

Komponen UI dasar berasal dari shadcn/ui jika tersedia.

Contoh:

```text
Button
Input
Dialog
DropdownMenu
Select
Table
Badge
Card
```

---

# 17. Component Rules

Component harus:

- reusable
- memiliki responsibility jelas
- tidak terlalu besar
- tidak mengandung business logic backend
- mudah dipahami

Hindari membuat satu component berisi seluruh halaman.

Contoh yang tidak disarankan:

```text
MegaProductPage.jsx
```

dengan ribuan baris kode.

Lebih baik:

```text
ProductGallery
ProductInfo
QuantitySelector
AddToCartButton
ProductDescription
```

---

# 18. Hooks

Reusable React logic berada di:

```text
resources/js/Hooks/
```

Contoh jika dibutuhkan:

```text
useCart.js
useDebounce.js
useAuth.js
```

Jangan membuat custom hook hanya untuk logic yang sangat sederhana dan hanya digunakan sekali.

---

# 19. Lib

Utility dan helper frontend berada di:

```text
resources/js/Lib/
```

Contoh:

```text
utils.js
formatCurrency.js
```

Jangan menyimpan credential sensitif di folder ini.

---

# 20. Routing

Routing utama Laravel:

```text
routes/
├── web.php
├── admin.php
├── vendor.php
└── buyer.php
```

Jika implementasi Laravel membutuhkan struktur berbeda, architecture tetap harus mempertahankan pemisahan route berdasarkan domain/role.

---

# 21. Public Routes

Route publik minimal:

```text
/
 /products
 /products/{product}
 /login
 /register
```

Detail route akan ditentukan pada implementasi.

---

# 22. Buyer Routes

Prefix:

```text
/buyer
```

atau route publik untuk halaman shopping.

Contoh:

```text
/
 /products
 /products/{product}
 /cart
 /checkout
 /orders
 /orders/{order}
 /profile
```

Buyer harus authenticated untuk:

```text
cart
checkout
orders
profile
```

---

# 23. Vendor Routes

Prefix:

```text
/vendor
```

Contoh:

```text
/vendor/dashboard
/vendor/products
/vendor/products/create
/vendor/products/{product}/edit
/vendor/orders
/vendor/store
```

Semua route Vendor harus:

```text
auth
+
vendor authorization
```

---

# 24. Admin Routes

Prefix:

```text
/admin
```

Contoh:

```text
/admin/dashboard
/admin/users
/admin/vendors
/admin/products
/admin/orders
```

Semua route Admin harus:

```text
auth
+
admin authorization
```

---

# 25. Authentication Flow

```text
Visitor
   │
   ├── Login
   │
   └── Public Register
         ├── Buyer
         │     └── User(role=buyer)
         │
         └── Vendor
               ├── User(role=vendor)
               └── Vendor(status=active)

Internal / Administrative Creation
         │
         └── Admin User
```

Public registration tidak menghasilkan Admin. Registration Vendor membuat User dan Vendor secara atomic menggunakan `DB::transaction()`. Store tidak dibuat saat registration dan dibuat kemudian melalui fitur atau proses terpisah.

Setelah authentication:

```text
Admin
→ Admin Dashboard

Vendor
→ Vendor Dashboard

Buyer
→ Buyer Home
```

---

# 26. Authorization Flow

Authorization dilakukan backend.

```text
Request
  ↓
Authentication
  ↓
Role Check
  ↓
Policy / Permission
  ↓
Controller
  ↓
Response
```

Frontend hanya menyembunyikan UI yang tidak relevan.

Frontend tidak dianggap sebagai security boundary.

---

# 27. Inertia Architecture

Inertia digunakan sebagai bridge.

Contoh flow:

```text
Browser
   ↓
GET /products
   ↓
Laravel Route
   ↓
ProductController@index
   ↓
Query Product
   ↓
Inertia::render()
   ↓
React Products Page
```

Tidak diperlukan REST API terpisah untuk page navigation utama.

---

# 28. Data Passing

Laravel mengirim data ke React melalui Inertia props.

Contoh:

```php
return Inertia::render('Buyer/Products/Index', [
    'products' => $products,
    'categories' => $categories,
]);
```

React menerima:

```jsx
export default function Index({ products, categories }) {
    // ...
}
```

Data harus dikirim hanya sesuai kebutuhan halaman.

Jangan mengirim seluruh database record jika tidak diperlukan.

---

# 29. Form Submission

Form utama menggunakan Inertia.

Contoh konseptual:

```text
React Form
    ↓
Inertia Request
    ↓
Laravel
    ↓
Form Request Validation
    ↓
Business Logic
    ↓
Database
    ↓
Redirect
    ↓
Inertia Page
```

---

# 30. Cart Architecture

Cart menggunakan database.

Flow:

```text
Buyer
 ↓
Cart
 ↓
Cart Items
 ↓
Products
```

Setiap buyer memiliki cart aktif.

Detail struktur database ditentukan pada:

```text
database-prd.md
```

---

# 31. Checkout Architecture

Checkout merupakan operasi penting.

Flow:

```text
Cart
 ↓
Validate Cart
 ↓
Validate Product
 ↓
Validate Stock
 ↓
Validate Price
 ↓
Calculate Total
 ↓
Create Order
 ↓
Create Order Items
 ↓
Update Stock
 ↓
Clear Cart
 ↓
Commit Transaction
```

Operasi checkout harus menggunakan database transaction.

Jika salah satu proses gagal, transaction harus rollback.

---

# 32. Order Architecture

Order memiliki:

```text
Order
 ↓
Order Items
 ↓
Product
 ↓
Vendor
```

Harga pada `OrderItem` harus menyimpan harga saat transaksi.

Jangan menghitung ulang histori order menggunakan harga product saat ini.

Contoh:

```text
Product price sekarang:
Rp150.000

Harga saat order:
Rp120.000
```

Order tetap menggunakan:

```text
Rp120.000
```

---

# 33. Stock Management

Stock harus divalidasi di backend.

Ketika checkout:

```text
requested quantity
        ↓
check current stock
        ↓
stock sufficient?
    ┌───┴───┐
   YES      NO
    ↓        ↓
continue    error
```

Update stock harus dilakukan secara aman untuk mencegah overselling.

---

# 34. Multi-Vendor Architecture

Setiap product dimiliki oleh vendor.

```text
Vendor
  │
  └── Products
```

Order item memiliki relasi ke product.

Dengan demikian sistem dapat mengetahui:

```text
Order
 ├── Product A → Vendor A
 ├── Product B → Vendor B
 └── Product C → Vendor A
```

Detail aturan order multi-vendor ditentukan pada `database-prd.md` dan `backend-prd.md`.

---

# 35. File Upload Architecture

Product image:

```text
React
 ↓
Inertia Form
 ↓
Laravel
 ↓
Validation
 ↓
Supabase Storage
 ↓
Database stores file reference/path
```

File upload harus divalidasi:

- MIME type
- extension
- ukuran
- jumlah file jika dibatasi

File yang tidak valid harus ditolak.

---

# 36. Error Architecture

Jenis error:

```text
Validation Error
Authorization Error
Authentication Error
Not Found
Business Logic Error
Database Error
Server Error
```

User harus menerima pesan yang aman dan mudah dipahami.

Informasi sensitif seperti stack trace tidak boleh ditampilkan di production.

---

# 37. Flash Message

Gunakan flash message untuk action seperti:

```text
Produk berhasil dibuat.
Produk berhasil diperbarui.
Produk berhasil dihapus.
Order berhasil dibuat.
Profile berhasil diperbarui.
```

Frontend menampilkan flash message melalui reusable notification component.

---

# 38. Pagination

Data yang dapat berkembang besar harus menggunakan pagination.

Minimal:

```text
Products
Orders
Users
Vendors
```

Pagination harus dilakukan di backend.

Jangan mengambil seluruh database lalu melakukan pagination di browser.

---

# 39. Search

Search utama dilakukan di backend.

Contoh:

```text
GET /products?search=laptop
```

Laravel melakukan query search.

Untuk search yang sering digunakan, frontend dapat menggunakan debounce.

---

# 40. Filtering

Filter minimal:

```text
Category
Price
```

Jika diperlukan berdasarkan desain dan scope.

Filter dilakukan di backend untuk dataset besar.

---

# 41. Performance

Prioritas performance:

- pagination
- eager loading
- query optimization
- image optimization
- avoid unnecessary database queries
- avoid unnecessary React re-render
- lazy loading jika relevan

Hindari N+1 query.

---

# 42. Security Architecture

Wajib:

```text
Authentication
Authorization
CSRF
Validation
Policies
Mass Assignment Protection
Secure File Upload
Environment Variables
Database Constraints
```

Jangan expose:

```text
SUPABASE_SERVICE_ROLE_KEY
Database Password
Private API Keys
Application Secrets
```

ke frontend.

---

# 43. Environment Configuration

Credential hanya melalui `.env`.

Contoh:

```env
APP_NAME=
APP_ENV=
APP_KEY=
APP_URL=

DB_CONNECTION=pgsql
DB_HOST=
DB_PORT=5432
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

SUPABASE_URL=
SUPABASE_ANON_KEY=
SUPABASE_SERVICE_ROLE_KEY=
```

`SUPABASE_SERVICE_ROLE_KEY` hanya boleh digunakan di server-side jika memang dibutuhkan.

Jangan memasukkannya ke:

```text
React
JavaScript bundle
public/
Git
```

---

# 44. Dependency Rules

Dependency baru hanya boleh ditambahkan jika:

- benar-benar dibutuhkan
- memiliki manfaat jelas
- tidak dapat diselesaikan dengan dependency yang sudah ada
- kompatibel dengan stack

Jangan menambahkan library hanya karena tersedia.

---

# 45. State Management

Untuk MVP tidak perlu menggunakan global state management kompleks seperti Redux jika tidak diperlukan.

Inertia props dan React state dapat digunakan terlebih dahulu.

Global state hanya ditambahkan jika kebutuhan nyata muncul.

---

# 46. API Architecture

MVP tidak membutuhkan REST API terpisah untuk seluruh fitur aplikasi.

Inertia menjadi mekanisme komunikasi utama.

REST API hanya dibuat jika memang dibutuhkan untuk:

- external integration
- mobile application
- third-party integration
- kebutuhan khusus lainnya

Jangan membuat API layer yang tidak diperlukan.

---

# 47. Testing Architecture

Testing dilakukan pada beberapa level.

## Backend

Test:

- authentication
- authorization
- product
- cart
- checkout
- order
- stock

## Frontend

Test:

- component penting
- form
- interaction penting

## Feature Testing

Minimal memastikan:

```text
Buyer dapat checkout
Vendor dapat melihat order
Admin dapat melihat order
Vendor tidak dapat mengakses vendor lain
Buyer tidak dapat melihat order buyer lain
```

---

# 48. Development Environment

Development lokal menggunakan:

```text
Laravel
PHP
Composer
Node.js
npm
PostgreSQL/Supabase
Git
```

Environment production harus menggunakan credential dan konfigurasi berbeda dari development.

---

# 49. Folder Structure Target

Struktur target:

```text
project/
│
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Requests/
│   │   └── Middleware/
│   │
│   ├── Models/
│   ├── Policies/
│   └── Services/
│
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
│
├── resources/
│   ├── js/
│   │   ├── Components/
│   │   ├── Layouts/
│   │   ├── Pages/
│   │   │   ├── Auth/
│   │   │   ├── Admin/
│   │   │   ├── Vendor/
│   │   │   └── Buyer/
│   │   ├── Hooks/
│   │   └── Lib/
│   │
│   └── css/
│
├── routes/
│   ├── web.php
│   ├── admin.php
│   ├── vendor.php
│   └── buyer.php
│
├── public/
│
├── design/
│
├── master-prd.md
├── architecture-prd.md
├── database-prd.md
├── design-prd.md
├── frontend-prd.md
├── backend-prd.md
└── AGENTS.md
```

Struktur ini merupakan target architecture, bukan alasan untuk membuat seluruh folder sekaligus sebelum diperlukan.

---

# 50. Development Workflow

Implementasi dilakukan:

```text
PRD
 ↓
Plan
 ↓
Implement
 ↓
Run Test
 ↓
Fix
 ↓
Review
 ↓
Commit
 ↓
Next Feature
```

AI agent tidak boleh mengimplementasikan seluruh aplikasi dalam satu langkah.

---

# 51. Cline Rules

Sebelum coding, Cline wajib:

1. membaca `master-prd.md`
2. membaca `architecture-prd.md`
3. membaca PRD terkait
4. melakukan audit project
5. memahami struktur existing code
6. membuat implementation plan
7. mengimplementasikan secara bertahap
8. melakukan testing
9. memperbaiki error
10. melaporkan perubahan

Cline tidak boleh:

- menghapus file secara sembarangan
- mengganti architecture tanpa alasan
- menambahkan dependency tanpa alasan
- membuat fitur di luar scope
- hardcode credential
- melewati backend authorization
- membuat massive refactoring tanpa kebutuhan

---

# 52. Prinsip Perubahan Kode

Sebelum mengubah kode:

```text
Understand
 ↓
Plan
 ↓
Change
 ↓
Test
```

Jika perubahan menyentuh architecture atau database secara signifikan, Cline harus berhenti dan meminta konfirmasi.

---

# 53. Definition of Done

Architecture dianggap siap untuk implementasi jika:

- Laravel 13 dapat berjalan
- Inertia dapat berjalan
- React dapat berjalan
- Tailwind dapat berjalan
- shadcn/ui dapat digunakan
- Supabase PostgreSQL dapat terkoneksi
- authentication architecture jelas
- role architecture jelas
- authorization architecture jelas
- folder structure jelas
- routing strategy jelas
- security strategy jelas

---

# 54. Status

```text
Architecture PRD:
Draft

Master PRD:
Completed

Database PRD:
Pending

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

# 55. Catatan

Dokumen ini mendefinisikan architecture tingkat aplikasi.

Detail database harus mengikuti:

```text
database-prd.md
```

Detail visual harus mengikuti:

```text
design-prd.md
```

Detail frontend harus mengikuti:

```text
frontend-prd.md
```

Detail backend harus mengikuti:

```text
backend-prd.md
```

Aturan kerja AI agent harus mengikuti:

```text
AGENTS.md
```

Jangan membuat keputusan architecture besar yang bertentangan dengan dokumen ini tanpa memperbarui PRD terkait.

---

# 56. Multi-Vendor Order Architecture

`orders` adalah checkout/payment aggregate. `order_vendor_groups` adalah fulfillment aggregate
per vendor; `order_items` menunjuk group yang sesuai. Satu order dapat memiliki banyak group.

Fulfillment source of truth berada pada `order_vendor_groups.status`. `orders.order_status`
dipertahankan sebagai derived/cached overall status: `pending`, `processing`, `shipped`,
`completed`, `cancelled`, dan `partially_cancelled`. Parent status merupakan aggregate/cache
dari seluruh status group. Aturan aggregation:

- semua group `cancelled` → `cancelled`;
- ada group `cancelled`, tetapi tidak semua group `cancelled` → `partially_cancelled`;
- jika tidak ada group `cancelled` dan ada `processing` → `processing`;
- jika tidak ada `cancelled`/`processing`, tetapi ada `pending` → `pending`;
- jika tidak ada `cancelled`/`processing`/`pending`, tetapi ada `shipped` → `shipped`;
- jika semua group `completed` → `completed`.

Dengan demikian, `cancelled` + `pending`, `processing`, `shipped`, atau `completed`
menghasilkan `partially_cancelled`, sedangkan `cancelled` + `cancelled` menghasilkan
`cancelled`.

Payment tetap parent-level (`orders.payment_method`, `orders.payment_status`). Vendor tidak
mendapat akses untuk mengubah parent payment. Payment confirmation, status mutation, admin
override, cancellation, dan stock restoration berada di phase berikutnya.

`order_status_histories` dan `payment_status_histories` menjadi audit persistence layer.
Schema dibuat sekarang, tetapi service/controller/UI penulis history belum dibuat.

Backward compatibility memakai additive migrations dan idempotent backfill: distinct vendor
per order menjadi group, parent status menjadi initial group status, subtotal dihitung dari item,
dan item ditautkan ke group. Existing migrations tetap immutable.