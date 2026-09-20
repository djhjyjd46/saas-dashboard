# AdAnalytics SaaS Dashboard

> **B2B Marketing & Ad Spend Intelligence Platform** built on **Laravel 10 / PHP 8.2+**, **Vite**, and **Tailwind CSS**. Integrates **Yandex.Direct API** and **amoCRM** for end-to-end multi-account unit economics and ROI tracking.

[![Laravel](https://img.shields.io/badge/Laravel-10.x-FF2D20.svg?logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4.svg?logo=php)](https://php.net)
[![Tailwind CSS](https://img.shields.io/badge/TailwindCSS-3.x-38B2AC.svg?logo=tailwind-css)](https://tailwindcss.com)
[![Vite](https://img.shields.io/badge/Vite-5.x-646CFF.svg?logo=vite)](https://vitejs.dev)
[![License](https://img.shields.io/badge/License-Proprietary-red.svg)](#)

---

## 🎯 Platform Overview

AdAnalytics solves the fragmentation between ad spend and sales revenue. Instead of manually stitching spreadsheets across advertising accounts and CRM pipelines, AdAnalytics provides automated end-to-end attribution:

```
┌──────────────────────────┐          ┌──────────────────────────┐
│    Yandex.Direct API     │          │        amoCRM API        │
│  (Clicks, Spend, CPC)    │          │  (Leads, Deals, Revenue) │
└─────────────┬────────────┘          └────────────┬─────────────┘
              │                                    │
              └──────────────► ┌─────────────────┐ ◄┘
                               │   AdAnalytics   │
                               │  SaaS Engine    │
                               │  (Laravel/SQL)  │
                               └────────┬────────┘
                                        │
             ┌──────────────────────────┼──────────────────────────┐
             ▼                          ▼                          ▼
   Executive Dashboard           Media Buyer Desk          Client Portal
 (ROMI, Profit, CAC, CPL)    (Campaign optimization)   (Transparent reporting)
```

---

## 🚀 Key Features

- **Automated Ad Spend Ingestion:** Direct integration with Yandex.Direct API to fetch live impressions, clicks, daily expenditure, and UTM parameters.
- **Two-Way CRM Attribution:** Connects deals and contact pipelines from amoCRM, automatically matching ad campaigns with closed deals.
- **Unit Economics Engine:** Computes real-time business metrics:
  - **CPL** (Cost per Lead)
  - **CAC** (Customer Acquisition Cost)
  - **ROMI / ROI** (Return on Marketing Investment)
  - **Conversion Rates** across every funnel stage.
- **Role-Based Access Control (RBAC):**
  - **Superadmin / Agency Owner:** Global view across all client ad accounts and profit margins.
  - **Media Buyer:** Granular access restricted to assigned client campaigns.
  - **Client:** Read-only executive dashboard with transparent campaign performance.
- **One-Click Deployments:** Automated production deployment pipelines (`deploy.ps1`, `deploy_server.sh`).

---

## 🏗️ Tech Stack

- **Backend:** Laravel 10.x, PHP 8.2+, Eloquent ORM, MySQL.
- **Frontend:** Vite, Blade templates, Tailwind CSS, Alpine.js, Chart.js.
- **APIs:** Yandex.Direct v5 REST API, amoCRM OAuth 2.0 API.
- **DevOps:** SSH/PowerShell automated deployment workflows.

---

## 📦 Local Development Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/djhjyjd46/saas-dashboard.git
   cd saas-dashboard
   ```

2. **Install dependencies:**
   ```bash
   composer install
   npm install
   ```

3. **Configure environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Set up database and run migrations:**
   ```bash
   php artisan migrate --seed
   ```

5. **Start development server:**
   ```bash
   npm run dev
   php artisan serve
   ```

---
*Developed by [Egor Voronov](https://github.com/djhjyjd46)*
