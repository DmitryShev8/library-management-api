# Library Management API

A RESTful API for managing library operations, built with Laravel 13. The application provides book and category management, borrowing and returning workflows, Sanctum authentication, and Role-Based Access Control (RBAC).

## Features

- **Authentication:** User registration, login, and logout using Laravel Sanctum.
- **Role-Based Access Control:** Three roles with different permissions: Admin, Staff, and Member.
- **Category Management:** Create, retrieve, update, and delete book categories.
- **Book Management:** Create, retrieve, update, and delete books.
- **Borrowing Management:** Borrow books, return books, and view borrowing history.
- **Stock Management:** Automatically adjusts book stock during borrowing and returning.
- **Borrowing Validation:** Prevents borrowing unavailable books, invalid due dates, and duplicate returns.
- **Pagination & Filtering:** Paginated borrowing history with status-based filtering.
- **Data Integrity:** Database transactions, row locking, foreign key constraints, and request validation.
- **Automated Testing:** Feature tests covering authentication, authorization, books, and borrowing workflows.

## Tech Stack

| Technology | Purpose |
|---|---|
| PHP 8.5 | Backend programming language |
| Laravel 13 | Backend framework |
| MySQL | Relational database |
| Laravel Sanctum | API authentication |
| PHPUnit 12 | Automated testing |
| Composer | Dependency management |

## User Roles & Permissions

| Feature | Admin | Staff | Member | Guest |
|---|:---:|:---:|:---:|:---:|
| View books | ✓ | ✓ | ✓ | ✓ |
| View categories | ✓ | ✓ | ✓ | ✓ |
| Manage categories | ✓ | - | - | - |
| Create books | ✓ | ✓ | - | - |
| Update books | ✓ | ✓ | - | - |
| Delete books | ✓ | - | - | - |
| View borrowing history | All | All | Own | - |
| Borrow books | - | - | ✓ | - |
| Return books | - | - | Own | - |

## API Endpoints

Base URL: `/api`

### Authentication

| Method | Endpoint | Access | Description |
|---|---|---|---|
| POST | `/register` | Public | Register a new member |
| POST | `/login` | Public | Authenticate user |
| POST | `/logout` | Authenticated | Revoke current access token |

### Categories

| Method | Endpoint | Access | Description |
|---|---|---|---|
| GET | `/categories` | Public | Retrieve all categories |
| GET | `/categories/{id}` | Public | Retrieve category details |
| POST | `/categories` | Admin | Create category |
| PUT/PATCH | `/categories/{id}` | Admin | Update category |
| DELETE | `/categories/{id}` | Admin | Delete category |

### Books

| Method | Endpoint | Access | Description |
|---|---|---|---|
| GET | `/books` | Public | Retrieve all books |
| GET | `/books/{id}` | Public | Retrieve book details |
| POST | `/books` | Admin, Staff | Create book |
| PUT/PATCH | `/books/{id}` | Admin, Staff | Update book |
| DELETE | `/books/{id}` | Admin | Delete book |

### Borrowings

| Method | Endpoint | Access | Description |
|---|---|---|---|
| GET | `/borrowings` | Authenticated | Retrieve borrowing history |
| GET | `/borrowings/{id}` | Authenticated | Retrieve borrowing details |
| POST | `/borrowings` | Member | Borrow a book |
| PUT | `/borrowings/{id}/return` | Member | Return a borrowed book |

Borrowing history supports pagination and status filtering:

`GET /api/borrowings?status=borrowed`

Available status values:
- `borrowed`
- `returned`

Members can only access their own borrowing records. Admin and Staff can access all borrowing records.

## Business Logic & Data Integrity

### Borrowing Workflow

1. A member submits a borrowing request.
2. The system validates the book and due date.
3. A database transaction locks the selected book record.
4. The system checks stock availability.
5. Book stock is decreased by one.
6. A borrowing record is created.

### Returning Workflow

1. A member submits a return request.
2. The system verifies borrowing ownership.
3. A database transaction locks the borrowing and book records.
4. The system checks whether the book has already been returned.
5. The borrowing status and return timestamp are updated.
6. Book stock is increased by one.

Database transactions and row-level locking help maintain stock consistency during concurrent borrowing requests.

## Installation

### Prerequisites

- PHP 8.3 or higher
- Composer
- MySQL
- Required PHP extensions for Laravel

### 1. Clone the Repository

```bash
git clone <repository-url>
cd library-management-api
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Configure Environment

Copy the example environment file:

```bash
cp .env.example .env
```

On Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

### 4. Configure MySQL

Create a MySQL database named:

```text
library_management
```

Update the database configuration in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=library_management
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Configure Admin and Staff Accounts

Configure the credentials used by the database seeders in `.env`:

```env
ADMIN_NAME="System Admin"
ADMIN_EMAIL=admin@library.local
ADMIN_PASSWORD=your_admin_password

STAFF_NAME="Library Staff"
STAFF_EMAIL=staff@library.local
STAFF_PASSWORD=your_staff_password
```

Use your own secure passwords.

### 6. Run Migrations and Seeders

```bash
php artisan migrate --seed
```

This creates the required database tables and initial records, including categories, books, and configured user accounts.

### 7. Start the Development Server

```bash
php artisan serve
```

The API will be available at:

```text
http://127.0.0.1:8000/api
```

## Authentication

Protected endpoints require a Bearer Token obtained through the login endpoint.

Example request header:

```http
Accept: application/json
Authorization: Bearer YOUR_ACCESS_TOKEN
```

The registration endpoint creates a Member account by default. Users cannot assign themselves elevated roles through public registration.

## Automated Testing

The project includes feature tests for:

- User registration and login.
- Role-based authorization.
- Book validation and management.
- Borrowing and returning workflows.
- Stock consistency.
- Borrowing ownership and access restrictions.
- Pagination and status filtering.

Run the test suite:

```bash
php artisan test
```

The current test suite contains **29 passing tests**.

Tests use a separate MySQL testing database configured in `phpunit.xml` to isolate test data from the development database.

## Security Considerations

- Passwords are hashed before storage.
- Authentication uses Laravel Sanctum personal access tokens.
- Role permissions are enforced through middleware.
- Members cannot access other members' borrowing records.
- Sensitive environment variables are stored in `.env`.
- Database constraints protect relational integrity.

## License

This project is licensed under the MIT License.