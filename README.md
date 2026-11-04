Event Decoration Management System

> **Status: Planned / Not yet implemented.** This repository is intended to become a responsive event-decoration booking and management application.

Overview

Event decoration management will help customers explore decoration packages and request events, while administrators manage booking approval, inventory, staff allocation, invoices and payments.

Planned stack

• Frontend: React, Vite, HTML5, CSS3, JavaScript, React Router, optional Tailwind CSS.
• Backend: PHP, Laravel REST API, Laravel Sanctum, validation and authorization policies.
• Database: MySQL 8+ using relational tables and foreign-key constraints.
• Deployment: HTTPS-enabled hosting, separate frontend/API configuration, automated backups.

Planned features

Customer: account, package catalog, event request, booking status, payments, invoices, reviews.

Admin: dashboard, packages/categories, quotations, booking calendar, venue, staff, inventory, add-ons, invoices, payment reconciliation and reports.

Staff (later phase): assigned jobs and progress updates.

Project status and roadmap

1. Define requirements, wireframes and ER diagram.
2. Set up frontend and backend repositories and MySQL schema.
3. Implement accounts, roles and package catalog.
4. Implement booking requests, scheduling and admin review.
5. Add stock, staff, invoice and payment workflows.
6. Test, secure, deploy and document.

Proposed directory structure

Event Decoration
├── frontend/                  # React + Vite SPA (planned)
│   └── src/
│       ├── components/
│       ├── pages/
│       ├── routes/
│       └── services/
├── backend/                   # Laravel REST API (planned)
│   ├── app/Http/Controllers/
│   ├── app/Models/
│   ├── database/migrations/
│   └── routes/api.php
├── docs/
│   ├── Event_Decoration_Project_Plan.docx
│   └── Event_Decoration _ER_Diagram.png
└── README.md

Database design

14 proposed tables: users, customers, staff, categories, packages, package_items, inventory, venues, bookings, booking_staff, payments, invoices, reviews, booking_addons.

Primary and foreign keys: Every entity has a primary key. customers.user_id and staff.user_id reference users.user_id. packages.category_id references categories.category_id. bookings references customer, package and optional venue. payments, invoices, reviews, booking_staff and booking_addons reference bookings. package_items joins packages and inventory; booking_staff joins bookings and staff. See the project plan and ER diagram for cardinalities and full field lists.

Planned API (illustrative)

|Method|Endpoint                           |Use                        |
|------|-----------------------------------|---------------------------|
|POST  |`/api/register`                    |Create customer account    |
|POST  |`/api/login`                       |Sign in                    |
|GET   |`/api/packages`                    |List packages              |
|POST  |`/api/bookings`                    |Request booking            |
|GET   |`/api/my/bookings`                 |Customer booking list      |
|PATCH |`/api/admin/bookings/{id}`         |Update booking status      |
|POST  |`/api/bookings/{id}/payment-intent`|Start payment              |
|POST  |`/api/webhooks/payments`           |Verify gateway notification|

Setup instructions (after implementation)

The application code does not exist yet. Once the project is scaffolded, typical local prerequisites will include PHP/Composer, Node.js/npm and MySQL. The team will then configure .env files, install dependencies, create the database, run Laravel migrations, start the API and Vite development servers. Exact commands will be added after the stack is initialized and tested.

Payment and security considerations

Payment provider integration is planned, not active. Verify signed server-to-server callbacks, use idempotency and never store card details. Apply server-side validation, access control, HTTPS, safe uploads and database backups.

Definition of done for MVP

A customer can submit a booking request; an admin can manage approval and allocate resources; invoices and verified payment records are visible only to authorized users; essential user journeys pass testing.

Documentation

• Event_Decoration _Project_Plan.docx — complete planning and presentation document.
•Event_Decoration _ER_Diagram.png — proposed database ER diagram.

License

To be decided before public distribution.
