# 🛍️ La Moda

> **A full-stack dynamic fashion e-commerce platform** — discover trendy collections, shop seamlessly, and enjoy a smart, personalized online shopping experience.

🌐 **Live Demo:** https://la-moda.onrender.com

---

## 📋 Table of Contents

* [Overview](#overview)
* [Website Preview](#website-preview)
* [Features](#features)
* [User Experience](#user-experience)
* [Admin Dashboard](#admin-dashboard)
* [Tech Stack](#tech-stack)
* [Architecture Highlights](#architecture-highlights)
* [Getting Started](#getting-started)
* [Future Scope](#future-scope)
* [Contributing](#contributing)

---

## Overview

La Moda is a full-stack dynamic fashion e-commerce website designed to deliver a premium online shopping experience.

The platform offers categories such as **Traditional Wear, Casual Wear, Dresses, and Accessories**, with advanced features like real-time search, wishlist, cart persistence, flash sales, coupons, reviews, and a powerful admin dashboard.

It goes beyond a normal shopping website by combining **modern UI, real-world e-commerce logic, user personalization, and business analytics**.

---

## Website Preview

### Home Page
<img width="1600" height="900" alt="Screenshot (38)" src="https://github.com/user-attachments/assets/653a9b26-c6d4-46b1-860c-1286b881d473" />

### Product Browsing
<img width="1600" height="900" alt="Screenshot (40)" src="https://github.com/user-attachments/assets/e1dab5d6-c810-4478-9f2e-5a16ede5c46e" />

### Product Details
<img width="1600" height="900" alt="Screenshot (66)" src="https://github.com/user-attachments/assets/3491ba30-df11-423f-85d1-11d56fb89cb9" />

### Cart
<img width="1600" height="900" alt="Screenshot (76)" src="https://github.com/user-attachments/assets/21dfeee1-1449-4c02-87de-c1b8292d7107" />

### Checkout
<img width="1600" height="900" alt="Screenshot (62)" src="https://github.com/user-attachments/assets/6db7338a-704a-40d4-ae53-0392d8ae8596" />

---

## Features

| Feature                     | Description                                               |
| --------------------------- | --------------------------------------------------------- |
| **Authentication System**   | Secure signup, login/logout with strong password rules   |
| **Live Search**             | Instant product suggestions while typing                 |
| **Wishlist System**         | Save favorite products (login required)                  |
| **Shopping Cart**           | Add, remove, update quantity dynamically                 |
| **Persistent Data**         | Cart & wishlist saved after logout/login                 |
| **Product Details Page**    | Size, pricing, stock, returns, reviews                   |
| **Size Validation**         | Must choose size before adding to cart                   |
| **Flash Sales**             | Countdown timer with limited-time offers                 |
| **Coupon System**           | Available, used, and reward coupons                      |
| **Ratings & Reviews**       | Star ratings with comments                               |
| **Recently Viewed**         | Tracks recently explored products                        |
| **Recommendations**         | “You Might Also Like” smart suggestions                  |
| **Dynamic Cart Count**      | Navbar count updates without refreshing                  |
| **Delivery Fee Logic**      | Smart shipping charges with free-delivery progress       |
| **Multiple Payments**       | COD, UPI, Card, EMI, Net Banking                         |

---

## User Experience

### Home & Discovery

* Elegant homepage with categories and featured collections  
* Flash sale banners with countdown timer  
* Trending and discounted products section  

### Product Browsing

* Browse by category:
  * Traditional Wear
  * Casual Wear
  * Dresses
  * Accessories

* Smooth navigation with responsive design  

### Product Details

* Product gallery and information  
* Old MRP + discounted price  
* Discount percentage display  
* Low stock alerts (5 or fewer items left)  
* 7-day return policy  
* Size chart popup  

### Cart & Checkout

* Quantity controls (+ / -)  
* Delivery charge calculation  
* “Spend ₹X more for free delivery” message  
* Apply coupons during checkout  
* Saved address or add new address  

### Payment Integration

* Cash on Delivery  
* UPI (Cashfree redirect simulation)  
* Credit / Debit Card  
* EMI  
* Net Banking  

---

## Admin Dashboard

### Overview

* Revenue  
* Orders  
* Customers  
* Products  
* Flash Sales  
* Page Visits  

### Sales Analytics

* Sales by Category (Donut Chart)  
* Weekly Sales (Line Graph)  
* Highest Selling Products  
* Waterfall Sales Trend  
* Weekly Order KPI  
* Geo Distribution of Orders  

### Products Management

* View all products  
* Add new products  
* Edit products  
* Manage stock  

### Customer Insights

* Gender Distribution (Pie Chart)  
* Customer Details  

### Advanced Analytics

* Orders Last 15 Days  
* Page Visit Breakdown  
* Most Wishlisted Products  
* Store Radar Performance  

---

## Tech Stack

| Layer      | Technology                     |
| ---------- | ------------------------------ |
| Frontend   | HTML, CSS, JavaScript          |
| Backend    | PHP                            |
| Database   | MongoDB / MySQL               |
| Payments   | Cashfree Payment Gateway       |
| Charts     | Chart.js / ApexCharts         |

---

## Architecture Highlights

* Full-stack dynamic architecture  
* Session-based authentication  
* Database-driven products & users  
* Real-time UI updates without refresh  
* Modular admin analytics dashboard  
* Persistent cart and wishlist system  

---

## Getting Started

### 1. Clone the repository

```bash
git clone https://github.com/yourusername/la-moda.git
cd la-moda
