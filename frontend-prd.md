# FRONTEND PRD
# Multi-Vendor E-Commerce Platform

**Versi:** 1.0.0  
**Status:** MVP  
**Frontend:** React.js  
**Framework:** Inertia.js  
**Backend:** Laravel 13  
**Styling:** Tailwind CSS  
**UI:** shadcn/ui  
**Icons:** Lucide React  
**Form:** React Hook Form + Zod

---

# 1. Tujuan Dokumen

Dokumen ini mendefinisikan standar dan struktur frontend aplikasi multi-vendor e-commerce.

Frontend harus:

- modular
- reusable
- responsive
- mudah dikembangkan
- mudah dipelihara
- mengikuti desain Google Stitch
- terintegrasi dengan Laravel melalui Inertia
- tidak mencampurkan business logic backend ke dalam UI

---

# 2. Frontend Architecture

Arsitektur:

```text
Browser
   ↓
React
   ↓
Inertia.js
   ↓
Laravel
   ↓
PostgreSQL
```

React bertanggung jawab terhadap:

- UI
- interaction
- client-side state
- form interaction
- visual feedback

Laravel bertanggung jawab terhadap:

- business logic
- authorization
- validation
- database
- transaction
- authentication
- stock
- order processing

---

# 3. Frontend Source of Truth

Frontend harus mengikuti:

```text
Google Stitch
      ↓
design-prd.md
      ↓
frontend-prd.md
      ↓
React implementation
```

Jika desain Stitch tersedia, jangan membuat desain baru tanpa alasan.

---

# 4. Folder Structure

Target structure:

```text
resources/
└── js/
    │
    ├── Components/
    │   ├── UI/
    │   ├── Product/
    │   ├── Order/
    │   ├── Cart/
    │   ├── Form/
    │   ├── Dashboard/
    │   └── Common/
    │
    ├── Layouts/
    │   ├── AuthLayout.jsx
    │   ├── BuyerLayout.jsx
    │   ├── VendorLayout.jsx
    │   └── AdminLayout.jsx
    │
    ├── Pages/
    │   ├── Auth/
    │   │   ├── Login.jsx
    │   │   └── Register.jsx
    │   │
    │   ├── Buyer/
    │   │   ├── Home.jsx
    │   │   ├── Products/
    │   │   │   ├── Index.jsx
    │   │   │   └── Show.jsx
    │   │   ├── Cart/
    │   │   │   └── Index.jsx
    │   │   ├── Checkout/
    │   │   │   └── Index.jsx
    │   │   ├── Orders/
    │   │   │   ├── Index.jsx
    │   │   │   └── Show.jsx
    │   │   └── Profile.jsx
    │   │
    │   ├── Vendor/
    │   │   ├── Dashboard.jsx
    │   │   ├── Products/
    │   │   │   ├── Index.jsx
    │   │   │   ├── Create.jsx
    │   │   │   └── Edit.jsx
    │   │   ├── Orders/
    │   │   │   ├── Index.jsx
    │   │   │   └── Show.jsx
    │   │   └── Store.jsx
    │   │
    │   └── Admin/
    │       ├── Dashboard.jsx
    │       ├── Users/
    │       │   └── Index.jsx
    │       ├── Vendors/
    │       │   └── Index.jsx
    │       ├── Products/
    │       │   └── Index.jsx
    │       └── Orders/
    │           ├── Index.jsx
    │           └── Show.jsx
    │
    ├── Hooks/
    │
    ├── Lib/
    │
    ├── Utils/
    │
    └── app.jsx
```

Folder hanya dibuat ketika memang diperlukan.

Jangan membuat puluhan folder kosong sejak awal.

---

# 5. Pages vs Components

`Pages` digunakan untuk halaman yang dirender oleh Inertia.

Contoh:

```text
Pages/Buyer/Products/Index.jsx
```

`Components` digunakan untuk UI reusable.

Contoh:

```text
Components/Product/ProductCard.jsx
```

Jangan memasukkan halaman lengkap ke dalam folder Components.

---

# 6. Layout Architecture

Gunakan layout berdasarkan role:

```text
AuthLayout
BuyerLayout
VendorLayout
AdminLayout
```

Contoh:

```text
Login
 ↓
AuthLayout

Buyer Home
 ↓
BuyerLayout

Vendor Dashboard
 ↓
VendorLayout

Admin Dashboard
 ↓
AdminLayout
```

---

# 7. Shared UI Components

Komponen yang digunakan di banyak halaman harus reusable.

Contoh:

```text
Components/
├── UI/
│   ├── Button
│   ├── Input
│   ├── Card
│   ├── Badge
│   └── Dialog
│
└── Common/
    ├── PageHeader
    ├── EmptyState
    ├── LoadingState
    └── ConfirmDialog
```

Gunakan shadcn/ui untuk primitive component jika tersedia.

---

# 8. Product Components

Product-related components:

```text
Components/Product/
├── ProductCard.jsx
├── ProductGrid.jsx
├── ProductImage.jsx
├── ProductPrice.jsx
└── ProductStatusBadge.jsx
```

Tidak semua komponen harus dibuat langsung.

Buat ketika terdapat kebutuhan reuse.

---

# 9. Order Components

Order-related components:

```text
Components/Order/
├── OrderCard.jsx
├── OrderStatusBadge.jsx
├── PaymentStatusBadge.jsx
├── OrderItem.jsx
└── OrderSummary.jsx
```

---

# 10. Cart Components

```text
Components/Cart/
├── CartItem.jsx
├── CartList.jsx
├── CartSummary.jsx
└── CartEmpty.jsx
```

---

# 11. Form Components

Reusable form components dapat dibuat untuk:

```text
Input
Select
Textarea
FileInput
FormError
```

Namun jangan membuat abstraction terlalu dini.

Jika sebuah input hanya digunakan satu kali, penggunaan langsung diperbolehkan.

---

# 12. Inertia Usage

Frontend menggunakan Inertia untuk komunikasi dengan Laravel.

Contoh konsep:

```text
router.get()
router.post()
router.put()
router.patch()
router.delete()
```

Gunakan Inertia sebagai primary navigation/request mechanism.

---

# 13. No Traditional API Requirement

MVP tidak membutuhkan REST API terpisah untuk frontend.

Jangan membuat:

```text
/api/products
/api/orders
/api/cart
```

hanya untuk komunikasi antara React dan Laravel jika tidak diperlukan.

Gunakan:

```text
Laravel Controller
        ↓
Inertia Response
        ↓
React Page
```

---

# 14. Server Props

Data dari Laravel dikirim melalui Inertia props.

Contoh:

```text
products
categories
user
cart
orders
```

React menerima data tersebut melalui props.

---

# 15. Page Responsibility

Page bertanggung jawab untuk:

- menerima props
- mengatur layout
- menyusun components
- menangani interaction tingkat halaman

Page tidak boleh menjadi tempat seluruh logic aplikasi.

Hindari component seperti:

```text
500+ lines
```

jika logic dapat dipisahkan.

---

# 16. Component Responsibility

Component bertanggung jawab terhadap UI dan interaction yang spesifik.

Contoh:

```text
ProductCard
```

bertanggung jawab terhadap:

- image
- name
- price
- vendor
- action

Tidak bertanggung jawab terhadap seluruh checkout system.

---

# 17. Hooks

Custom hooks digunakan jika terdapat logic frontend yang reusable.

Contoh:

```text
Hooks/
├── useCart.js
├── useDebounce.js
└── useModal.js
```

Namun jangan membuat hook hanya untuk membungkus satu baris kode tanpa manfaat nyata.

---

# 18. State Management

MVP tidak membutuhkan global state library besar seperti Redux kecuali terdapat kebutuhan nyata.

Prioritas:

```text
1. Inertia props
2. React useState
3. React useEffect
4. React Context jika diperlukan
5. Global state library hanya jika benar-benar dibutuhkan
```

Jangan menambahkan Zustand/Redux hanya karena aplikasi menggunakan React.

---

# 19. Server State vs Client State

Data server:

```text
Products
Orders
Users
Vendors
Categories
Cart
```

harus berasal dari Laravel/Inertia.

Client state digunakan untuk:

```text
Modal
Dropdown
Form input
UI toggle
Temporary interaction
```

---

# 20. Authentication State

User authentication dikelola Laravel.

Frontend mendapatkan authenticated user melalui shared Inertia props.

Contoh konsep:

```text
auth.user
```

Jangan menyimpan authentication credential secara manual di localStorage.

---

# 21. Role Handling

Frontend dapat menggunakan role untuk menentukan tampilan navigation.

Contoh:

```text
admin
vendor
buyer
```

Tetapi frontend role check bukan security.

Authorization tetap dilakukan Laravel.

---

# 21.1 Public Registration

Halaman registration menyediakan selector jenis akun:

```text
Buyer
Vendor
```

Admin tidak tersedia sebagai pilihan public registration.

Field registration:

```text
Full name
Email
Phone / WhatsApp
Password
Confirm password
```

Form registration memberikan feedback berikut:

- password strength indicator secara live
- password requirements feedback
- password confirmation feedback

Password strength dan requirements pada frontend hanya memberikan feedback awal. Laravel backend tetap menjadi authority untuk validation dan password security.

Login dapat menampilkan Google dan Apple sebagai visual placeholder. OAuth, Google authentication, dan Apple authentication belum diimplementasikan. Button social login tetap disabled.

---

# 22. Authorization Principle

Jangan mengandalkan:

```text
if user.role === 'admin'
```

sebagai satu-satunya perlindungan.

Backend harus melakukan authorization.

Frontend hanya mengontrol visibility/UX.

---

# 23. Navigation

Navigation harus menggunakan route Laravel/Inertia.

Hindari hardcoded URL jika named route tersedia.

Jika menggunakan Ziggy atau mekanisme route helper, gunakan secara konsisten.

---

# 24. Forms

Form menggunakan:

```text
React Hook Form
+
Zod
```

untuk client-side validation.

Namun validation backend Laravel tetap wajib.

Flow:

```text
User Input
    ↓
React Hook Form
    ↓
Zod
    ↓
Inertia Request
    ↓
Laravel Form Request
    ↓
Database
```

---

# 25. Validation

Frontend validation memberikan feedback cepat.

Backend validation merupakan sumber kebenaran.

Jangan menganggap:

```text
Zod valid
```

berarti data otomatis aman.

Laravel tetap harus memvalidasi semua input.

---

# 26. Form Error

Validation error dari Laravel harus ditampilkan pada field terkait.

Contoh:

```text
Name
[________________]

Product name is required.
```

Jangan hanya menampilkan error di console.

---

# 27. Form Submit

Submit button harus memiliki state:

```text
Normal
Loading
Disabled
Success
Error
```

Ketika request sedang diproses:

```text
Create Product
```

dapat berubah menjadi:

```text
Creating...
```

dan mencegah duplicate submit.

---

# 28. Search

Search product menggunakan server-side search jika data berasal dari database.

Jangan mengambil seluruh product ke browser hanya untuk filtering jika dataset sudah besar.

Flow:

```text
Search Input
     ↓
Inertia Request
     ↓
Laravel Query
     ↓
PostgreSQL
     ↓
Results
```

---

# 29. Pagination

Product, users, vendors, dan orders menggunakan pagination jika dataset memungkinkan menjadi besar.

Pagination berasal dari Laravel.

React hanya merender pagination information.

---

# 30. Filtering

Filtering menggunakan query parameter.

Contoh:

```text
/products?category=electronics
```

atau parameter sesuai kebutuhan.

State filter harus dapat dipertahankan ketika navigation dilakukan jika sesuai UX.

---

# 31. Debouncing

Search input dapat menggunakan debounce untuk menghindari request pada setiap keystroke.

Contoh:

```text
User mengetik:
lap...
lapt...
lapto...
laptop
```

Request tidak harus dikirim untuk setiap karakter.

---

# 32. Product Listing

Buyer product listing harus menyediakan:

```text
Search
Category
Product Grid
Pagination
Empty State
```

sesuai desain Stitch.

---

# 33. Product Detail

Product detail menerima product dari server.

Minimal data:

```text
id
name
description
price
stock
vendor
category
images
```

UI harus menangani product inactive atau unavailable sesuai backend response.

---

# 34. Add To Cart

Flow:

```text
Product Detail
      ↓
Quantity
      ↓
Add To Cart
      ↓
Inertia POST
      ↓
Laravel
      ↓
Cart Updated
```

Frontend tidak boleh langsung memanipulasi database.

---

# 35. Cart

Cart harus menampilkan data server.

Ketika quantity berubah:

```text
Cart
 ↓
Update quantity
 ↓
Laravel
 ↓
Database
 ↓
Updated Cart
```

Jangan menganggap localStorage sebagai source of truth.

---

# 36. Checkout

Checkout harus menggunakan server-side processing.

Frontend mengirim:

```text
address_id
payment_method
```

dan data minimal lain yang memang diperlukan.

Backend menghitung:

```text
subtotal
shipping
total
```

Frontend tidak boleh menentukan final order total.

---

# 37. Order Creation

Setelah checkout berhasil:

```text
Laravel
   ↓
Create Order
   ↓
Create Order Items
   ↓
Reduce Stock
   ↓
Clear Cart
   ↓
Return Success
```

Frontend kemudian melakukan navigation ke order detail/list sesuai flow desain.

---

# 38. Order List

Buyer melihat:

```text
Order Number
Date
Total
Payment Status
Order Status
```

Vendor hanya melihat order item yang terkait vendor.

Admin melihat order platform sesuai authorization.

---

# 39. Vendor Product Management

Vendor dapat:

```text
View Products
Create Product
Edit Product
Change Product Status
```

Jika delete tersedia pada desain, backend tetap harus mencegah kerusakan histori order.

---

# 40. Vendor Order Management

Vendor dapat melihat order yang memiliki product milik vendor tersebut.

Vendor tidak boleh melihat data order vendor lain yang tidak diperlukan.

Query backend harus membatasi data.

---

# 41. Admin Management

Admin dapat melihat:

```text
Users
Vendors
Products
Orders
```

UI admin harus memiliki:

```text
Search
Filter
Pagination
Actions
```

sesuai desain Stitch.

---

# 42. Dashboard

Dashboard menampilkan data berdasarkan role.

### Buyer

Contoh:

```text
Recent Orders
Cart
Quick Actions
```

### Vendor

Contoh:

```text
Products
Orders
Sales
```

### Admin

Contoh:

```text
Users
Vendors
Products
Orders
```

Angka harus berasal dari backend.

Jangan menggunakan dummy statistics pada production flow.

---

# 43. Loading State

Gunakan loading state untuk:

- page navigation jika diperlukan
- form submission
- cart update
- delete action
- checkout

Gunakan skeleton jika desain Stitch menyediakannya.

---

# 44. Empty State

Semua list penting harus memiliki empty state.

Contoh:

```text
No Products
No Orders
No Users
No Vendors
Empty Cart
```

Empty state harus sesuai desain.

---

# 45. Error State

Frontend harus menangani:

```text
Validation Error
Authorization Error
Not Found
Server Error
Network Error
```

Jangan menampilkan raw exception kepada user.

---

# 46. Notifications

MVP hanya membutuhkan feedback sederhana:

```text
Success
Error
Warning
Info
```

Tidak perlu membuat real-time notification system.

---

# 47. Image Upload

Product image upload:

```text
React Form
   ↓
Inertia
   ↓
Laravel
   ↓
Supabase Storage
   ↓
Database path
```

Frontend hanya menangani:

- file selection
- preview
- validation feedback
- upload progress jika tersedia

---

# 48. File Validation

Frontend dapat memeriksa:

```text
File type
File size
Number of files
```

Tetapi backend juga wajib melakukan validasi.

---

# 49. Responsive Frontend

Semua page wajib responsive.

Target:

```text
Mobile
Tablet
Desktop
```

Gunakan Tailwind.

Jangan membuat mobile sebagai tahap terakhir.

---

# 50. Accessibility

Frontend wajib memperhatikan:

```text
Semantic HTML
ARIA bila diperlukan
Keyboard navigation
Focus state
Alt image
Form labels
Contrast
```

---

# 51. Performance

Frontend harus menghindari:

- unnecessary re-render
- giant component
- giant bundle jika tidak diperlukan
- duplicate requests
- loading seluruh dataset sekaligus

Gunakan lazy loading dan code splitting jika memang diperlukan.

Jangan melakukan premature optimization.

---

# 52. Component Reuse

Jika dua halaman memiliki UI yang hampir sama, pertimbangkan reusable component.

Contoh:

```text
OrderStatusBadge
```

dapat digunakan oleh:

```text
Buyer
Vendor
Admin
```

---

# 53. Avoid Over-Abstraction

Jangan membuat:

```text
GenericUniversalComponent
```

yang memiliki terlalu banyak props dan conditional logic.

Component harus memiliki tanggung jawab yang jelas.

---

# 54. Naming Convention

Gunakan:

```text
PascalCase
```

untuk React component.

Contoh:

```text
ProductCard.jsx
OrderSummary.jsx
AdminLayout.jsx
```

Gunakan camelCase untuk variable/function.

Contoh:

```text
product
orderItems
handleSubmit
```

---

# 55. Page Naming

Gunakan struktur:

```text
Pages/
├── Buyer/
│   └── Products/
│       ├── Index.jsx
│       └── Show.jsx
```

`Index` digunakan untuk list.

`Show` digunakan untuk detail.

`Create` digunakan untuk create form.

`Edit` digunakan untuk edit form.

---

# 56. Imports

Import harus rapi dan konsisten.

Prioritaskan:

```text
React
Inertia
UI components
Feature components
Hooks
Utils
```

Hindari import path yang terlalu panjang jika alias tersedia.

---

# 57. Alias

Jika project menggunakan alias:

```text
@/
```

gunakan secara konsisten.

Contoh:

```text
@/Components/Product/ProductCard
@/Layouts/BuyerLayout
@/Pages/Buyer/Home
```

Sesuaikan dengan konfigurasi project aktual.

---

# 58. No Business Logic Duplication

Business logic seperti:

```text
calculate order total
validate stock
determine order authorization
calculate vendor ownership
```

tidak boleh diduplikasi di React.

Backend Laravel adalah source of truth.

---

# 59. No Direct Database Access

React tidak boleh:

```text
connect directly to PostgreSQL
```

React juga tidak boleh menggunakan Supabase database client untuk menggantikan Laravel pada MVP.

Architecture:

```text
React
 ↓
Inertia
 ↓
Laravel
 ↓
Supabase PostgreSQL
```

---

# 60. Supabase Storage Access

Supabase Storage dapat digunakan melalui backend Laravel.

Frontend tidak boleh menyimpan secret Supabase service role key.

---

# 61. Security

Frontend tidak boleh menyimpan:

```text
Database Password
Supabase Service Role Key
Laravel APP_KEY
Private API Secret
```

dalam source code.

---

# 62. Google Stitch Implementation Rule

Sebelum mengimplementasikan page:

```text
1. Cari desain page terkait
2. Baca HTML Stitch
3. Periksa PNG
4. Identifikasi layout
5. Identifikasi reusable component
6. Implementasikan React
7. Hubungkan dengan Inertia
8. Cocokkan visual
```

---

# 63. Stitch HTML Conversion

Stitch HTML harus diterjemahkan.

Contoh:

```text
Stitch HTML
<div class="product-card">
```

menjadi:

```text
<ProductCard />
```

Kemudian style diterapkan menggunakan Tailwind dan reusable UI components.

---

# 64. Design Validation Loop

Setelah page selesai:

```text
Implement
   ↓
Run Application
   ↓
Compare with Stitch
   ↓
Fix Layout
   ↓
Fix Spacing
   ↓
Fix Typography
   ↓
Fix Responsive
   ↓
Final Review
```

---

# 65. Browser Testing

Minimal test pada:

```text
Desktop
Tablet
Mobile
```

dan browser modern.

---

# 66. Frontend Error Policy

Tidak boleh terdapat:

```text
console.error
```

yang berasal dari error aplikasi normal pada production.

Tidak boleh terdapat:

```text
React warning
missing key
invalid nesting
broken import
```

sebelum fitur dianggap selesai.

---

# 67. ESLint

Frontend harus mengikuti ESLint configuration project.

Sebelum feature dianggap selesai:

```text
npm run lint
```

harus berhasil.

---

# 68. Build

Production build harus berhasil:

```text
npm run build
```

Tidak boleh terdapat build-breaking error.

---

# 69. Definition of Done

Frontend feature dianggap selesai jika:

```text
[ ] Sesuai Stitch
[ ] Responsive
[ ] Reusable component
[ ] Inertia integration
[ ] Backend integration
[ ] Validation
[ ] Loading state
[ ] Empty state
[ ] Error state
[ ] Accessible
[ ] No console errors
[ ] ESLint pass
[ ] Build pass
```

---

# 70. Development Rules

Cline harus:

1. membaca `master-prd.md`
2. membaca `architecture-prd.md`
3. membaca `database-prd.md`
4. membaca `design-prd.md`
5. membaca `frontend-prd.md`
6. memeriksa desain Stitch terkait
7. mengimplementasikan satu feature secara lengkap
8. melakukan testing
9. memperbaiki error
10. baru melanjutkan feature berikutnya

---

# 71. Jangan Mengerjakan Semua Sekaligus

Cline tidak boleh langsung membangun:

```text
Admin
+
Vendor
+
Buyer
+
Checkout
+
Orders
```

dalam satu langkah besar.

Gunakan incremental implementation.

Contoh:

```text
Authentication
   ↓
Buyer Product
   ↓
Product Detail
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

# 72. MVP Feature Boundary

Frontend tidak boleh menambahkan:

```text
Wishlist
Reviews
Chat
Coupons
Affiliate
Flash Sale
Loyalty
Subscription
Advanced Analytics
Real-time Notification
```

kecuali diminta secara eksplisit.

---

# 73. Final Frontend Architecture

```text
resources/js/
│
├── Components/
│   ├── UI/
│   ├── Product/
│   ├── Order/
│   ├── Cart/
│   ├── Form/
│   ├── Dashboard/
│   └── Common/
│
├── Layouts/
│   ├── AuthLayout.jsx
│   ├── BuyerLayout.jsx
│   ├── VendorLayout.jsx
│   └── AdminLayout.jsx
│
├── Pages/
│   ├── Auth/
│   ├── Buyer/
│   ├── Vendor/
│   └── Admin/
│
├── Hooks/
├── Lib/
├── Utils/
└── app.jsx
```

---

# 74. Status Dokumen

```text
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
Pending

AGENTS.md:
Pending

Implementation:
Not Started
```

---

# 75. Source of Truth

Untuk frontend:

```text
Google Stitch
      ↓
design-prd.md
      ↓
frontend-prd.md
      ↓
React + Inertia
```

Untuk database:

```text
database-prd.md
      ↓
Laravel Migration
      ↓
Eloquent Models
```

Untuk business logic:

```text
backend-prd.md
      ↓
Laravel Controllers
Form Requests
Policies
Services
```