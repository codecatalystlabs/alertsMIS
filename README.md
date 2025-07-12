# Alerts Management Information System

A modern web application for managing disease alerts and surveillance, built with Go Fiber and React.

## Features

- User authentication and authorization
- Role-based access control
- Alert management (create, read, update, delete)
- Advanced filtering and search
- Administrative unit management (regions, districts, subcounties)
- Responsive design with Material-UI
- Swagger API documentation

## Tech Stack

### Backend
- Go Fiber (web framework)
- GORM (ORM)
- MariaDB (database)
- JWT (authentication)
- Swagger (API documentation)

### Frontend
- React
- Redux Toolkit (state management)
- Material-UI (UI components)
- Formik & Yup (form handling and validation)
- Axios (HTTP client)

## Prerequisites

- Go 1.21 or later
- Node.js 18 or later
- MariaDB 10.4 or later

## Setup

1. Clone the repository:
```bash
git clone https://github.com/yourusername/alerts-mis.git
cd alerts-mis
```

2. Set up the backend:
```bash
cd backend
cp .env.example .env  # Configure your environment variables
go mod download
go run cmd/main.go
```

3. Set up the frontend:
```bash
cd frontend
cp .env.example .env  # Configure your environment variables
npm install
npm start
```

4. Configure the database:
- Create a new MariaDB database
- Update the `.env` file with your database credentials
- The application will automatically create the required tables

## Development

### Backend Development
```bash
cd backend
go run cmd/main.go
```

The backend server will start at http://localhost:8080

### Frontend Development
```bash
cd frontend
npm start
```

The frontend development server will start at http://localhost:3000

## API Documentation

Once the backend server is running, you can access the Swagger API documentation at:
http://localhost:8080/swagger/

## Database Migration

The application uses GORM auto-migration to handle database schema changes. When you start the application, it will automatically create or update the necessary tables.

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

