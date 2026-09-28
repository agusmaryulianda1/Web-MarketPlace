# DESIGN PRD
# Multi-Vendor E-Commerce Platform

**Versi:** 1.0.0  
**Status:** MVP  
**Design Source:** Google Stitch  
**Frontend:** React.js  
**Styling:** Tailwind CSS  
**UI Components:** shadcn/ui  
**Icons:** Lucide React

---

# 1. Tujuan Dokumen

Dokumen ini mendefinisikan aturan visual dan UI aplikasi e-commerce multi-vendor.

Tujuan utama:

- menjaga konsistensi UI
- memastikan implementasi React mengikuti desain Google Stitch
- menjadi referensi Cline saat membangun frontend
- mencegah setiap halaman memiliki style yang berbeda
- menentukan layout berdasarkan role
- menentukan responsive behavior
- menentukan komponen reusable

---

# 2. Design Source of Truth

Desain utama aplikasi dibuat menggunakan:

```text
Google Stitch
```

Hasil desain disimpan di dalam project:

```text
design/
├── admin/
├── vendor/
├── buyer/
└── login/
```

Folder tersebut merupakan referensi visual utama.

Cline wajib memeriksa file desain yang tersedia sebelum mengimplementasikan halaman terkait.

---

# 3. Design Priority

Jika terdapat perbedaan antara implementasi dan desain, gunakan prioritas:

```text
1. Google Stitch Design
2. design-prd.md
3. frontend-prd.md
4. master-prd.md
5. implementation preference
```

Cline tidak boleh mengubah desain secara signifikan tanpa alasan teknis atau instruksi pengguna.

---

# 4. Design Philosophy

Visual aplikasi harus memberikan kesan:

```text
Modern
Professional
Clean
Premium
Simple
Responsive
Trustworthy
```

Karena aplikasi merupakan multi-vendor e-commerce, UI harus membuat pengguna mudah memahami:

- produk
- harga
- vendor
- cart
- checkout
- order
- status transaksi

---

# 5. MVP Design Scope

UI MVP hanya perlu mencakup:

## Authentication

```text
Login
Register
```

## Buyer

```text
Home
Products
Product Detail
Cart
Checkout
Orders
Profile
```

## Vendor

```text
Dashboard
Products
Orders
Store
```

## Admin

```text
Dashboard
Users
Vendors
Products
Orders
```

---

# 6. Role-Based Layout

Aplikasi memiliki tiga dashboard utama:

```text
Admin
Vendor
Buyer
```

Setiap role memiliki layout sendiri.

---

# 7. Authentication Layout

Authentication menggunakan:

```text
AuthLayout
```

Halaman:

```text
Login
Register
```

Layout harus fokus pada authentication.

Hindari menampilkan navigation kompleks pada halaman authentication.

---

# 8. Buyer Layout

Buyer menggunakan:

```text
BuyerLayout
```

Komponen utama:

```text
Header
Navigation
Main Content
Footer
```

Navigation minimal:

```text
Home
Products
Cart
Orders
Profile
```

Desktop dan mobile harus memiliki behavior yang berbeda sesuai desain Stitch.

---

# 9. Vendor Layout

Vendor menggunakan:

```text
VendorLayout
```

Struktur umum:

```text
Sidebar
Header
Main Content
```

Menu:

```text
Dashboard
Products
Orders
Store
```

Sidebar harus responsive.

Pada mobile, sidebar dapat berubah menjadi:

```text
Drawer
```

atau menu mobile sesuai desain Stitch.

---

# 10. Admin Layout

Admin menggunakan:

```text
AdminLayout
```

Struktur:

```text
Sidebar
Header
Main Content
```

Menu:

```text
Dashboard
Users
Vendors
Products
Orders
```

Admin UI harus memberikan kesan:

```text
Professional
Administrative
Data-oriented
Clean
```

---

# 11. Design Folder Mapping

Folder desain harus dipetakan ke halaman aplikasi.

```text
design/
├── admin/
│   ├── dashboard
│   ├── users
│   ├── vendors
│   ├── products
│   └── orders
│
├── vendor/
│   ├── dashboard
│   ├── products
│   ├── orders
│   └── store
│
├── buyer/
│   ├── home
│   ├── products
│   ├── product-detail
│   ├── cart
│   ├── checkout
│   ├── orders
│   └── profile
│
└── login/
    ├── login
    └── register
```

Nama folder/file aktual dapat berbeda.

Cline harus membaca file yang benar-benar tersedia dan memetakan berdasarkan isi desain.

---

# 12. Stitch HTML

Google Stitch dapat menghasilkan HTML.

HTML hasil Stitch digunakan sebagai:

```text
visual reference
```

bukan sebagai kode final aplikasi.

Cline tidak boleh melakukan copy-paste HTML Stitch secara mentah jika struktur tersebut bertentangan dengan arsitektur React.

HTML harus diterjemahkan menjadi:

```text
React components
Tailwind classes
shadcn/ui components
```

---

# 13. Stitch PNG

PNG hasil Stitch digunakan untuk:

```text
visual comparison
```

Cline dapat menggunakan PNG untuk memeriksa:

- spacing
- alignment
- typography
- layout
- card size
- button placement
- sidebar
- header
- responsive structure

Target implementasi harus sedekat mungkin dengan visual Stitch.

---

# 14. Color System

Design harus menggunakan centralized color tokens.

Design utama menggunakan konsep dark premium jika sesuai dengan desain Stitch.

Token awal:

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

Namun jika file Stitch menunjukkan nilai final yang berbeda, nilai dari desain Stitch menjadi referensi visual utama.

---

# 15. Color Usage

## Background

Digunakan untuk:

- page background
- main application background

## Surface

Digunakan untuk:

- card
- panel
- navigation
- form container

## Elevated

Digunakan untuk:

- modal
- dropdown
- elevated card
- hover state

## Primary

Digunakan untuk:

- CTA
- primary button
- active state
- important action
- links tertentu

---

# 16. Text Colors

Gunakan hierarchy.

Contoh:

```text
Primary Text
Secondary Text
Muted Text
Disabled Text
```

Jangan menggunakan terlalu banyak warna text.

Text hierarchy harus konsisten di seluruh aplikasi.

---

# 17. Typography

Typography harus mengikuti desain Stitch.

Gunakan hierarchy:

```text
Display
H1
H2
H3
Body
Small
Caption
```

Heading harus jelas.

Body text harus mudah dibaca.

Jangan menggunakan terlalu banyak jenis font.

---

# 18. Spacing

Gunakan spacing system Tailwind.

Contoh:

```text
p-4
p-6
p-8

gap-4
gap-6
gap-8

space-y-4
space-y-6
```

Jangan menggunakan nilai spacing random jika tidak diperlukan.

---

# 19. Border Radius

Gunakan border radius yang konsisten.

Contoh:

```text
rounded-md
rounded-lg
rounded-xl
```

Nilai final harus mengikuti desain Stitch.

---

# 20. Shadows

Shadow digunakan secara terbatas.

Jangan membuat semua card memiliki shadow besar.

Untuk dark UI, gunakan:

```text
border
surface contrast
subtle shadow
```

untuk memberikan hierarchy.

---

# 21. Buttons

Button memiliki hierarchy:

```text
Primary
Secondary
Outline
Ghost
Destructive
```

Primary button digunakan untuk action utama.

Contoh:

```text
Add to Cart
Checkout
Save
Create Product
Update Product
```

Destructive digunakan untuk:

```text
Delete
Cancel
Remove
```

---

# 22. Button Rules

Button harus:

- memiliki readable text
- memiliki hover state
- memiliki disabled state
- memiliki loading state jika melakukan request
- memiliki focus state
- responsive

Jangan menggunakan button yang hanya berupa icon jika action tersebut tidak jelas.

---

# 23. Forms

Form harus memiliki:

```text
Label
Input
Helper Text
Validation Message
```

Jika diperlukan.

Form menggunakan:

```text
React Hook Form
+
Zod
```

Validation UI harus konsisten.

---

# 24. Input States

Input minimal memiliki:

```text
Default
Focus
Filled
Error
Disabled
```

Error harus terlihat jelas tetapi tetap sesuai design system.

---

# 25. Cards

Card digunakan untuk:

```text
Product
Order
Statistics
Vendor
Store
Dashboard information
```

Card tidak boleh terlalu padat.

Gunakan hierarchy:

```text
Image
Title
Description
Price
Meta
Action
```

sesuai kebutuhan.

---

# 26. Product Card

Product card minimal menampilkan:

```text
Product Image
Product Name
Price
Vendor / Store
Action
```

Jika desain Stitch menampilkan informasi tambahan, ikuti desain tersebut.

Product card harus reusable.

Komponen:

```text
ProductCard
```

---

# 27. Product Detail

Product detail minimal memiliki:

```text
Product Image
Product Name
Price
Description
Stock
Vendor / Store
Quantity
Add to Cart
```

UI harus membedakan informasi utama dengan metadata.

---

# 28. Cart UI

Cart harus menampilkan:

```text
Product
Image
Price
Quantity
Subtotal
Remove
```

Kemudian:

```text
Cart Summary
Subtotal
Shipping
Total
Checkout
```

Jika cart berisi produk dari beberapa vendor, UI harus tetap mudah dipahami.

---

# 29. Checkout UI

Checkout harus fokus pada:

```text
Shipping Address
Order Summary
Payment Method
Total
Place Order
```

Jangan menambahkan field yang tidak dibutuhkan MVP.

---

# 30. Order UI

Order list menampilkan:

```text
Order Number
Date
Total
Payment Status
Order Status
Action
```

Order detail menampilkan:

```text
Order Information
Products
Vendor
Quantity
Price
Subtotal
Shipping Address
Payment Method
Payment Status
Order Status
```

---

# 31. Dashboard Cards

Dashboard dapat menggunakan reusable statistic card.

Contoh:

```text
Total Orders
Total Products
Total Revenue
Total Users
```

Namun data yang ditampilkan harus sesuai role.

Jangan menampilkan data yang tidak boleh diakses oleh role tersebut.

---

# 32. Tables

Admin dan vendor membutuhkan table untuk:

```text
Users
Vendors
Products
Orders
```

Table harus mendukung:

```text
Responsive
Loading
Empty State
Error State
Pagination
```

Jika mobile tidak cocok menggunakan table penuh, gunakan card/list layout.

---

# 33. Sidebar

Sidebar harus:

- memiliki active state
- memiliki icon
- memiliki label
- memiliki hover state
- memiliki responsive behavior
- memiliki logout action

Gunakan:

```text
Lucide React
```

untuk icons.

Jangan menggunakan emoji sebagai icon UI utama.

---

# 34. Header

Header minimal dapat berisi:

```text
Logo / Brand
Page Context
Notification area jika diperlukan
User Menu
```

Jangan menambahkan notification system kompleks karena belum termasuk MVP.

---

# 35. Navigation Active State

Menu aktif harus terlihat jelas.

Contoh:

```text
Dashboard
```

ketika berada pada dashboard harus memiliki:

```text
active background
active text
active icon
```

sesuai desain Stitch.

---

# 36. Modal

Modal digunakan untuk action sederhana seperti:

```text
Confirm Delete
Confirm Action
Simple Form
```

Gunakan shadcn/ui jika tersedia.

Jangan membuat modal custom berulang kali.

---

# 37. Dropdown

Dropdown digunakan untuk:

```text
User Menu
Actions
Filters
Selection
```

Gunakan komponen reusable.

---

# 38. Toast

Toast dapat digunakan untuk feedback:

```text
Product created successfully
Product updated successfully
Product deleted successfully
Order created successfully
```

Error:

```text
Something went wrong
```

Feedback harus singkat.

---

# 39. Loading State

Setiap request asynchronous harus memiliki loading state jika diperlukan.

Contoh:

```text
Loading...
Skeleton
Disabled button
Spinner
```

Jangan membuat halaman terlihat blank ketika data sedang dimuat.

---

# 40. Empty State

Jika tidak ada data:

```text
No products found
No orders found
No users found
Your cart is empty
```

Empty state harus menyediakan action jika relevan.

Contoh:

```text
Your cart is empty

[Continue Shopping]
```

---

# 41. Error State

Jika request gagal:

```text
Something went wrong
```

Tampilkan action:

```text
Try Again
```

jika memungkinkan.

Error tidak boleh hanya muncul di console browser.

---

# 42. Responsive Design

Aplikasi wajib responsive:

```text
Mobile
Tablet
Desktop
Large Desktop
```

Gunakan Tailwind responsive utilities.

Contoh:

```text
sm:
md:
lg:
xl:
2xl:
```

Jangan menganggap desktop sebagai satu-satunya layout.

---

# 43. Mobile Priority

Mobile layout harus dipikirkan sejak awal.

Contoh:

Desktop:

```text
Sidebar | Content
```

Mobile:

```text
Header
Content
Bottom navigation / drawer
```

Gunakan desain Stitch sebagai referensi final.

---

# 44. Image Rules

Product images harus:

- memiliki aspect ratio konsisten
- tidak terdistorsi
- menggunakan object-fit
- memiliki fallback jika gambar gagal
- lazy loading jika relevan

Contoh:

```text
object-cover
```

atau:

```text
object-contain
```

sesuai jenis gambar.

---

# 45. Accessibility

UI harus mempertimbangkan:

- semantic HTML
- label form
- keyboard navigation
- focus state
- accessible buttons
- alt text image
- readable contrast

Icon-only button harus memiliki accessible label.

---

# 46. Icons

Library:

```text
Lucide React
```

Gunakan icon secara konsisten.

Contoh:

```text
Home
ShoppingCart
Package
Users
Store
Settings
LogOut
Search
Plus
Edit
Trash
```

Jangan mencampur banyak icon library.

---

# 47. shadcn/ui

shadcn/ui digunakan sebagai dasar reusable UI.

Komponen yang dapat digunakan:

```text
Button
Input
Label
Card
Dialog
Dropdown Menu
Table
Select
Badge
Alert
Toast
Skeleton
```

Komponen hanya ditambahkan ketika benar-benar dibutuhkan.

Jangan menginstall semua komponen sekaligus tanpa kebutuhan.

---

# 48. Reusable Components

Komponen umum harus dibuat reusable.

Contoh:

```text
Button
Input
Modal
Badge
ProductCard
OrderStatusBadge
EmptyState
LoadingState
PageHeader
StatCard
DataTable
```

Hindari duplikasi UI antar halaman.

---

# 49. Product Status Badge

Status product:

```text
active
inactive
```

Badge harus memiliki visual berbeda.

---

# 50. Order Status Badge

Status:

```text
pending
processing
shipped
completed
cancelled
```

Gunakan visual hierarchy yang jelas.

---

# 51. Payment Status Badge

Status:

```text
pending
paid
failed
```

Harus mudah dibedakan dari order status.

---

# 52. Vendor UI

Vendor hanya boleh melihat data yang berkaitan dengan vendor tersebut.

UI vendor tidak boleh menampilkan:

```text
Other vendor products
Other vendor orders
Admin-only information
```

Frontend visibility bukan security layer.

Authorization tetap dilakukan backend.

---

# 53. Admin UI

Admin dapat melihat data platform sesuai authorization.

Admin navigation:

```text
Dashboard
Users
Vendors
Products
Orders
```

UI admin harus memprioritaskan:

```text
overview
management
search
filter
action
```

---

# 54. Buyer UI

Buyer navigation:

```text
Home
Products
Cart
Orders
Profile
```

Buyer experience harus memprioritaskan:

```text
discover product
view product
add to cart
checkout
track order
```

---

# 55. Design Consistency

Semua halaman harus menggunakan:

- color system yang sama
- typography system yang sama
- spacing system yang sama
- button style yang sama
- input style yang sama
- border radius yang sama
- icon system yang sama

Jangan membuat style baru pada setiap halaman.

---

# 56. Design Validation

Setiap halaman yang selesai harus dibandingkan dengan desain Stitch.

Checklist:

```text
[ ] Layout sesuai
[ ] Spacing sesuai
[ ] Typography sesuai
[ ] Color sesuai
[ ] Button sesuai
[ ] Card sesuai
[ ] Icon sesuai
[ ] Image ratio sesuai
[ ] Responsive sesuai
[ ] Hover state sesuai
[ ] Loading state tersedia
[ ] Empty state tersedia
[ ] Error state tersedia
```

---

# 57. Cline Design Rules

Ketika mengimplementasikan UI, Cline wajib:

1. membaca `design-prd.md`
2. membaca PRD terkait
3. membaca desain Stitch yang relevan
4. memahami struktur HTML Stitch
5. melihat PNG jika tersedia
6. menerjemahkan desain menjadi React
7. menggunakan Tailwind
8. menggunakan shadcn/ui jika sesuai
9. menggunakan Lucide React
10. membuat component reusable
11. menjaga responsive behavior

---

# 58. Jangan Copy-Paste Stitch Secara Mentah

Kode HTML dari Stitch bukan architecture aplikasi final.

Jangan:

```text
copy HTML
↓
paste ke React
```

Secara langsung.

Gunakan:

```text
Stitch
 ↓
Analyze
 ↓
Break into components
 ↓
React Components
 ↓
Tailwind
 ↓
Reusable UI
```

---

# 59. Design vs Backend Logic

Design tidak boleh menentukan business logic.

Contoh:

Stitch mungkin menampilkan:

```text
Add to Cart
```

Tetapi logic:

```text
check stock
check authentication
validate product
create cart item
```

tetap menjadi tanggung jawab Laravel backend.

---

# 60. No Fake Functionality

Cline tidak boleh membuat UI seolah-olah fitur sudah bekerja jika backend belum tersedia.

Contoh buruk:

```text
[Checkout]
```

tetapi hanya melakukan:

```text
console.log()
```

Implementasi harus menggunakan flow aplikasi yang sebenarnya.

Jika backend belum dibuat, gunakan placeholder hanya pada tahap UI development dan tandai dengan jelas.

---

# 61. No Unnecessary Features

Jangan menambahkan UI untuk:

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
```

karena belum termasuk MVP.

---

# 62. Design Folder Rule

Folder:

```text
design/
```

tidak boleh dianggap sebagai source code production.

Folder tersebut merupakan:

```text
Design Reference
```

Production UI berada di:

```text
resources/js/
```

---

# 63. Final Design Architecture

Target frontend:

```text
resources/js/
│
├── Components/
│   ├── UI/
│   ├── Product/
│   ├── Order/
│   ├── Form/
│   └── Layout/
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
│
├── Lib/
│
└── app.jsx
```

---

# 64. Definition of Done

Design implementation dianggap selesai apabila:

```text
[ ] Sesuai desain Stitch
[ ] Responsive
[ ] Reusable components
[ ] Consistent design system
[ ] Accessible
[ ] Loading state
[ ] Empty state
[ ] Error state
[ ] Hover state
[ ] Disabled state
[ ] Tidak ada UI palsu
[ ] Tidak ada fitur di luar MVP
```

---

# 65. Status Dokumen

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
Pending

Backend PRD:
Pending

AGENTS.md:
Pending

Implementation:
Not Started
```

---

# 66. Source of Truth

Untuk implementasi frontend:

```text
Google Stitch
      ↓
design-prd.md
      ↓
frontend-prd.md
      ↓
React Components
```

Cline harus mengikuti alur tersebut.