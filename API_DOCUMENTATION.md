# GoodDeeds API Documentation

## Authentication

The API uses Laravel Sanctum for token-based authentication.

## User Roles

The system supports three user roles:
- **admin**: Platform administrator
- **donor**: Users who donate/give goods
- **beneficiary**: Users who receive goods

## Default Admin Credentials

```
Email: admin@gooddeeds.com
Password: Admin@123
```

## API Endpoints

### Base URL
```
http://localhost:8000/api
```

---

### Health Check
Check if API is running

**Endpoint:** `GET /health`

**Response:**
```json
{
  "success": true,
  "message": "API is running",
  "timestamp": "2025-09-30T08:02:59.356142Z"
}
```

---

### Admin Login
Authenticate admin user and get access token

**Endpoint:** `POST /admin/login`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "email": "admin@gooddeeds.com",
  "password": "Admin@123"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "Admin",
      "email": "admin@gooddeeds.com",
      "role": "admin"
    },
    "token": "1|vVhsbmakL8GYbQRVIZdZDrggIBLdO0wlctnDapry1379e907"
  }
}
```

**Error Response - Invalid Credentials (401):**
```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

**Error Response - Not Admin (403):**
```json
{
  "success": false,
  "message": "Unauthorized. Admin access only."
}
```

---

### Admin Logout
Logout and revoke current access token

**Endpoint:** `POST /admin/logout`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

### Get Current User
Get authenticated user details

**Endpoint:** `GET /admin/me`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "Admin",
      "email": "admin@gooddeeds.com",
      "role": "admin"
    }
  }
}
```

---

## How to Use

### 1. Login
```bash
curl -X POST http://localhost:8000/api/admin/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "admin@gooddeeds.com",
    "password": "Admin@123"
  }'
```

### 2. Use Token for Protected Routes
Save the token from login response and use it in subsequent requests:

```bash
curl -X GET http://localhost:8000/api/admin/me \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### 3. Logout
```bash
curl -X POST http://localhost:8000/api/admin/logout \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## Setup Instructions

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Seed Admin User
```bash
php artisan db:seed
# Or specifically:
php artisan db:seed --class=AdminSeeder
```

### 3. Start Development Server
```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

---

## User Model Helper Methods

The User model includes the following helper methods for role checking:

```php
// Check if user is admin
$user->isAdmin(); // returns boolean

// Check if user is donor
$user->isDonor(); // returns boolean

// Check if user is beneficiary
$user->isBeneficiary(); // returns boolean

// Check if user has specific role
$user->hasRole('admin'); // returns boolean
```

## Role Constants

```php
User::ROLE_ADMIN       // 'admin'
User::ROLE_DONOR       // 'donor'
User::ROLE_BENEFICIARY // 'beneficiary'
```
