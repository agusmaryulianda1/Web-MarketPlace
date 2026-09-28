# AGENTS.md

# Multi-Vendor E-Commerce Platform

Dokumen ini merupakan aturan utama untuk AI coding agent yang bekerja pada project ini.

AI agent wajib membaca dan memahami dokumen ini sebelum melakukan perubahan pada source code.

---

# 1. PROJECT OVERVIEW

Project ini adalah aplikasi:

```text
Multi-Vendor E-Commerce
```

Tech stack:

```text
Backend:
Laravel 13

Frontend:
React.js

Bridge:
Inertia.js

Database:
PostgreSQL

Database Provider:
Supabase

Storage:
Supabase Storage

Styling:
Tailwind CSS

UI:
shadcn/ui

Icons:
Lucide React

Forms:
React Hook Form + Zod
```

---

# 2. PRIMARY RULE

Jangan langsung menulis kode sebelum memahami:

```text
master-prd.md
architecture-prd.md
database-prd.md
design-prd.md
frontend-prd.md
backend-prd.md
AGENTS.md
```

Dokumen tersebut merupakan project specification.

---

# 3. SOURCE OF TRUTH

Gunakan urutan berikut:

```text
Product Requirements
        ↓
master-prd.md

Architecture
        ↓
architecture-prd.md

Database
        ↓
database-prd.md

Design
        ↓
design-prd.md

Frontend
        ↓
frontend-prd.md

Backend
        ↓
backend-prd.md

Agent Behavior
        ↓
AGENTS.md
```

Jika terdapat konflik antar dokumen:

1. jangan langsung memilih sendiri
2. identifikasi konflik
3. gunakan dokumen yang lebih spesifik
4. jika konflik berdampak besar terhadap architecture/database, berhenti dan minta keputusan user

---

# 4. GOOGLE STITCH DESIGN

Project memiliki design reference:

```text
design/
├── admin/
├── vendor/
├── buyer/
└── login/
```

Google Stitch adalah visual source of truth.

AI agent wajib memeriksa folder tersebut sebelum mengimplementasikan UI.

---

# 5. STITCH IMPLEMENTATION RULE

Jangan mengarang desain baru jika desain Stitch sudah tersedia.

Workflow:

```text
Stitch Design
     ↓
Analyze
     ↓
Identify Layout
     ↓
Identify Components
     ↓
React Implementation
     ↓
Tailwind
     ↓
shadcn/ui
     ↓
Visual Verification
```

---

# 6. STITCH HTML RULE

HTML dari Stitch adalah reference.

Jangan copy-paste HTML Stitch secara mentah ke production.

HTML harus diterjemahkan menjadi:

```text
React Components
+
Tailwind Classes
+
Reusable Components
```

---

# 7. STITCH PNG RULE

PNG digunakan untuk membandingkan hasil implementasi.

Periksa:

```text
Layout
Spacing
Typography
Colors
Cards
Buttons
Images
Navigation
Responsive behavior
```

Hasil implementasi harus sedekat mungkin dengan desain.

---

# 8. DO NOT INVENT UI

Jangan membuat:

```text
layout baru
color scheme baru
button style baru
navigation baru
card style baru
```

jika sudah tersedia dalam desain atau design system.

---

# 9. PROJECT SCOPE

MVP:

```text
Authentication
Login
Register
Logout
```

Buyer:

```text
Home
Products
Product Detail
Cart
Checkout
Orders
Profile
```

Vendor:

```text
Dashboard
Products
Orders
Store
```

Admin:

```text
Dashboard
Users
Vendors
Products
Orders
```

---

# 10. OUT OF SCOPE

Jangan mengimplementasikan fitur berikut tanpa instruksi user:

```text
Wishlist
Reviews
Chat
Coupons
Vouchers
Flash Sale
Affiliate
Loyalty Points
Subscription
Advanced Analytics
Real-time Notifications
Payment Gateway
Shipping API
Recommendation Engine
Multi Currency
Multi Language
```

---

# 11. NO FEATURE CREEP

Jika menemukan ide fitur tambahan:

Jangan langsung implementasikan.

Laporkan:

```text
Potential future feature:
[feature]

Reason:
[reason]

Impact:
[impact]
```

Kemudian tunggu instruksi.

---

# 12. WORKFLOW

Setiap task harus mengikuti:

```text
1. Understand
2. Inspect
3. Plan
4. Implement
5. Test
6. Fix
7. Verify
8. Report
```

---

# 13. UNDERSTAND

Sebelum coding:

- pahami request user
- identifikasi feature
- cari PRD terkait
- identifikasi dependency
- identifikasi halaman terkait
- identifikasi database yang terlibat

---

# 14. INSPECT

Sebelum membuat file baru:

Periksa apakah file/component/service yang dibutuhkan sudah ada.

Jangan membuat duplicate.

Contoh buruk:

```text
ProductCard.jsx
ProductCardNew.jsx
ProductCardV2.jsx
ProductCardFinal.jsx
```

Jika component sudah ada, gunakan atau refactor component tersebut.

---

# 15. PLAN

Sebelum implementation besar, buat rencana singkat:

```text
Files to modify:
- ...

Files to create:
- ...

Database changes:
- ...

Routes:
- ...

Components:
- ...

Testing:
- ...
```

Untuk perubahan kecil, planning dapat dilakukan secara internal.

---

# 16. SMALL CHANGES

Jangan mengubah banyak bagian project yang tidak berkaitan dengan task.

Jika user meminta:

```text
Create Product
```

jangan sekaligus mengubah:

```text
Authentication
Dashboard
Order
Navigation
Database architecture
```

kecuali memang diperlukan.

---

# 17. INCREMENTAL DEVELOPMENT

Implementasikan feature secara bertahap.

Urutan utama:

```text
Foundation
   ↓
Authentication
   ↓
Authorization
   ↓
Products
   ↓
Buyer Product Browsing
   ↓
Cart
   ↓
Checkout
   ↓
Orders
   ↓
Vendor
   ↓
Admin
```

---

# 18. DO NOT BIG BANG

Jangan mengimplementasikan seluruh aplikasi dalam satu prompt/task.

Jangan membuat ratusan file sekaligus tanpa validasi.

Setiap milestone harus dapat dijalankan dan diuji.

---

# 19. BACKEND AUTHORITY

Laravel adalah source of truth untuk:

```text
Authentication
Authorization
Validation
Product
Stock
Cart
Order
Payment Status
Order Status
User Role
Vendor Ownership
```

React tidak boleh menggantikan backend logic.

---

# 20. FRONTEND AUTHORITY

React bertanggung jawab terhadap:

```text
UI
Interaction
Form State
Loading State
Empty State
Error State
Responsive Layout
```

---

# 21. NO DIRECT DATABASE ACCESS

React tidak boleh langsung mengakses PostgreSQL.

Architecture:

```text
React
 ↓
Inertia
 ↓
Laravel
 ↓
PostgreSQL
```

---

# 22. SUPABASE RULE

Supabase digunakan sebagai:

```text
PostgreSQL Database
Storage
```

Laravel tetap menjadi backend utama.

Jangan membuat Supabase menjadi backend kedua tanpa instruksi.

---

# 23. SUPABASE SECRET

Jangan expose:

```text
Database Password
Supabase Service Role Key
APP_KEY
Private API Key
```

ke frontend.

Jangan commit secrets ke Git.

---

# 24. DATABASE RULE

Sebelum membuat migration:

Baca:

```text
database-prd.md
```

Jangan membuat table baru hanya karena terasa diperlukan.

Pastikan table tersebut memang termasuk scope atau sudah disetujui user.

---

# 25. DATABASE CHANGE

Jika database change diperlukan:

```text
Inspect existing migration
        ↓
Compare database-prd.md
        ↓
Create migration
        ↓
Update model
        ↓
Update relationships
        ↓
Test
```

Jangan mengedit migration lama yang sudah digunakan pada environment penting tanpa alasan kuat.

---

# 26. MODEL RULE

Eloquent Model harus fokus pada:

```text
Relationships
Casts
Scopes
Model configuration
```

Jangan membuat model menjadi tempat seluruh business logic.

---

# 27. CONTROLLER RULE

Controller harus tipis.

Ideal:

```text
Request
 ↓
Validation
 ↓
Authorization
 ↓
Service
 ↓
Response
```

Jangan membuat controller dengan ratusan baris.

---

# 28. FORM REQUEST RULE

Gunakan Form Request untuk validation kompleks.

Contoh:

```text
StoreProductRequest
UpdateProductRequest
CheckoutRequest
RegisterRequest
```

---

# 29. POLICY RULE

Gunakan Policy untuk authorization resource.

Contoh:

```text
ProductPolicy
OrderPolicy
StorePolicy
VendorPolicy
```

---

# 30. SERVICE RULE

Gunakan Service jika logic:

- kompleks
- multi-step
- transaction
- reusable

Contoh:

```text
CheckoutService
CartService
OrderService
ProductService
```

Jangan membuat service untuk setiap method CRUD sederhana tanpa alasan.

---

# 31. ROLE SECURITY

Role:

```text
admin
vendor
buyer
```

User tidak boleh mengubah role dirinya sendiri.

Jangan percaya:

```text
role
vendor_id
user_id
```

dari request client.

Public registration mendukung Buyer dan Vendor.

Role public registration hanya:

```text
buyer
vendor
```

Admin tidak tersedia pada public registration. Admin creation merupakan proses internal atau administratif.

Vendor registration harus membuat User dan Vendor secara atomic menggunakan `DB::transaction()`. Vendor memiliki status awal `active` dan terhubung melalui `user_id`.

Store tidak dibuat otomatis saat registration. Store dibuat kemudian melalui fitur atau proses terpisah.

---

# 32. VENDOR OWNERSHIP

Vendor ownership harus ditentukan backend.

Contoh:

```text
auth()->user()
        ↓
vendor
        ↓
vendor_id
```

Jangan menggunakan:

```text
request('vendor_id')
```

sebagai source of truth.

---

# 33. ORDER SECURITY

Buyer tidak boleh menentukan:

```text
total_amount
payment_status
order_status
vendor_id
```

Backend menentukan semuanya.

---

# 34. STOCK SECURITY

Stock hanya boleh dimodifikasi melalui backend.

Frontend tidak boleh menjadi sumber stock.

Checkout harus memeriksa stock kembali.

---

# 35. CHECKOUT TRANSACTION

Checkout harus menggunakan database transaction.

Flow:

```text
Validate
 ↓
Calculate
 ↓
Create Order
 ↓
Create Order Items
 ↓
Reduce Stock
 ↓
Clear Cart
 ↓
Commit
```

Jika gagal:

```text
Rollback
```

---

# 36. MULTI-VENDOR RULE

Satu order dapat memiliki product dari beberapa vendor.

Contoh:

```text
Order #001

Product A → Vendor A
Product B → Vendor B
Product C → Vendor A
```

Vendor hanya melihat item miliknya.

---

# 37. ORDER ITEM SNAPSHOT

Order item harus menyimpan snapshot:

```text
product_name
price
quantity
subtotal
vendor_id
```

Jangan bergantung sepenuhnya pada data product saat ini untuk histori transaksi.

---

# 38. PRODUCT DELETION

Jangan hard delete product yang memiliki histori transaksi tanpa mempertimbangkan integrity.

Lebih aman menggunakan:

```text
inactive
```

jika product sudah pernah digunakan dalam order.

---

# 39. FRONTEND STRUCTURE

Gunakan:

```text
resources/js/
├── Components/
├── Layouts/
├── Pages/
├── Hooks/
├── Lib/
├── Utils/
└── app.jsx
```

Jangan mengubah architecture ini tanpa alasan.

---

# 40. PAGE RULE

Pages digunakan untuk halaman Inertia.

Contoh:

```text
Pages/Buyer/Products/Index.jsx
Pages/Vendor/Products/Index.jsx
Pages/Admin/Products/Index.jsx
```

---

# 41. COMPONENT RULE

Components digunakan untuk reusable UI.

Contoh:

```text
ProductCard
OrderStatusBadge
ProductForm
EmptyState
PageHeader
```

---

# 42. COMPONENT SIZE

Jika component menjadi terlalu besar dan memiliki beberapa responsibility:

Evaluasi untuk memecahnya.

Hindari:

```text
500+ lines component
```

jika dapat dipisahkan secara masuk akal.

---

# 43. NO OVER-ABSTRACTION

Jangan membuat abstraction hanya demi terlihat clean.

Prioritaskan:

```text
Simple
Readable
Maintainable
Reusable when needed
```

---

# 44. TAILWIND RULE

Gunakan Tailwind CSS untuk styling.

Jangan membuat CSS file baru untuk setiap component jika utility classes sudah mencukupi.

Custom CSS hanya jika memang diperlukan.

---

# 45. SHADCN RULE

Gunakan shadcn/ui untuk reusable UI primitive.

Contoh:

```text
Button
Input
Card
Dialog
Table
Badge
Select
```

Jangan menginstall seluruh component library tanpa kebutuhan.

---

# 46. ICON RULE

Gunakan:

```text
Lucide React
```

Jangan mencampur icon library tanpa alasan.

Jangan menggunakan emoji sebagai icon utama UI.

---

# 47. FORM RULE

Frontend forms menggunakan:

```text
React Hook Form
+
Zod
```

Backend tetap melakukan validation.

---

# 48. INERTIA RULE

Gunakan Inertia untuk:

```text
Navigation
Form submission
Server interaction
```

Jangan membuat REST API hanya untuk kebutuhan frontend MVP jika tidak diperlukan.

---

# 49. SERVER PROPS

Gunakan Inertia props untuk data server.

Contoh:

```text
products
orders
categories
auth.user
cart
```

Jangan mengambil ulang data melalui client-side API tanpa kebutuhan.

---

# 50. STATE MANAGEMENT

Prioritas:

```text
Inertia Props
 ↓
useState
 ↓
useEffect
 ↓
Context
 ↓
Global State Library
```

Jangan menambahkan Redux/Zustand hanya karena aplikasi menggunakan React.

---

# 51. LOADING STATE

Setiap asynchronous action penting harus memiliki loading state.

Contoh:

```text
Creating...
Updating...
Deleting...
Processing...
```

---

# 52. EMPTY STATE

List harus memiliki empty state.

Contoh:

```text
No products found.
No orders found.
Your cart is empty.
```

---

# 53. ERROR STATE

Error harus ditampilkan dengan jelas.

Jangan hanya:

```text
console.error()
```

User harus mendapatkan feedback yang sesuai.

---

# 54. RESPONSIVE

Semua halaman harus responsive.

Minimal:

```text
Mobile
Tablet
Desktop
```

Jangan membuat desktop-only UI jika desain Stitch tidak demikian.

---

# 55. ACCESSIBILITY

Perhatikan:

```text
Semantic HTML
Labels
Alt Text
Keyboard Navigation
Focus State
Accessible Buttons
Color Contrast
```

---

# 56. DESIGN SYSTEM

Gunakan design system yang konsisten.

Jangan membuat:

```text
Button A
Button B
Button C
```

dengan style berbeda tanpa alasan.

---

# 57. COLOR RULE

Gunakan color token dari design system.

Default design reference:

```text
Background:
#0A0A0A

Surface:
#141414

Elevated:
#1C1C1C

Primary:
#F97316

Primary Hover:
#EA580C
```

Jika desain Stitch memiliki final value yang berbeda, ikuti Stitch.

---

# 58. NO RANDOM COLORS

Jangan menggunakan warna random seperti:

```text
blue-500
green-500
purple-500
```

hanya karena terlihat bagus.

Pastikan warna sesuai design system.

---

# 59. ROUTE RULE

Gunakan named route.

Contoh:

```text
buyer.products.index
vendor.products.index
admin.products.index
```

Hindari hardcoded route jika route helper tersedia.

---

# 60. ERROR-FREE REQUIREMENT

Sebelum feature dianggap selesai:

```text
npm run lint
```

harus berhasil.

Kemudian:

```text
npm run build
```

harus berhasil.

Laravel tests juga harus dijalankan jika relevan.

---

# 61. TESTING

Prioritas testing:

```text
Authentication
Authorization
Product ownership
Cart ownership
Checkout
Stock
Orders
Vendor isolation
```

---

# 62. BROWSER VERIFICATION

Untuk UI feature, verifikasi:

```text
Desktop
Tablet
Mobile
```

Periksa terhadap desain Stitch.

---

# 63. VISUAL VERIFICATION

Setelah UI dibuat:

```text
Run application
 ↓
Open page
 ↓
Compare with Stitch
 ↓
Identify differences
 ↓
Fix
 ↓
Verify again
```

Jangan menganggap UI selesai hanya karena tidak ada error JavaScript.

---

# 64. GIT RULE

Setiap milestone harus menghasilkan perubahan yang dapat dipahami.

Contoh commit:

```text
feat: setup authentication
feat: implement buyer products
feat: implement shopping cart
feat: implement checkout
feat: implement vendor products
feat: implement admin users
```

Hindari commit:

```text
update
fix
test
changes
final
final2
```

tanpa konteks.

---

# 65. DO NOT DESTROY USER WORK

Jangan:

```text
delete project
delete design
rewrite entire project
reset database
remove existing feature
```

tanpa alasan dan persetujuan user.

---

# 66. MIGRATION SAFETY

Jangan menjalankan:

```text
migrate:fresh
```

atau command destructive lainnya pada database penting tanpa instruksi eksplisit.

Untuk development baru, destructive command tetap harus dijelaskan sebelum dijalankan jika berpotensi menghapus data.

---

# 67. FILE SAFETY

Sebelum menghapus file:

1. pastikan file tidak digunakan
2. search references
3. pastikan replacement tersedia
4. baru hapus jika memang diperlukan

---

# 68. DEPENDENCY RULE

Jangan install package baru tanpa alasan.

Sebelum install:

```text
Check package.json
Check existing dependency
Check whether functionality already exists
```

Jika package baru benar-benar diperlukan, jelaskan:

```text
Package:
Reason:
Alternative:
```

---

# 69. NO DUPLICATE DEPENDENCIES

Jangan menginstall library yang memiliki fungsi sama dengan dependency yang sudah ada.

Contoh:

Jika sudah menggunakan:

```text
Lucide React
```

jangan menambahkan icon library lain hanya untuk beberapa icon.

---

# 70. CODE QUALITY

Kode harus:

- readable
- maintainable
- consistent
- modular
- secure
- mengikuti framework conventions

Hindari:

```text
giant components
giant controllers
duplicate logic
hardcoded secrets
magic values
unnecessary abstraction
```

---

# 71. COMMENTS

Komentar hanya digunakan jika membantu menjelaskan:

- business rule
- alasan teknis
- behavior yang tidak obvious

Jangan memenuhi code dengan komentar yang hanya mengulang kode.

Buruk:

```text
// Set product name
$product->name = $name;
```

---

# 72. ENVIRONMENT

Jangan commit:

```text
.env
```

ke Git.

Pastikan `.env.example` memiliki variable yang diperlukan tanpa credential asli.

---

# 73. DEBUGGING RULE

Jika menemukan error:

```text
1. Read error
2. Identify root cause
3. Inspect related file
4. Fix root cause
5. Run test
6. Verify
```

Jangan melakukan random changes sampai error hilang.

---

# 74. DO NOT MASK ERRORS

Jangan menyelesaikan error dengan:

```text
@ suppress
try/catch tanpa handling
disable lint
disable type checking
ignore warning
```

jika tindakan tersebut hanya menyembunyikan masalah.

---

# 75. LARAVEL ERROR RULE

Jika terdapat error Laravel:

Cari root cause.

Contoh:

```text
SQLSTATE
Undefined relationship
Null property
Authorization failure
Validation error
Route error
Migration error
```

Jangan hanya menambahkan optional chaining atau null fallback jika root cause sebenarnya adalah data/relationship yang salah.

---

# 76. REACT ERROR RULE

Jika terdapat:

```text
React warning
Missing key
Undefined property
Invalid hook
Infinite render
```

perbaiki root cause.

Jangan hanya menekan warning.

---

# 77. DATABASE ERROR RULE

Jika migration/database error:

```text
Inspect migration
Inspect schema
Inspect relationship
Inspect database state
```

Jangan langsung:

```text
migrate:fresh
```

sebagai solusi default.

---

# 78. TASK COMPLETION REPORT

Setelah menyelesaikan task, berikan report singkat:

```text
## Completed

- ...
- ...
- ...

## Files Changed

- ...
- ...

## Testing

- npm run lint
- npm run build
- php artisan test

## Result

PASS / NEEDS ATTENTION

## Notes

- ...
```

Jangan memberikan laporan seolah-olah test berhasil jika test belum benar-benar dijalankan.

---

# 79. WHEN TO ASK USER

Tanyakan user jika:

- terdapat konflik PRD
- perubahan database besar
- architecture harus diubah
- fitur di luar MVP
- desain Stitch tidak jelas dan keputusan akan berdampak besar
- credential diperlukan tetapi belum tersedia
- operasi dapat menghapus data penting

---

# 80. WHEN NOT TO ASK USER

Jangan bertanya untuk hal kecil yang sudah jelas dari PRD.

Contoh:

```text
Nama component
Nama variable
Cara memecah component
Tailwind utility
Simple refactor
```

Gunakan best practice yang konsisten.

---

# 81. PRIORITY

Jika harus memilih antara:

```text
Speed
vs
Correctness
```

prioritaskan:

```text
Correctness
```

Jika harus memilih:

```text
Feature quantity
vs
Code quality
```

prioritaskan:

```text
Code quality
```

Jika harus memilih:

```text
Quick implementation
vs
Architecture consistency
```

prioritaskan:

```text
Architecture consistency
```

---

# 82. MVP FIRST

Tujuan utama adalah menghasilkan:

```text
Working MVP
```

bukan aplikasi dengan fitur sebanyak mungkin.

Prioritas:

```text
Core functionality
 ↓
Correct architecture
 ↓
Security
 ↓
UX
 ↓
Polish
 ↓
Future features
```

---

# 83. FINAL DEVELOPMENT FLOW

Gunakan workflow:

```text
PRD
 ↓
Architecture
 ↓
Database
 ↓
Design
 ↓
Frontend
 ↓
Backend
 ↓
Implementation
 ↓
Testing
 ↓
Visual Verification
 ↓
Git Commit
```

---

# 84. IMPLEMENTATION PHASES

## Phase 1 — Foundation

```text
Laravel 13
Inertia
React
Tailwind
shadcn/ui
Supabase
Environment
```

---

## Phase 2 — Authentication

```text
Register
Login
Logout
Authenticated User
```

---

## Phase 3 — Authorization

```text
Admin
Vendor
Buyer
Middleware
Policies
Redirect
```

---

## Phase 4 — Core Catalog

```text
Categories
Products
Product Images
Store
```

---

## Phase 5 — Buyer

```text
Home
Products
Product Detail
```

---

## Phase 6 — Cart

```text
Add To Cart
Update Quantity
Remove Item
Cart Summary
```

---

## Phase 7 — Checkout

```text
Address
Payment Method
Order Creation
Stock Reduction
Cart Clearing
```

---

## Phase 8 — Orders

```text
Buyer Orders
Order Detail
Order Status
Payment Status
```

---

## Phase 9 — Vendor

```text
Dashboard
Products
Orders
Store
```

---

## Phase 10 — Admin

```text
Dashboard
Users
Vendors
Products
Orders
```

---

## Phase 11 — Final QA

```text
Security
Authorization
Responsive
UI
Performance
Lint
Build
Tests
```

---

# 85. DEFINITION OF DONE

Project MVP dianggap selesai apabila:

```text
[ ] Authentication bekerja
[ ] Role bekerja
[ ] Authorization bekerja
[ ] Buyer flow bekerja
[ ] Product flow bekerja
[ ] Cart bekerja
[ ] Checkout bekerja
[ ] Stock bekerja
[ ] Order bekerja
[ ] Vendor flow bekerja
[ ] Admin flow bekerja
[ ] Supabase bekerja
[ ] Product image upload bekerja
[ ] Responsive UI
[ ] Stitch design implemented
[ ] No critical errors
[ ] ESLint pass
[ ] Build pass
[ ] Tests pass
```

---

# 86. FINAL RULE

AI agent harus selalu mengingat:

```text
DO NOT OVERENGINEER.

DO NOT INVENT FEATURES.

DO NOT IGNORE THE PRD.

DO NOT IGNORE THE STITCH DESIGN.

DO NOT TRUST FRONTEND FOR SECURITY.

DO NOT MODIFY ARCHITECTURE WITHOUT REASON.

DO NOT DELETE USER DATA WITHOUT PERMISSION.

BUILD IN SMALL, TESTABLE STEPS.
```

Tujuan utama adalah membangun aplikasi e-commerce MVP yang:

```text
Clean
Secure
Maintainable
Responsive
Scalable
Consistent
```

dengan Laravel 13 + Inertia + React + Supabase.