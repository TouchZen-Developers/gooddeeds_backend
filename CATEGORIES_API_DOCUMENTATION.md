# Categories CRUD API Documentation

## Overview
This API provides full CRUD operations for managing categories in the GoodDeeds platform. All endpoints require admin authentication.

## Authentication
All category endpoints require:
- Valid authentication token (Bearer token)
- Admin role verification

## Base URL
```
http://localhost:8000/api/admin
```

---

## API Endpoints

### 1. List Categories
Get all categories with pagination and search.

**Endpoint:** `GET /categories`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
```

**Query Parameters:**
- `per_page` (optional): Number of items per page (default: 15)
- `search` (optional): Search term for category name

**Example Request:**
```bash
curl -X GET "http://localhost:8000/api/admin/categories?per_page=10&search=medical" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "categories": [
      {
        "id": 1,
        "name": "Medical",
        "total_items": 8,
        "icon_url": "https://s3.amazonaws.com/bucket/categories/icons/medical.png",
        "created_at": "2025-10-01T17:28:26.000000Z",
        "updated_at": "2025-10-01T17:28:26.000000Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 6
    }
  }
}
```

---

### 2. Create Category
Create a new category.

**Endpoint:** `POST /categories`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "name": "Sports",
  "total_items": 10,
  "icon": "file_upload_here" // Optional: multipart/form-data
}
```

**Example Request:**
```bash
curl -X POST "http://localhost:8000/api/admin/categories" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Sports",
    "total_items": 10
  }'
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "Category created successfully",
  "data": {
    "category": {
      "id": 7,
      "name": "Sports",
      "total_items": 10,
      "icon_url": null,
      "created_at": "2025-10-01T17:28:55.000000Z",
      "updated_at": "2025-10-01T17:28:55.000000Z"
    }
  }
}
```

**Validation Errors (422):**
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "name": ["A category with this name already exists."]
  }
}
```

---

### 3. Get Single Category
Get a specific category by ID.

**Endpoint:** `GET /categories/{id}`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
```

**Example Request:**
```bash
curl -X GET "http://localhost:8000/api/admin/categories/1" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "category": {
      "id": 1,
      "name": "Medical",
      "total_items": 8,
      "icon_url": "https://s3.amazonaws.com/bucket/categories/icons/medical.png",
      "created_at": "2025-10-01T17:28:26.000000Z",
      "updated_at": "2025-10-01T17:28:26.000000Z"
    }
  }
}
```

**Not Found (404):**
```json
{
  "success": false,
  "message": "Category not found"
}
```

---

### 4. Update Category
Update an existing category.

**Endpoint:** `PUT /categories/{id}`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "name": "Sports & Recreation",
  "total_items": 15,
  "icon": "file_upload_here" // Optional: multipart/form-data
}
```

**Example Request:**
```bash
curl -X PUT "http://localhost:8000/api/admin/categories/7" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Sports & Recreation",
    "total_items": 15
  }'
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Category updated successfully",
  "data": {
    "category": {
      "id": 7,
      "name": "Sports & Recreation",
      "total_items": 15,
      "icon_url": "https://s3.amazonaws.com/bucket/categories/icons/sports.png",
      "created_at": "2025-10-01T17:28:55.000000Z",
      "updated_at": "2025-10-01T17:29:00.000000Z"
    }
  }
}
```

---

### 5. Delete Category
Delete a category.

**Endpoint:** `DELETE /categories/{id}`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
```

**Example Request:**
```bash
curl -X DELETE "http://localhost:8000/api/admin/categories/7" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Category deleted successfully"
}
```

---

### 6. Upload Category Icon
Upload an icon for a category (separate endpoint).

**Endpoint:** `POST /categories/upload`

**Headers:**
```
Content-Type: multipart/form-data
Accept: application/json
Authorization: Bearer {token}
```

**Form Data:**
- `icon`: Image file (required)

**Example Request:**
```bash
curl -X POST "http://localhost:8000/api/admin/categories/upload" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "icon=@/path/to/icon.png"
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Icon uploaded successfully",
  "data": {
    "icon_url": "https://s3.amazonaws.com/bucket/categories/icons/uuid.png"
  }
}
```

**Validation Errors (422):**
```json
{
  "success": false,
  "message": "Icon validation failed",
  "errors": [
    "File must be an image (JPEG, PNG, GIF, or WebP)"
  ]
}
```

---

## Error Responses

### Authentication Errors

**Unauthenticated (401):**
```json
{
  "success": false,
  "message": "Unauthenticated. Please login first."
}
```

**Unauthorized (403):**
```json
{
  "success": false,
  "message": "Unauthorized. Admin access required."
}
```

### Validation Errors (422)

**Field Validation:**
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "name": ["Category name is required."],
    "total_items": ["Total items must be a number."],
    "icon": ["The icon must be an image."]
  }
}
```

---

## File Upload Specifications

### Supported Image Types
- JPEG (.jpg, .jpeg)
- PNG (.png)
- GIF (.gif)
- WebP (.webp)

### File Size Limits
- Maximum file size: 5MB
- Recommended dimensions: 64x64px to 512x512px

### S3 Storage
- Files are uploaded to S3 bucket
- Public URLs are generated automatically
- Old files are deleted when updating icons
- Files are stored in `categories/icons/` folder

---

## Validation Rules

### Category Name
- Required
- String
- Maximum 255 characters
- Must be unique

### Total Items
- Optional
- Integer
- Minimum value: 0

### Icon
- Optional
- Must be an image file
- Supported formats: jpeg, png, jpg, gif, webp
- Maximum size: 5MB

---

## Environment Configuration

Add these variables to your `.env` file for S3 integration:

```env
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your_bucket_name
AWS_URL=https://your-bucket.s3.amazonaws.com
```

---

## Sample Data

The system comes with pre-seeded categories:
- Medical (8 items)
- School (12 items)
- Food (4 items)
- Clothing (15 items)
- Electronics (6 items)
- Books (20 items)

---

## Testing the API

### 1. Login as Admin
```bash
curl -X POST "http://localhost:8000/api/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@gooddeeds.com",
    "password": "Admin@123"
  }'
```

### 2. Use Token for Category Operations
```bash
# Get all categories
curl -X GET "http://localhost:8000/api/admin/categories" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Create category
curl -X POST "http://localhost:8000/api/admin/categories" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name": "New Category", "total_items": 5}'
```

---

## Database Schema

```sql
categories:
- id (bigint, primary key)
- name (varchar, unique)
- total_items (integer, default 0)
- icon_url (varchar, nullable)
- created_at (timestamp)
- updated_at (timestamp)
```
