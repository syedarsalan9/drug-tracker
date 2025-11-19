# Drug Search and Tracker API

A Laravel-based REST API for searching drug information and managing user medication lists. Integrates with the National Library of Medicine's RxNorm APIs.

## Features

- User authentication (Register/Login/Logout)
- Public drug search endpoint
- Private user medication management
- Rate limiting on public endpoints
- Response caching for better performance
- Comprehensive error handling
- 90%+ test coverage
- RESTful API design

## Tech Stack

- Laravel 10.x
- MySQL/PostgreSQL
- Laravel Sanctum (Authentication)
- Guzzle HTTP Client
- PHPUnit (Testing)

## Installation

### Prerequisites
- PHP 8.1+
- Composer
- MySQL/PostgreSQL
- Git

### Setup Steps

1. **Clone the repository**
```bash
git clone https://github.com/syedarsalan9/drug-tracker.git
cd drug-tracker
```

2. **Install dependencies**
```bash
composer install
```

3. **Environment configuration**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configure database in `.env`**
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=drug_mgmt_system
DB_USERNAME=root
DB_PASSWORD=
```

5. **Run migrations**
```bash
php artisan migrate
```

6. **Start the server**
```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

## API Documentation

### Base URL
```
http://localhost:8000/api
```

### Authentication
Most endpoints require Bearer token authentication. Include in headers:
```
Authorization: Bearer {your_token}
```

---

### 1. User Registration

**Endpoint:** `POST /api/register`

**Request Body:**
```json
{
    "name": "Syed Arsalan",
    "email": "syedarslanahmed99@gmail.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Response (201):**
```json
{
    "success": true,
    "message": "Registration successful",
    "data": {
        "user": {
            "id": 1,
            "name": "Syed Arsalan",
            "email": "syedarslanahmed99@gmail.com"
        },
        "access_token": "1|abc123...",
        "token_type": "Bearer"
    }
}
```

---

### 2. User Login

**Endpoint:** `POST /api/login`

**Request Body:**
```json
{
    "email": "syedarslanahmed99@gmail.com",
    "password": "password123"
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "syedarslanahmed99@gmail.com"
        },
        "access_token": "2|xyz789...",
        "token_type": "Bearer"
    }
}
```

---

### 3. Search Drugs (Public)

**Endpoint:** `GET /api/drugs/search?drug_name={query}`

**Rate Limit:** 30 requests per minute

**Example Request:**
```
GET /api/drugs/search?drug_name=aspirin
```

**Response (200):**
```json
{
    "success": true,
    "message": "Drugs found successfully",
    "data": [
        {
            "rxcui": "243670",
            "name": "Aspirin 81 MG Oral Tablet",
            "base_names": ["Aspirin"],
            "dosage_forms": ["Oral Tablet"]
        },
        {
            "rxcui": "198467",
            "name": "Aspirin 325 MG Oral Tablet",
            "base_names": ["Aspirin"],
            "dosage_forms": ["Oral Tablet"]
        }
    ],
    "count": 2
}
```

---

### 4. Add Medication (Protected)

**Endpoint:** `POST /api/medications`

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
    "rxcui": "243670"
}
```

**Response (201):**
```json
{
    "success": true,
    "message": "Medication added successfully",
    "data": {
        "id": 1,
        "user_id": 1,
        "rxcui": "243670",
        "drug_name": "Aspirin 81 MG Oral Tablet",
        "base_names": ["Aspirin"],
        "dosage_forms": ["Oral Tablet"],
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z"
    }
}
```

---

### 5. Get User Medications (Protected)

**Endpoint:** `GET /api/medications`

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
    "success": true,
    "message": "Medications retrieved successfully",
    "data": [
        {
            "id": 1,
            "user_id": 1,
            "rxcui": "243670",
            "drug_name": "Aspirin 81 MG Oral Tablet",
            "base_names": ["Aspirin"],
            "dosage_forms": ["Oral Tablet"],
            "created_at": "2024-01-15T10:30:00.000000Z",
            "updated_at": "2024-01-15T10:30:00.000000Z"
        }
    ],
    "count": 1
}
```

---

### 6. Delete Medication (Protected)

**Endpoint:** `DELETE /api/medications/{rxcui}`

**Headers:** `Authorization: Bearer {token}`

**Example:**
```
DELETE /api/medications/243670
```

**Response (200):**
```json
{
    "success": true,
    "message": "Medication removed successfully"
}
```

---

### 7. Logout (Protected)

**Endpoint:** `POST /api/logout`

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
    "success": true,
    "message": "Logged out successfully"
}
```

---

## Error Responses

### Validation Error (422)
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email has already been taken."]
    }
}
```

### Unauthorized (401)
```json
{
    "message": "Unauthenticated."
}
```

### Not Found (404)
```json
{
    "success": false,
    "message": "Medication not found in your list"
}
```

### Server Error (500)
```json
{
    "success": false,
    "message": "Registration failed",
    "error": "Database connection error"
}
```

---

## Testing

Run the test suite:
```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test file
php artisan test tests/Feature/AuthTest.php
```

**Test Coverage:** 90%+

---

## Rate Limiting

- **Public Search Endpoint:** 30 requests per minute per IP
- Can be adjusted in `routes/api.php`

---

## Caching

- Drug search results cached for 1 hour
- Drug details cached for 1 hour
- RXCUI validation cached for 1 hour
- Cache automatically cleared on errors

---

## Deployment

### Environment Variables (Production)
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://drug-tracker-production.up.railway.app

DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_DATABASE=your-db-name
DB_USERNAME=your-db-user
DB_PASSWORD=your-db-password

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
```

---

## Project Structure
```
app/
├── Http/
│   ├── Controllers/      # API Controllers
│   ├── Requests/         # Form Validation
│   └── Middleware/       # Custom Middleware
├── Models/               # Eloquent Models
├── Services/             # Business Logic
└── Repositories/         # Data Access Layer

tests/
└── Feature/              # Integration Tests

routes/
└── api.php               # API Routes
```

---

## Security Features

- Password hashing (bcrypt)
- API token authentication (Sanctum)
- CSRF protection
- SQL injection prevention (Eloquent ORM)
- Rate limiting
- Input validation

---

## Performance Optimizations

- Response caching (1 hour TTL)
- Database indexing
- Eager loading to prevent N+1 queries
- Query optimization
- Connection pooling

---

## Support & Contact

For issues or questions:
- Email: syedarslanahmed99@gmail.com
- GitHub Issues: [Project Issues](https://github.com/syedarsalan9/drug-tracker/issues)

---

## License

MIT License