Event Booking App

A test event booking application using React + TypeScript + ShadCN for the frontend and Laravel 11 for the backend.
Users can view events, book tickets, and process payments. Organizers and admins can manage events and tickets.

Features

User authentication (register/login/logout)

Public event listing and details

Ticket booking with duplicate booking prevention

Payment processing (with test mode)

Event filters (by title, location, date, upcoming)

Role-based access: user, organizer, admin

Organizer/Admin can manage events and tickets

Dark/light mode support via ShadCN

Tech Stack

Frontend: React, TypeScript, ShadCN, TailwindCSS

Backend: Laravel 11, Sanctum, MySQL

API testing: Postman or curl

Optional: ngrok for public API testing

Setup Instructions
Backend (Laravel)

Install dependencies:

composer install


Copy .env file and configure database:

cp .env.example .env


Generate app key:

php artisan key:generate


Run migrations:

php artisan migrate


Start the Laravel server:

php artisan serve


Backend will run at http://127.0.0.1:8000.

Frontend (React + TypeScript + ShadCN)

Install dependencies:

npm install


Start development server:

npm run dev


Frontend usually runs at http://localhost:3000.

API End Points

| Method | Endpoint        | Description                 |
| ------ | --------------- | --------------------------- |
| POST   | `/api/register` | Register new user           |
| POST   | `/api/login`    | Login user                  |
| POST   | `/api/logout`   | Logout (requires token)     |
| GET    | `/api/me`       | Get authenticated user info |

| Method | Endpoint                                   | Description            |
| ------ | ------------------------------------------ | ---------------------- |
| GET    | `/api/events`                              | List all events        |
| GET    | `/api/events/{id}`                         | View event details     |
| GET    | `/api/events/{eventId}/tickets`            | List tickets for event |
| GET    | `/api/events/{eventId}/tickets/{ticketId}` | View ticket details    |

| Method | Endpoint                     | Description          |
| ------ | ---------------------------- | -------------------- |
| POST   | `/api/bookings/{id}/payment` | Process payment      |
| GET    | `/api/payments/{id}`         | View payment details |

| Method | Endpoint                                   | Description             |
| ------ | ------------------------------------------ | ----------------------- |
| POST   | `/api/events`                              | Create event            |
| PUT    | `/api/events/{id}`                         | Update event            |
| DELETE | `/api/events/{id}`                         | Delete event            |
| GET    | `/api/my-events`                           | List organizer’s events |
| POST   | `/api/events/{eventId}/tickets`            | Create ticket for event |
| PUT    | `/api/events/{eventId}/tickets/{ticketId}` | Update ticket           |
| DELETE | `/api/events/{eventId}/tickets/{ticketId}` | Delete ticket           |


Admin-only routes are prefixed with /api/admin and will manage all resources.

Testing with Postman

Login to get a token.

Add token as Authorization: Bearer <TOKEN> in headers for protected routes.

Test endpoints in order:

View events → select ticket → create booking → process payment → view bookings/payments.

Duplicate booking attempts should return 409 Conflict.

Event Filters

Search by title: /api/events?search=festival

Filter by location: /api/events?location=manila

Filter by date: /api/events?date=2024-12-25

Upcoming events: /api/events?upcoming=true

Combine filters: /api/events?search=concert&location=manila&upcoming=true&per_page=10

Notes

Use Postman or curl to test APIs.

Replace {id} in endpoints with real event, ticket, or booking IDs.

Frontend and backend run on separate ports (frontend 3000, backend 8000).