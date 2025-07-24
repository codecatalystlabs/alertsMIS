# Alerts MIS API Endpoints

This document provides a comprehensive list of all API endpoints converted from the PHP flows in the `manage` folder.

## Base URL
```
http://localhost:8080/api/v1
```

## Authentication
Most endpoints require JWT authentication. Include the token in the Authorization header:
```
Authorization: Bearer <your-jwt-token>
```

## Endpoints

### Authentication & Users

#### Login
- **POST** `/login`
- **Description**: Authenticate user and get JWT token
- **Body**: 
  ```json
  {
    "username": "string",
    "password": "string"
  }
  ```
- **Response**: 
  ```json
  {
    "token": "string",
    "user": {
      "id": 1,
      "username": "string",
      "email": "string",
      "affiliation": "string",
      "userType": "string",
      "level": "string"
    }
  }
  ```

#### Register User
- **POST** `/users/register`
- **Description**: Register a new user
- **Body**: User object
- **Auth**: Required

#### Get User Profile
- **GET** `/users/profile`
- **Description**: Get authenticated user's profile
- **Auth**: Required

#### Logout
- **POST** `/users/logout`
- **Description**: Logout user
- **Auth**: Required

#### Get All Users
- **GET** `/users/all`
- **Description**: Get all users in the system
- **Auth**: Required

#### Get User by ID
- **GET** `/users/:id`
- **Description**: Get user by ID
- **Auth**: Required

### Alerts

#### Create Alert
- **POST** `/alerts`
- **Description**: Create a new disease alert
- **Body**: Alert object
- **Auth**: Required
- **Response**: Created alert object

#### Get All Alerts
- **GET** `/alerts`
- **Description**: Get all alerts with filtering and pagination
- **Auth**: Required
- **Query Parameters**:
  - `page` (int): Page number (default: 1)
  - `limit` (int): Records per page (default: 50)
  - `region` (string): Filter by region
  - `district` (string): Filter by district
  - `from_date` (string): Filter from date (YYYY-MM-DD)
  - `to_date` (string): Filter to date (YYYY-MM-DD)
  - `alert_id` (int): Filter by alert ID
  - `alert_case_name` (string): Filter by alert case name
  - `person_reporting` (string): Filter by person reporting
  - `status` (string): Filter by status
  - `is_verified` (bool): Filter by verification status
- **Response**: 
  ```json
  {
    "alerts": [...],
    "pagination": {
      "page": 1,
      "limit": 50,
      "total": 100,
      "pages": 2
    }
  }
  ```

#### Get Alert by ID
- **GET** `/alerts/:id`
- **Description**: Get a specific alert by ID
- **Auth**: Required
- **Response**: Alert object

#### Update Alert
- **PUT** `/alerts/:id`
- **Description**: Update an existing alert
- **Body**: Alert object
- **Auth**: Required
- **Response**: Updated alert object

#### Delete Alert
- **DELETE** `/alerts/:id`
- **Description**: Delete an alert
- **Auth**: Required
- **Response**: Success message

#### Verify Alert
- **POST** `/alerts/:id/verify`
- **Description**: Verify an alert using a verification token
- **Auth**: Not required (public endpoint for verification)
- **Body**:
  ```json
  {
    "token": "string",
    "status": "string",
    "verificationDate": "2024-01-01T00:00:00Z",
    "verificationTime": "2024-01-01T00:00:00Z",
    "cifNo": "string",
    "personReporting": "string",
    "village": "string",
    "subCounty": "string",
    "contactNumber": "string",
    "sourceOfAlert": "string",
    "alertCaseName": "string",
    "alertCaseAge": 0,
    "alertCaseSex": "string",
    "alertCasePregnantDuration": 0,
    "alertCaseVillage": "string",
    "alertCaseParish": "string",
    "alertCaseSubCounty": "string",
    "alertCaseDistrict": "string",
    "alertCaseNationality": "string",
    "pointOfContactName": "string",
    "pointOfContactRelationship": "string",
    "pointOfContactPhone": "string",
    "history": "string",
    "healthFacilityVisit": "string",
    "traditionalHealerVisit": "string",
    "symptoms": "string",
    "actions": "string",
    "feedback": "string",
    "verifiedBy": "string"
  }
  ```
- **Response**: 
  ```json
  {
    "message": "Alert verified successfully",
    "alert": {...}
  }
  ```

#### Generate Verification Token
- **POST** `/alerts/:id/generate-token`
- **Description**: Generate a verification token for an alert
- **Auth**: Required
- **Response**:
  ```json
  {
    "message": "Verification token generated successfully",
    "token": "string",
    "alertId": 1
  }
  ```

#### Query Alerts
- **POST** `/alerts/query`
- **Description**: Query alerts based on verification status and time
- **Auth**: Required
- **Body**:
  ```json
  {
    "query": "verified|not_verified_1h|not_verified_less_1h|not_verified_in_24h"
  }
  ```
- **Response**: Array of alerts

#### Get Verified Alerts Count
- **GET** `/alerts/verified/count`
- **Description**: Get count of alerts verified in the last hour
- **Auth**: Required
- **Response**:
  ```json
  {
    "count": 5
  }
  ```

#### Get Unverified Alerts Count
- **GET** `/alerts/not-verified/count`
- **Description**: Get count of alerts not verified in the last hour
- **Auth**: Required
- **Response**:
  ```json
  {
    "count": 10
  }
  ```

### Administrative Units

#### Get Options
- **GET** `/admin-units/options`
- **Description**: Get distinct values for regions, districts, and facilities
- **Auth**: Not required
- **Response**:
  ```json
  {
    "regions": ["Region1", "Region2"],
    "districts": ["District1", "District2"],
    "facilities": ["Facility1", "Facility2"]
  }
  ```

#### Get All Regions
- **GET** `/admin-units/regions`
- **Description**: Get all regions
- **Auth**: Not required
- **Response**: Array of Region objects

#### Get All Districts
- **GET** `/admin-units/districts`
- **Description**: Get all districts with region information
- **Auth**: Not required
- **Response**: Array of District objects

#### Get All Subcounties
- **GET** `/admin-units/subcounties`
- **Description**: Get all subcounties with district information
- **Auth**: Not required
- **Response**: Array of Subcounty objects

#### Get All Facilities
- **GET** `/admin-units/facilities`
- **Description**: Get all facilities with subcounty information
- **Auth**: Not required
- **Response**: Array of Facility objects

#### Get Districts by Region
- **GET** `/admin-units/regions/:region_id/districts`
- **Description**: Get districts for a specific region
- **Auth**: Not required
- **Response**: Array of District objects

#### Get Subcounties by District
- **GET** `/admin-units/districts/:district_id/subcounties`
- **Description**: Get subcounties for a specific district
- **Auth**: Not required
- **Response**: Array of Subcounty objects

#### Get Facilities by Subcounty
- **GET** `/admin-units/subcounties/:subcounty_id/facilities`
- **Description**: Get facilities for a specific subcounty
- **Query Parameters**:
  - `facility_type` (string): Filter by facility type/ownership
- **Auth**: Not required
- **Response**: Array of Facility objects

### Health Check

#### Health Check
- **GET** `/health`
- **Description**: Check if the API is running
- **Auth**: Not required
- **Response**:
  ```json
  {
    "status": "ok",
    "message": "Alerts MIS API is running"
  }
  ```

## Data Models

### Alert
```json
{
  "id": 1,
  "status": "string",
  "date": "2024-01-01T00:00:00Z",
  "time": "2024-01-01T00:00:00Z",
  "callTaker": "string",
  "cifNo": "string",
  "personReporting": "string",
  "village": "string",
  "subCounty": "string",
  "contactNumber": "string",
  "sourceOfAlert": "string",
  "alertCaseName": "string",
  "alertCaseAge": 0,
  "alertCaseSex": "string",
  "alertCasePregnantDuration": 0,
  "alertCaseVillage": "string",
  "alertCaseParish": "string",
  "alertCaseSubCounty": "string",
  "alertCaseDistrict": "string",
  "alertCaseNationality": "string",
  "pointOfContactName": "string",
  "pointOfContactRelationship": "string",
  "pointOfContactPhone": "string",
  "history": "string",
  "healthFacilityVisit": "string",
  "traditionalHealerVisit": "string",
  "symptoms": "string",
  "actions": "string",
  "caseVerificationDesk": "string",
  "fieldVerification": "string",
  "fieldVerificationDecision": "string",
  "feedback": "string",
  "labResult": "string",
  "labResultDate": "2024-01-01T00:00:00Z",
  "isHighlighted": false,
  "assignedTo": "string",
  "alertReportedBefore": "string",
  "alertFrom": "string",
  "verified": "string",
  "comments": "string",
  "verificationDate": "2024-01-01T00:00:00Z",
  "verificationTime": "2024-01-01T00:00:00Z",
  "response": "string",
  "narrative": "string",
  "facilityType": "string",
  "facility": "string",
  "isVerified": false,
  "verifiedBy": "string",
  "region": "string",
  "createdAt": "2024-01-01T00:00:00Z",
  "updatedAt": "2024-01-01T00:00:00Z"
}
```

### User
```json
{
  "id": 1,
  "username": "string",
  "firstName": "string",
  "lastName": "string",
  "otherName": "string",
  "email": "string",
  "affiliation": "string",
  "userType": "string",
  "level": "string",
  "createdAt": "2024-01-01T00:00:00Z",
  "updatedAt": "2024-01-01T00:00:00Z"
}
```

### AlertVerificationToken
```json
{
  "id": 1,
  "alertId": 1,
  "token": "string",
  "used": false,
  "createdAt": "2024-01-01T00:00:00Z",
  "usedAt": "2024-01-01T00:00:00Z"
}
```

## Error Responses

All endpoints return consistent error responses:

```json
{
  "error": "Error message",
  "details": "Detailed error information (optional)"
}
```

Common HTTP status codes:
- `200`: Success
- `201`: Created
- `400`: Bad Request
- `401`: Unauthorized
- `404`: Not Found
- `500`: Internal Server Error

## PHP to Go Conversion Summary

The following PHP flows have been converted to Go API endpoints:

1. **alert_verification.php** → `POST /alerts/:id/verify`
2. **index.php** → `POST /alerts`
3. **call_log.php** → `GET /alerts` (with filtering)
4. **alerts.php** → `GET /alerts` (with district filtering)
5. **fetch_alert_data.php** → `POST /alerts/query`
6. **login.php** → `POST /login`
7. **fetch_options.php** → `GET /admin-units/options`
8. **getDistrict.php** → `GET /admin-units/regions/:region_id/districts`
9. **getSubcounties.php** → `GET /admin-units/districts/:district_id/subcounties`
10. **getFacilities.php** → `GET /admin-units/subcounties/:subcounty_id/facilities`

## Additional Features Added

1. **Token-based Alert Verification**: Secure verification system with unique tokens
2. **Comprehensive Filtering**: Advanced filtering and pagination for alerts
3. **Better Error Handling**: Consistent error responses with details
4. **Input Validation**: Request validation and sanitization
5. **Swagger Documentation**: Auto-generated API documentation
6. **JWT Authentication**: Secure token-based authentication
7. **Database Transactions**: ACID compliance for critical operations 