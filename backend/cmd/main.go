package main

import (
	"log"

	"github.com/alertsMIS/backend/internal/config"
	"github.com/alertsMIS/backend/internal/database"
	"github.com/alertsMIS/backend/internal/handlers"
	"github.com/alertsMIS/backend/internal/middleware"
	"github.com/gofiber/fiber/v2"
	"github.com/gofiber/fiber/v2/middleware/cors"
	"github.com/gofiber/fiber/v2/middleware/logger"
	"github.com/gofiber/swagger"
)

// @title Alerts MIS API
// @version 1.0
// @description This is the API documentation for the Alerts Management Information System.
// @host localhost:8080
// @BasePath /api/v1
func main() {
	// Load configuration
	cfg, err := config.LoadConfig()
	if err != nil {
		log.Fatalf("Failed to load configuration: %v", err)
	}

	// Initialize database
	if err := database.InitDB(cfg.GetDSN()); err != nil {
		log.Fatalf("Failed to initialize database: %v", err)
	}

	// Create new Fiber app
	app := fiber.New(fiber.Config{
		AppName: "Alerts MIS API v1.0",
	})

	// Middleware
	app.Use(logger.New())
	app.Use(cors.New(cors.Config{
		AllowOrigins:     "https://alerts.health.go.ug,http://localhost:3000,http://localhost:3001",
		AllowHeaders:     "Origin, Content-Type, Accept, Authorization",
		AllowMethods:     "GET, POST, PUT, DELETE, OPTIONS",
		AllowCredentials: true,
	}))

	// Favicon route to prevent 404 errors
	app.Get("/favicon.ico", func(c *fiber.Ctx) error {
		return c.Status(204).Send(nil) // Return no content
	})

	// Swagger documentation
	app.Get("/swagger/*", swagger.HandlerDefault)

	// API routes
	api := app.Group("/api/v1")

	// Initialize handlers
	userHandler := handlers.NewUserHandler(database.GetDB(), cfg.JWTSecret)
	alertHandler := handlers.NewAlertHandler(database.GetDB())
	adminUnitsHandler := handlers.NewAdminUnitsHandler(database.GetDB())

	// Auth routes
	api.Post("/users/register", userHandler.Register)
	api.Post("/login", userHandler.Login)
	api.Post("/users/logout", middleware.AuthMiddleware(cfg.JWTSecret), userHandler.Logout)
	api.Get("/users/profile", middleware.AuthMiddleware(cfg.JWTSecret), userHandler.GetProfile)
	api.Get("/users/all", middleware.AuthMiddleware(cfg.JWTSecret), userHandler.GetAllUsers)
	api.Get("/users/:id", middleware.AuthMiddleware(cfg.JWTSecret), userHandler.GetUserById)
	api.Get("/debug/users", userHandler.DebugUsers)  // Temporary debug endpoint
	api.Get("/debug/bcrypt", userHandler.TestBcrypt) // Temporary bcrypt test endpoint

	// Alert routes
	api.Get("/alerts", middleware.AuthMiddleware(cfg.JWTSecret), alertHandler.GetAlerts)
	api.Get("/alerts/:id", middleware.AuthMiddleware(cfg.JWTSecret), alertHandler.GetAlert)
	api.Post("/alerts", middleware.AuthMiddleware(cfg.JWTSecret), alertHandler.CreateAlert)
	api.Put("/alerts/:id", middleware.AuthMiddleware(cfg.JWTSecret), alertHandler.UpdateAlert)
	api.Delete("/alerts/:id", middleware.AuthMiddleware(cfg.JWTSecret), alertHandler.DeleteAlert)
	api.Post("/alerts/:id/verify", alertHandler.VerifyAlert) // No auth required for verification
	api.Post("/alerts/:id/generate-token", middleware.AuthMiddleware(cfg.JWTSecret), alertHandler.GenerateVerificationToken)
	api.Post("/alerts/query", middleware.AuthMiddleware(cfg.JWTSecret), alertHandler.QueryAlerts)
	api.Get("/alerts/not-verified/count", middleware.AuthMiddleware(cfg.JWTSecret), alertHandler.GetNotVerifiedAlertsCount)
	api.Get("/alerts/verified/count", middleware.AuthMiddleware(cfg.JWTSecret), alertHandler.GetVerifiedAlertsCount)

	// Admin units routes
	api.Get("/admin-units/regions", adminUnitsHandler.GetAllRegions)
	api.Get("/admin-units/districts", adminUnitsHandler.GetAllDistricts)
	api.Get("/admin-units/subcounties", adminUnitsHandler.GetAllSubcounties)
	api.Get("/admin-units/regions/:region_id/districts", adminUnitsHandler.GetDistrictsByRegion)
	api.Get("/admin-units/districts/:district_id/subcounties", adminUnitsHandler.GetSubcountiesByDistrict)

	// Health check
	app.Get("/health", func(c *fiber.Ctx) error {
		return c.JSON(fiber.Map{
			"status":  "ok",
			"message": "Alerts MIS API is running",
		})
	})

	// Start server
	log.Printf("Server starting on port %s", cfg.ServerPort)

	if cfg.SSLEnabled {
		if cfg.SSLCertFile == "" || cfg.SSLKeyFile == "" {
			log.Fatal("SSL is enabled but certificate or key file is not specified")
		}
		log.Printf("Starting HTTPS server with SSL certificate: %s", cfg.SSLCertFile)
		log.Fatal(app.ListenTLS(":"+cfg.ServerPort, cfg.SSLCertFile, cfg.SSLKeyFile))
	} else {
		log.Fatal(app.Listen(":" + cfg.ServerPort))
	}
}
