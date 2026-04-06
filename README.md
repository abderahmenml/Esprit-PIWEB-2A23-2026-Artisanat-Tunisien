# حرفة Tunisie — Module 5: Offres d’emploi

A complete frontend-only Job Offers System for artisan projects, built with:

- HTML5
- CSS3
- Vanilla JavaScript

This module is designed for **artisans** and **recruiters** to post offers, apply, and manage application statuses in a modern, warm, artisan-inspired interface.

---

## Table of Contents

1. [Overview](#overview)
2. [Features](#features)
3. [Design System](#design-system)
4. [Project Structure](#project-structure)
5. [Pages](#pages)
6. [Data Model (LocalStorage)](#data-model-localstorage)
7. [How to Run](#how-to-run)
8. [How to Use](#how-to-use)
9. [UX/UI Enhancements Included](#uxui-enhancements-included)
10. [Customization Guide](#customization-guide)
11. [Troubleshooting](#troubleshooting)
12. [Notes](#notes)

---

## Overview

This project implements **Module 5: Offres d’emploi** for the platform **"حرفة Tunisie"**.

It includes:

- Job listing and filtering
- Job details
- Job creation form with live preview
- Application submission
- Recruiter dashboard for accept/reject workflow
- Artisan application tracking timeline

The module is fully client-side and stores data in `localStorage`.

---

## Features

### Core Functional

- Switch active user role (recruiter/artisan)
- Publish job offers (recruiter only)
- Browse and search/filter offers
- Apply to jobs (artisan only)
- Prevent duplicate applications
- Recruiter accepts/rejects applications
- Artisan views status progression

### UI/UX

- Premium card-based layout
- Soft shadows and rounded corners
- Warm artisan-inspired palette
- Berber geometric subtle pattern background
- Smooth transitions and hover effects
- Status badges and skill pills
- KPI summaries and dashboard visuals

---

## Design System

### Colors

- Primary Marron: `#8B5A3A`
- Forest Green: `#2E6B3E`
- Warm Caramel: `#C49A6C`
- Cream Background: `#F5ECD7`
- Dark Brown Text: `#3B2314`

### Typography

- Titles: **Georgia**
- Body: **Calibri**

### Style Direction

- Minimal, warm, artisan-inspired
- Not rigid / not blocky
- Rounded corners (`12px–18px`)
- Organic gradients and soft depth

---

## Project Structure

```text
project web fav/
├─ index.html
├─ job_details.html
├─ create_job.html
├─ apply.html
├─ dashboard.html
├─ my_applications.html
├─ login_register.html
├─ module.css
├─ login_register.css
├─ script.js
├─ logo.jpg
├─ style.css
└─ README.md
```

### Important Notes

- Main module pages use: `module.css` + `script.js`
- Login/Register page uses: `login_register.css`
- `style.css` may exist as legacy and is not required by module pages

---

## Pages

### 1) Job Offers — `index.html`

- Search by text
- Filter by skills and budget
- Premium job cards with:
  - budget highlight
  - skill tags
  - location + duration hints
  - optional badges (New / Urgent)
- KPI counters for jobs, artisans, applications

### 2) Job Details — `job_details.html`

- Full offer detail
- Skills as pill tags
- Budget, location, duration
- Apply action CTA

### 3) Publish Job — `create_job.html`

- Structured sections:
  - Job Info
  - Skills
  - Budget & Duration
- Iconized inputs
- **Live preview card** updated while typing

### 4) Apply — `apply.html`

- Job context summary
- Message form
- Validation and success/error feedback

### 5) Recruiter Dashboard — `dashboard.html`

- Recruiter-only view
- Applicant cards with:
  - avatar initials
  - message preview
  - skill pills
- Action buttons:
  - Accept (green)
  - Reject (dark brown)
- Status badges + KPI summary

### 6) My Applications — `my_applications.html`

- Artisan-only view
- Application cards with status badge
- Timeline style progression:
  - Postulé
  - En revue
  - Décision (Accepté / Refusé)

---

## Data Model (LocalStorage)

The app uses key:

- `harfa-module5-data-v1`

Stored object structure:

```js
{
  currentUserId: number,
  users: [{ id, name, role }],
  jobs: [{
    id,
    title,
    description,
    skills,
    budget,
    duration,
    location,
    user_id,
    created_at
  }],
  applications: [{
    id,
    job_id,
    user_id,
    message,
    status, // pending | accepted | rejected
    created_at
  }]
}
```

### Seeded Users

- Recruiters:
  - Amine Ben Salem
  - Salma Gharbi
- Artisans:
  - Nour Hmidi
  - Youssef Trabelsi

---

## How to Run

### Option A: Open directly

- Double-click `index.html`

### Option B (recommended): Local server

Use any static server to avoid browser restrictions:

- VS Code Live Server
- `python -m http.server`
- `npx serve`

Then open `index.html` from the served URL.

---

## How to Use

1. Open `index.html`
2. Choose active user from the top selector
3. As recruiter:
   - go to **Publier**
   - create offers
   - manage applications in **Dashboard**
4. As artisan:
   - browse offers
   - apply to jobs
   - track results in **Mes candidatures**

---

## UX/UI Enhancements Included

- Fluid hover animations (lift + shadow)
- Soft premium gradients
- Rounded skill and status pills
- Strong visual hierarchy
- Breathing space and card rhythm
- Nav active state highlighting
- Reveal-on-scroll animation
- Professional section strips and footers

---

## Customization Guide

### Change theme colors

Edit CSS variables at top of `module.css`:

- `--marron`
- `--vert`
- `--caramel`
- `--creme`
- `--brun`

### Change seed data

Edit the `seed` object near top of `script.js`.

### Reset app data

In browser console:

```js
localStorage.removeItem('harfa-module5-data-v1')
location.reload()
```

---

## Troubleshooting

### UI looks outdated

- Hard refresh: `Ctrl + F5`
- Confirm pages link to `module.css`

### Data seems wrong

- Clear localStorage key and reload

### Buttons don’t work

- Ensure `script.js` is loaded with `defer`
- Check console for JS errors

---

## Notes

- This module is currently frontend-only (no PHP/MySQL runtime).
- It is ready for future backend integration by replacing `localStorage` operations with API calls.
- `login_register.html` remains separate and styled with `login_register.css`.

---

## Authoring Intent

The module aims to deliver a modern, emotionally warm experience that reflects Tunisian craftsmanship while maintaining clean professional usability for both artisans and recruiters.
