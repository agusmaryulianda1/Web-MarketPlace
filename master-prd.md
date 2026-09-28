# MASTER PRD
# Multi-Vendor E-Commerce Platform

**Versi:** 1.0.0  
**Status:** Draft / MVP  
**Bahasa:** Indonesia  
**Frontend:** React.js + Inertia.js  
**Backend:** Laravel 13  
**Database:** PostgreSQL melalui Supabase  
**UI:** Tailwind CSS + shadcn/ui  
**AI Coding Agent:** Cline  
**Design Reference:** Google Stitch  

---

# 1. Ringkasan Produk

Aplikasi ini adalah platform **multi-vendor e-commerce** yang memungkinkan banyak vendor/toko menjual produk melalui satu platform.

Platform memiliki tiga jenis pengguna utama:

1. **Admin**
2. **Vendor**
3. **Buyer**

Setiap role memiliki area aplikasi, hak akses, dan fungsi yang berbeda.

Platform akan dibangun menggunakan:

- Laravel 13 sebagai backend dan business logic
- Inertia.js sebagai penghubung backend Laravel dengan frontend React
- React.js sebagai frontend
- PostgreSQL sebagai database
- Supabase sebagai layanan PostgreSQL dan Storage
- Tailwind CSS sebagai styling
- shadcn/ui sebagai reusable UI components
- Cline sebagai AI coding agent
- Google Stitch sebagai referensi utama desain UI/UX

---

# 2. Tujuan Produk

## 2.1 Tujuan Utama

Membangun platform e-commerce multi-vendor yang:

- mudah digunakan oleh buyer
- mudah dikelola oleh vendor
- mudah dikontrol oleh admin
- memiliki UI modern dan profesional
- memiliki struktur kode yang maintainable
- memiliki database yang terstruktur
- memiliki sistem authorization yang jelas
- responsive pada desktop, tablet, dan mobile
- dapat dikembangkan ke fitur yang lebih kompleks di masa depan

## 2.2 Tujuan MVP

MVP berfokus pada proses inti:

```text
Register/Login
      ↓
Buyer mencari produk
      ↓
Buyer melihat detail produk
      ↓
Buyer menambahkan produk ke cart
      ↓
Buyer checkout
      ↓
Order dibuat
      ↓
Vendor melihat order
      ↓
Vendor memproses order
      ↓
Buyer melihat status order
```

Admin berfungsi untuk mengelola platform dan memonitor data utama.

---

# 3. Target Pengguna

## 3.1 Buyer

Pengguna yang membeli produk dari vendor.

Kebutuhan utama:

- mencari produk
- melihat produk
- melihat detail produk
- memasukkan produk ke keranjang
- checkout
- melihat pesanan
- mengelola profil

## 3.2 Vendor

Pengguna yang menjual produk melalui platform.

Kebutuhan utama:

- mengelola toko
- mengelola produk
- melihat pesanan
- memperbarui status pesanan

## 3.3 Admin

Pengelola platform.

Kebutuhan utama:

- melihat kondisi platform
- mengelola user
- mengelola vendor
- mengelola produk
- melihat dan mengelola order

---

# 4. Role dan Hak Akses

## 4.1 Admin

Admin memiliki akses ke:

- Dashboard
- Users
- Vendors
- Products
- Orders

Admin dapat:

- melihat data user
- mengelola user
- melihat vendor
- mengelola vendor
- melihat produk
- mengelola produk
- melihat order
- mengelola status order sesuai aturan sistem

Admin memiliki akses tertinggi terhadap data aplikasi.

---

# 5. Vendor

Vendor memiliki akses ke:

- Dashboard
- Products
- Orders
- Store

Vendor dapat:

- melihat dashboard tokonya
- membuat produk
- melihat produk miliknya
- mengubah produk miliknya
- menghapus produk miliknya
- melihat order yang berisi produk miliknya
- memperbarui status order sesuai aturan
- mengubah informasi toko

Vendor **tidak boleh**:

- mengakses data vendor lain
- mengubah produk vendor lain
- mengubah data user secara global
- mengakses halaman Admin

---

# 6. Buyer

Buyer memiliki akses ke:

- Home
- Products
- Product Detail
- Cart
- Checkout
- Orders
- Profile

Buyer dapat:

- melihat produk
- mencari produk
- memfilter produk berdasarkan kategori
- melihat detail produk
- menambahkan produk ke cart
- mengubah quantity
- menghapus item dari cart
- checkout
- membuat order
- melihat order miliknya
- melihat detail order
- mengubah profile

Buyer **tidak boleh**:

- mengakses dashboard Admin
- mengakses dashboard Vendor
- mengubah produk
- mengubah order milik buyer lain

---

# 7. Authentication

Sistem authentication minimal terdiri dari:

- Login
- Register
- Logout

Setiap user memiliki role.

Role minimal:

```text
admin
vendor
buyer
```

Public registration hanya mendukung role berikut:

```text
buyer
vendor
```

Buyer dapat melakukan public registration. Registration membuat User dengan role `buyer`, serta menyimpan name, email, phone, dan password. Registration Buyer tidak membuat Vendor atau Store.

Vendor dapat melakukan public registration. Registration membuat User dengan role `vendor` dan Vendor profile dengan status awal `active`. Vendor terhubung ke User melalui `vendors.user_id`. User dan Vendor dibuat secara atomic menggunakan database transaction. Registration Vendor tidak membuat Store.

Admin tidak tersedia pada public registration. Admin hanya dibuat melalui mekanisme internal atau administratif.

Setelah login, user diarahkan berdasarkan role:

```text
ADMIN
→ /admin/dashboard

VENDOR
→ /vendor/dashboard

BUYER
→ /
```

Sistem harus menggunakan authorization yang aman.

Frontend tidak boleh menjadi satu-satunya lapisan keamanan.

Authorization harus tetap dilakukan di backend Laravel.

---

# 8. Modul Buyer

## 8.1 Home

Home merupakan halaman utama e-commerce.

Komponen minimal:

- Navbar
- Logo/Brand
- Search
- Navigation
- Hero section
- Category section
- Featured products
- Product cards
- Footer

Home tidak boleh terlalu penuh.

Fokus utama adalah:

- produk
- kategori
- pencarian
- call-to-action

---

# 9. Product Listing

Buyer dapat melihat daftar produk.

Fitur:

- daftar produk
- search
- filter kategori
- sorting sederhana
- pagination

Product card minimal menampilkan:

- gambar produk
- nama produk
- harga
- nama vendor
- tombol/detail

---

# 10. Product Detail

Halaman detail produk menampilkan:

- gambar produk
- nama produk
- harga
- vendor
- kategori
- deskripsi
- stock
- quantity selector

Action:

```text
Tambah ke Keranjang
Beli Sekarang
```

Sistem harus melakukan validasi stock sebelum produk dimasukkan atau diproses dalam checkout.

---

# 11. Shopping Cart

Cart menyimpan produk yang ingin dibeli buyer.

Setiap cart item memiliki minimal:

- product
- quantity
- price
- subtotal

Buyer dapat:

- menambah quantity
- mengurangi quantity
- menghapus item
- melihat subtotal
- melihat total

Sistem harus melakukan validasi:

- product masih tersedia
- product aktif
- stock mencukupi
- harga terbaru

Jangan mempercayai total harga yang dikirim dari frontend.

Total harus dihitung ulang oleh backend.

---

# 12. Checkout

Checkout minimal memiliki:

## Informasi Buyer

- nama
- nomor telepon
- alamat

## Ringkasan Order

- produk
- quantity
- harga
- subtotal
- total

## Pembayaran MVP

Metode pembayaran:

- Transfer Bank
- COD

Checkout menghasilkan order baru.

Backend harus melakukan validasi ulang:

- cart
- product
- stock
- price
- quantity
- total

Sebelum order dibuat.

---

# 13. Multi-Vendor Order

Platform menggunakan konsep multi-vendor.

Contoh:

Buyer membeli:

```text
Product A → Vendor A
Product B → Vendor B
Product C → Vendor A
```

Sistem harus tetap mengetahui vendor pemilik masing-masing product.

Struktur konseptual:

```text
Order
│
├── Order Item
│      └── Product
│             └── Vendor
│
├── Order Item
│      └── Product
│             └── Vendor
│
└── Order Item
       └── Product
              └── Vendor
```

Untuk MVP, satu checkout dapat berisi produk dari beberapa vendor.

Implementasi detail pemisahan order per vendor akan ditentukan dalam `database-prd.md` dan `backend-prd.md`.

---

# 14. Order

Order memiliki informasi minimal:

- order number
- buyer
- total
- payment method
- payment status
- order status
- shipping address
- created date

Status order minimal:

```text
pending
processing
shipped
completed
cancelled
```

Status pembayaran minimal:

```text
pending
paid
failed
```

Untuk MVP, workflow dapat berupa:

```text
pending
   ↓
processing
   ↓
shipped
   ↓
completed
```

Order juga dapat:

```text
pending
   ↓
cancelled
```

Aturan perubahan status akan dijelaskan lebih detail pada backend PRD.

---

# 15. Buyer Orders

Buyer dapat melihat daftar order miliknya.

Informasi:

- order number
- tanggal
- jumlah item
- total
- payment status
- order status

Buyer dapat membuka detail order.

Buyer hanya boleh melihat order miliknya sendiri.

---

# 16. Profile

Buyer dapat melihat dan mengubah:

- nama
- email
- nomor telepon
- alamat
- avatar jika tersedia

Perubahan data harus melalui backend validation.

---

# 17. Modul Vendor

## 17.1 Vendor Dashboard

Dashboard dibuat sederhana.

Statistik minimal:

- Total Produk
- Total Pesanan
- Total Penjualan

Tambahkan:

- pesanan terbaru
- produk terbaru

Tidak perlu dashboard dengan banyak chart untuk MVP.

---

# 18. Vendor Products

Vendor dapat mengelola produk miliknya.

Data minimal produk:

- nama
- kategori
- deskripsi
- harga
- stock
- status
- gambar

Status product:

```text
active
inactive
```

Vendor dapat:

- create
- read
- update
- delete

Vendor hanya boleh mengelola produk miliknya.

---

# 19. Vendor Orders

Vendor dapat melihat order yang berhubungan dengan produk miliknya.

Informasi minimal:

- order number
- buyer
- product
- quantity
- subtotal
- order status
- tanggal

Vendor dapat memperbarui status order sesuai permission.

Vendor tidak boleh melihat atau mengubah data order vendor lain secara tidak sah.

---

# 20. Vendor Store

Vendor memiliki toko.

Informasi minimal:

- nama toko
- logo
- deskripsi
- nomor kontak

Vendor dapat mengubah informasi tokonya.

---

# 21. Modul Admin

## 21.1 Admin Dashboard

Dashboard minimal menampilkan:

- Total Users
- Total Vendors
- Total Products
- Total Orders

Tambahkan:

- order terbaru
- product terbaru

Dashboard tidak perlu menggunakan sistem reporting kompleks pada MVP.

---

# 22. Admin Users

Admin dapat melihat daftar user.

Data:

- nama
- email
- role
- status
- tanggal dibuat

Admin dapat mengelola user sesuai permission.

---

# 23. Admin Vendors

Admin dapat melihat vendor.

Informasi:

- nama vendor
- nama toko
- email
- status
- tanggal bergabung

Admin dapat:

- melihat vendor
- mengubah status vendor
- mengelola data vendor sesuai aturan aplikasi

---

# 24. Admin Products

Admin dapat melihat seluruh produk.

Informasi:

- product
- vendor
- category
- price
- stock
- status
- action

Admin dapat mengelola produk sesuai permission.

---

# 25. Admin Orders

Admin dapat melihat seluruh order.

Informasi:

- order ID
- buyer
- vendor
- total
- status
- tanggal

Admin dapat melihat detail order dan mengelola status sesuai aturan aplikasi.

---

# 26. Kategori Produk

Produk memiliki satu kategori.

Kategori minimal:

- nama
- slug
- deskripsi
- status

Status:

```text
active
inactive
```

Admin menjadi pihak yang mengelola kategori.

Untuk MVP, vendor memilih kategori yang sudah tersedia.

Vendor tidak membuat kategori baru.

---

# 27. Product Image

Produk dapat memiliki gambar.

Minimal:

- satu gambar utama

Sistem dapat dikembangkan untuk mendukung beberapa gambar.

Storage gambar menggunakan:

**Supabase Storage**

Folder storage akan ditentukan pada architecture/database implementation.

---

# 28. Database

Database menggunakan:

**PostgreSQL melalui Supabase**

Entitas utama minimal:

```text
users
vendors
stores
categories
products
product_images
carts
cart_items
orders
order_items
addresses
```

Relationship detail, primary key, foreign key, index, constraint dan tipe data akan ditentukan pada:

```text
database-prd.md
```

---

# 29. Arsitektur Aplikasi

Arsitektur utama:

```text
Browser
   │
   ▼
React.js
   │
   ▼
Inertia.js
   │
   ▼
Laravel 13
   │
   ├── Controllers
   ├── Requests
   ├── Models
   ├── Policies
   ├── Services
   └── Business Logic
   │
   ▼
Supabase PostgreSQL
```

Supabase Storage digunakan untuk file/gambar.

Laravel menjadi backend utama dan pengelola business logic.

React tidak boleh langsung menjalankan business logic penting yang seharusnya dilakukan backend.

---

# 30. Frontend

Frontend menggunakan:

- React.js
- Inertia.js
- Tailwind CSS
- shadcn/ui
- Lucide React

Frontend harus menggunakan reusable components.

Contoh:

```text
resources/js/
├── Components/
├── Layouts/
├── Pages/
├── Hooks/
└── Lib/
```

Page dipisahkan berdasarkan role:

```text
Pages/
├── Admin/
├── Vendor/
├── Buyer/
└── Auth/
```

---

# 31. Layout

Minimal terdapat:

```text
AdminLayout
VendorLayout
BuyerLayout
AuthLayout
```

Buyer:

```text
Navbar
Main Content
Footer
```

Vendor:

```text
Sidebar
Topbar
Main Content
```

Admin:

```text
Sidebar
Topbar
Main Content
```

---

# 32. Design System

Google Stitch menjadi referensi utama desain.

Design implementation harus mengikuti hasil desain Stitch.

Sumber desain:

```text
Google Stitch
     ↓
PNG / HTML Reference
     ↓
design-prd.md
     ↓
React Implementation
```

Jika terdapat perbedaan antara implementasi dan desain, developer/AI agent harus memeriksa `design-prd.md` dan referensi Stitch sebelum membuat keputusan desain baru.

Detail:

- color
- typography
- spacing
- component
- layout
- responsive
- state

akan ditentukan pada:

```text
design-prd.md
```

---

# 33. Responsive Design

Aplikasi harus responsive.

Minimal mendukung:

```text
Desktop
Tablet
Mobile
```

Buyer mobile harus tetap nyaman untuk:

- browsing produk
- search
- melihat detail
- cart
- checkout

Admin dan Vendor mobile tidak harus memiliki pengalaman yang sama dengan desktop, tetapi fungsi utama harus tetap dapat digunakan.

---

# 34. Loading State

Setiap proses asynchronous harus memiliki loading state yang sesuai.

Contoh:

- loading product
- loading order
- submitting form
- updating cart
- checkout processing

Jangan membuat user tidak mengetahui apakah action sedang diproses.

---

# 35. Empty State

Setiap halaman yang dapat tidak memiliki data harus memiliki empty state.

Contoh:

```text
Cart kosong
Tidak ada produk
Belum ada order
Belum ada vendor
Belum ada data
```

Empty state harus memberikan informasi yang jelas dan jika relevan memberikan CTA.

---

# 36. Error Handling

Aplikasi harus menangani:

- validation error
- authentication error
- authorization error
- database error
- network error
- not found
- server error

User harus mendapatkan pesan yang mudah dipahami.

Detail error internal tidak boleh ditampilkan kepada user production.

---

# 37. Validation

Validation harus dilakukan di backend Laravel.

Frontend validation dapat digunakan sebagai UX tambahan.

Untuk form React:

```text
React Hook Form
+
Zod
```

Tetapi backend Laravel tetap menjadi authority utama.

---

# 38. Authorization

Authorization harus dilakukan di backend.

Gunakan mekanisme Laravel yang sesuai, seperti:

- middleware
- policies
- gates

Contoh:

```text
Admin
→ seluruh data sesuai permission

Vendor
→ hanya data miliknya

Buyer
→ hanya data miliknya
```

Frontend route protection bukan pengganti backend authorization.

---

# 39. Security

Security merupakan requirement wajib.

Minimal:

- authentication Laravel
- authorization
- CSRF protection
- server-side validation
- mass assignment protection
- policy authorization
- secure file upload
- input sanitization/validation
- database constraints
- jangan expose secret key
- jangan expose Supabase service role key ke frontend
- jangan hardcode credential

Environment variable digunakan untuk credential.

---

# 40. Supabase

Supabase digunakan untuk:

```text
PostgreSQL
Storage
```

Laravel terhubung ke PostgreSQL Supabase menggunakan konfigurasi environment.

Credential tidak boleh ditulis langsung dalam source code.

Supabase Service Role Key tidak boleh dikirim ke browser/frontend.

---

# 41. Scope MVP

## Termasuk MVP

### Authentication

- Login
- Register
- Logout

### Buyer

- Home
- Product listing
- Product detail
- Cart
- Checkout
- Orders
- Profile

### Vendor

- Dashboard
- Products
- Orders
- Store

### Admin

- Dashboard
- Users
- Vendors
- Products
- Orders

### Core System

- Role
- Authorization
- Categories
- Product image
- Cart
- Order
- Basic payment method
- Basic order status

---

# 42. Di luar Scope MVP

Fitur berikut tidak dibuat pada fase pertama:

- Wishlist
- Product reviews
- Chat
- Coupon
- Voucher
- Flash sale
- Affiliate
- Loyalty points
- Subscription
- Advanced analytics
- Advanced reporting
- Real-time notification
- Advanced recommendation system
- Multi-language
- Multi-currency
- Payment gateway kompleks
- Shipping API kompleks

Fitur tersebut dapat menjadi Phase 2 atau Phase 3.

---

# 43. Prioritas Implementasi

Urutan implementasi wajib:

```text
PHASE 1
Project Foundation
        ↓
PHASE 2
Authentication
        ↓
PHASE 3
Role & Authorization
        ↓
PHASE 4
Category & Product
        ↓
PHASE 5
Buyer Product Experience
        ↓
PHASE 6
Cart
        ↓
PHASE 7
Checkout & Order
        ↓
PHASE 8
Vendor
        ↓
PHASE 9
Admin
        ↓
PHASE 10
Testing & Optimization
```

Jangan mengimplementasikan seluruh fitur sekaligus.

---

# 44. Development Rules

Pengembangan harus dilakukan secara incremental.

Setiap fitur:

```text
Plan
 ↓
Implement
 ↓
Test
 ↓
Fix
 ↓
Review
 ↓
Next Feature
```

AI coding agent tidak boleh mengerjakan banyak modul besar dalam satu task tanpa alasan.

---

# 45. AI Agent

AI coding agent yang digunakan:

**Cline**

Cline harus menggunakan dokumen berikut sebagai referensi:

```text
master-prd.md
architecture-prd.md
database-prd.md
design-prd.md
frontend-prd.md
backend-prd.md
AGENTS.md
```

`master-prd.md` merupakan dokumen induk.

Jika terdapat konflik antar dokumen, Cline harus berhenti dan meminta klarifikasi sebelum melakukan perubahan besar.

---

# 46. Source of Truth

Prioritas sumber kebenaran:

```text
1. Security & Framework Constraints
2. master-prd.md
3. architecture-prd.md
4. database-prd.md
5. backend-prd.md
6. frontend-prd.md
7. design-prd.md
8. Implementation
```

Untuk visual/UI:

```text
Google Stitch
+
design-prd.md
```

Untuk database:

```text
database-prd.md
```

Untuk business logic:

```text
backend-prd.md
```

---

# 47. Definition of Done

Sebuah fitur dianggap selesai jika:

- fitur telah diimplementasikan
- sesuai PRD
- sesuai design reference
- responsive
- validation bekerja
- authorization bekerja
- tidak menghasilkan error
- tidak merusak fitur sebelumnya
- tidak terdapat duplikasi kode yang tidak perlu
- reusable component digunakan jika relevan
- lint berhasil
- build berhasil
- test yang relevan berhasil

---

# 48. Quality Requirements

Kode harus:

- readable
- maintainable
- modular
- reusable
- scalable
- secure

Hindari:

- duplicate code
- hardcoded business logic
- hardcoded credential
- massive component
- massive controller
- unnecessary abstraction
- unnecessary dependency
- unused code

---

# 49. Git Workflow

Pengembangan menggunakan Git.

Commit sebaiknya berdasarkan fitur.

Contoh:

```text
feat: setup inertia react foundation
feat: implement authentication
feat: add product management
feat: implement shopping cart
feat: implement checkout
feat: add vendor dashboard
feat: add admin dashboard
fix: resolve cart stock validation
```

Jangan melakukan commit besar yang mencampurkan banyak fitur yang tidak berkaitan.

---

# 50. Roadmap Pengembangan

## Phase 1 — Foundation

```text
Laravel 13
Inertia
React
Tailwind
shadcn/ui
Supabase
Git
```

## Phase 2 — Authentication

```text
Login
Register
Logout
Role
Authorization
```

## Phase 3 — Catalog

```text
Category
Product
Product Image
```

## Phase 4 — Buyer

```text
Home
Product
Detail
Cart
Checkout
Orders
Profile
```

## Phase 5 — Vendor

```text
Dashboard
Products
Orders
Store
```

## Phase 6 — Admin

```text
Dashboard
Users
Vendors
Products
Orders
```

## Phase 7 — Quality

```text
Validation
Authorization
Security
Responsive
Loading
Empty State
Error State
Testing
Optimization
```

---

# 51. Future Development

Setelah MVP stabil, fitur dapat ditambahkan secara bertahap:

```text
Phase 2
├── Wishlist
├── Reviews
├── Payment Gateway
├── Shipping Integration
└── Notifications

Phase 3
├── Voucher
├── Coupon
├── Advanced Reports
├── Analytics
└── Recommendation

Phase 4
├── Chat
├── Loyalty
├── Affiliate
└── Advanced Vendor Management
```

Fitur Phase 2+ tidak boleh masuk ke MVP kecuali ada keputusan baru.

---

# 52. Prinsip Utama Proyek

Proyek harus mengikuti prinsip:

### Simple First

Bangun fitur paling penting terlebih dahulu.

### Reusable

Gunakan komponen dan logic yang dapat digunakan kembali.

### Secure by Default

Security harus diperhitungkan sejak awal.

### Backend Authority

Business logic dan authorization penting harus dikontrol Laravel.

### Design Consistency

Implementasi UI harus mengikuti Google Stitch dan design system.

### Incremental Development

Jangan membangun seluruh aplikasi sekaligus.

### AI-Assisted, Human-Controlled

Cline digunakan untuk membantu development, tetapi architecture dan keputusan penting tetap dikontrol berdasarkan PRD.

---

# 53. Dokumen Turunan

Dokumen ini menjadi dasar untuk membuat:

```text
architecture-prd.md
database-prd.md
design-prd.md
frontend-prd.md
backend-prd.md
AGENTS.md
```

Setiap dokumen harus konsisten dengan `master-prd.md`.

Jangan membuat requirement baru yang bertentangan dengan dokumen induk tanpa memperbarui master PRD.

---

# 54. Status Dokumen

```text
Master PRD
Status: Draft

Google Stitch Design:
Completed

Architecture PRD:
Pending

Database/ERD PRD:
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

# 55. Catatan Implementasi

Proyek belum memasuki tahap implementasi.

Sebelum coding dimulai, seluruh dokumen architecture, database, design, frontend, backend, dan AI agent rules harus disiapkan.

Cline tidak boleh langsung membangun seluruh aplikasi berdasarkan `master-prd.md` saja.

Implementasi harus dilakukan setelah dokumentasi teknis selesai dan dilakukan secara bertahap berdasarkan roadmap.