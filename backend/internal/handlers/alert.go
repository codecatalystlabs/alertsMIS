package handlers

import (
	"time"

	"github.com/alertsMIS/backend/internal/models"
	"github.com/gofiber/fiber/v2"
	"gorm.io/gorm"
)

// AlertHandler handles alert-related HTTP requests
type AlertHandler struct {
	db *gorm.DB
}

// NewAlertHandler creates a new AlertHandler
func NewAlertHandler(db *gorm.DB) *AlertHandler {
	return &AlertHandler{db: db}
}

// CreateAlert handles alert creation
// @Router /api/v1/alerts [post]
func (h *AlertHandler) CreateAlert(c *fiber.Ctx) error {
	alert := new(models.Alert)

	if err := c.BodyParser(alert); err != nil {
		return c.Status(fiber.StatusBadRequest).JSON(fiber.Map{
			"error": "Invalid request body",
		})
	}

	if err := h.db.Create(alert).Error; err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error": "Failed to create alert",
		})
	}

	return c.Status(fiber.StatusCreated).JSON(alert)
}

// GetAlerts handles retrieving all alerts
// @Router /api/v1/alerts [get]
func (h *AlertHandler) GetAlerts(c *fiber.Ctx) error {
	var alerts []models.Alert

	if err := h.db.Find(&alerts).Error; err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error": "Failed to fetch alerts",
		})
	}

	return c.JSON(alerts)
}

// GetAlert handles retrieving a single alert
// @Router /api/v1/alerts/{id} [get]
func (h *AlertHandler) GetAlert(c *fiber.Ctx) error {
	id := c.Params("id")
	var alert models.Alert

	if err := h.db.First(&alert, id).Error; err != nil {
		return c.Status(fiber.StatusNotFound).JSON(fiber.Map{
			"error": "Alert not found",
		})
	}

	return c.JSON(alert)
}

// UpdateAlert handles updating an alert
// @Router /api/v1/alerts/{id} [put]
func (h *AlertHandler) UpdateAlert(c *fiber.Ctx) error {
	id := c.Params("id")
	var alert models.Alert

	if err := h.db.First(&alert, id).Error; err != nil {
		return c.Status(fiber.StatusNotFound).JSON(fiber.Map{
			"error": "Alert not found",
		})
	}

	if err := c.BodyParser(&alert); err != nil {
		return c.Status(fiber.StatusBadRequest).JSON(fiber.Map{
			"error": "Invalid request body",
		})
	}

	if err := h.db.Save(&alert).Error; err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error": "Failed to update alert",
		})
	}

	return c.JSON(alert)
}

// DeleteAlert handles deleting an alert
// @Router /api/v1/alerts/{id} [delete]
func (h *AlertHandler) DeleteAlert(c *fiber.Ctx) error {
	id := c.Params("id")
	var alert models.Alert

	if err := h.db.First(&alert, id).Error; err != nil {
		return c.Status(fiber.StatusNotFound).JSON(fiber.Map{
			"error": "Alert not found",
		})
	}

	if err := h.db.Delete(&alert).Error; err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error": "Failed to delete alert",
		})
	}

	return c.JSON(fiber.Map{
		"message": "Alert deleted successfully",
	})
}

// Get number of alerts Not verified in 59 min
// @Router /api/v1/alerts/not-verified/count [get]
func (h *AlertHandler) GetNotVerifiedAlertsCount(c *fiber.Ctx) error {
	var count int64

	if err := h.db.Model(&models.Alert{}).Where("is_verified = ? AND created_at >= ?", 0, time.Now().Add(-1*time.Hour)).Count(&count).Error; err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error": "Failed to fetch not verified alerts count",
		})
	}

	return c.JSON(fiber.Map{
		"count": count,
	})
}

// Get number of alerts Verified in 59 min
// @Router /api/v1/alerts/verified/count [get]
func (h *AlertHandler) GetVerifiedAlertsCount(c *fiber.Ctx) error {
	var count int64

	if err := h.db.Model(&models.Alert{}).Where("is_verified = ? AND created_at >= ?", 0, time.Now().Add(-1*time.Hour)).Count(&count).Error; err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error": "Failed to fetch not verified alerts count",
		})
	}

	return c.JSON(fiber.Map{
		"count": count,
	})
}
