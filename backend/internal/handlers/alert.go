package handlers

import (
	"crypto/rand"
	"encoding/hex"
	"strconv"
	"strings"
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

// generateToken creates a secure random token for alert verification
func (h *AlertHandler) generateToken() (string, error) {
	bytes := make([]byte, 32)
	if _, err := rand.Read(bytes); err != nil {
		return "", err
	}
	return hex.EncodeToString(bytes), nil
}

// CreateAlert handles alert creation
// @Summary Create a new alert
// @Description Create a new disease alert
// @Tags alerts
// @Accept json
// @Produce json
// @Param alert body models.Alert true "Alert object"
// @Success 201 {object} models.Alert
// @Failure 400 {object} fiber.Map
// @Failure 500 {object} fiber.Map
// @Router /api/v1/alerts [post]
func (h *AlertHandler) CreateAlert(c *fiber.Ctx) error {
	alert := new(models.Alert)

	if err := c.BodyParser(alert); err != nil {
		return c.Status(fiber.StatusBadRequest).JSON(fiber.Map{
			"error":   "Invalid request body",
			"details": err.Error(),
		})
	}

	// Set default values
	if alert.Date.IsZero() {
		alert.Date = time.Now()
	}
	if alert.Time.IsZero() {
		alert.Time = time.Now()
	}
	if alert.Status == "" {
		alert.Status = "Pending"
	}
	if alert.AlertFrom == "" {
		alert.AlertFrom = "Open Alerts"
	}

	// Validate required fields
	if alert.PersonReporting == "" {
		return c.Status(fiber.StatusBadRequest).JSON(fiber.Map{
			"error": "Person reporting is required",
		})
	}
	if alert.AlertCaseName == "" {
		return c.Status(fiber.StatusBadRequest).JSON(fiber.Map{
			"error": "Alert case name is required",
		})
	}

	if err := h.db.Create(alert).Error; err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error":   "Failed to create alert",
			"details": err.Error(),
		})
	}

	return c.Status(fiber.StatusCreated).JSON(alert)
}

// GetAlerts handles retrieving alerts with filtering and pagination
// @Summary Get alerts
// @Description Get all alerts with optional filtering and pagination
// @Tags alerts
// @Accept json
// @Produce json
// @Param page query int false "Page number"
// @Param limit query int false "Number of records per page"
// @Param region query string false "Filter by region"
// @Param district query string false "Filter by district"
// @Param from_date query string false "Filter from date (YYYY-MM-DD)"
// @Param to_date query string false "Filter to date (YYYY-MM-DD)"
// @Param alert_id query int false "Filter by alert ID"
// @Param alert_case_name query string false "Filter by alert case name"
// @Param person_reporting query string false "Filter by person reporting"
// @Param status query string false "Filter by status"
// @Param is_verified query bool false "Filter by verification status"
// @Success 200 {array} models.Alert
// @Failure 500 {object} fiber.Map
// @Router /api/v1/alerts [get]
func (h *AlertHandler) GetAlerts(c *fiber.Ctx) error {
	var alerts []models.Alert
	query := h.db.Model(&models.Alert{})

	// Pagination
	page, _ := strconv.Atoi(c.Query("page", "1"))
	limit, _ := strconv.Atoi(c.Query("limit", "50"))
	offset := (page - 1) * limit

	// Apply filters
	if region := c.Query("region"); region != "" {
		query = query.Where("region = ?", region)
	}
	if district := c.Query("district"); district != "" {
		query = query.Where("alert_case_district = ?", district)
	}
	if fromDate := c.Query("from_date"); fromDate != "" {
		query = query.Where("date >= ?", fromDate)
	}
	if toDate := c.Query("to_date"); toDate != "" {
		query = query.Where("date <= ?", toDate)
	}
	if alertID := c.Query("alert_id"); alertID != "" {
		query = query.Where("id = ?", alertID)
	}
	if alertCaseName := c.Query("alert_case_name"); alertCaseName != "" {
		query = query.Where("alert_case_name LIKE ?", "%"+alertCaseName+"%")
	}
	if personReporting := c.Query("person_reporting"); personReporting != "" {
		query = query.Where("person_reporting LIKE ?", "%"+personReporting+"%")
	}
	if status := c.Query("status"); status != "" {
		query = query.Where("status = ?", status)
	}
	if isVerified := c.Query("is_verified"); isVerified != "" {
		verified, _ := strconv.ParseBool(isVerified)
		query = query.Where("is_verified = ?", verified)
	}

	// Apply pagination and ordering
	query = query.Order("date DESC").Offset(offset).Limit(limit)

	if err := query.Find(&alerts).Error; err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error":   "Failed to fetch alerts",
			"details": err.Error(),
		})
	}

	// Get total count for pagination
	var total int64
	h.db.Model(&models.Alert{}).Count(&total)

	return c.JSON(fiber.Map{
		"alerts": alerts,
		"pagination": fiber.Map{
			"page":  page,
			"limit": limit,
			"total": total,
			"pages": (total + int64(limit) - 1) / int64(limit),
		},
	})
}

// GetAlert handles retrieving a single alert
// @Summary Get alert by ID
// @Description Get a specific alert by its ID
// @Tags alerts
// @Accept json
// @Produce json
// @Param id path int true "Alert ID"
// @Success 200 {object} models.Alert
// @Failure 404 {object} fiber.Map
// @Router /api/v1/alerts/{id} [get]
func (h *AlertHandler) GetAlert(c *fiber.Ctx) error {
	id := c.Params("id")
	var alert models.Alert

	if err := h.db.First(&alert, id).Error; err != nil {
		if err == gorm.ErrRecordNotFound {
			return c.Status(fiber.StatusNotFound).JSON(fiber.Map{
				"error": "Alert not found",
			})
		}
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error":   "Failed to fetch alert",
			"details": err.Error(),
		})
	}

	return c.JSON(alert)
}

// UpdateAlert handles updating an alert
// @Summary Update alert
// @Description Update an existing alert
// @Tags alerts
// @Accept json
// @Produce json
// @Param id path int true "Alert ID"
// @Param alert body models.Alert true "Alert object"
// @Success 200 {object} models.Alert
// @Failure 400 {object} fiber.Map
// @Failure 404 {object} fiber.Map
// @Failure 500 {object} fiber.Map
// @Router /api/v1/alerts/{id} [put]
func (h *AlertHandler) UpdateAlert(c *fiber.Ctx) error {
	id := c.Params("id")
	var alert models.Alert

	if err := h.db.First(&alert, id).Error; err != nil {
		if err == gorm.ErrRecordNotFound {
			return c.Status(fiber.StatusNotFound).JSON(fiber.Map{
				"error": "Alert not found",
			})
		}
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error":   "Failed to fetch alert",
			"details": err.Error(),
		})
	}

	if err := c.BodyParser(&alert); err != nil {
		return c.Status(fiber.StatusBadRequest).JSON(fiber.Map{
			"error":   "Invalid request body",
			"details": err.Error(),
		})
	}

	if err := h.db.Save(&alert).Error; err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error":   "Failed to update alert",
			"details": err.Error(),
		})
	}

	return c.JSON(alert)
}

// DeleteAlert handles deleting an alert
// @Summary Delete alert
// @Description Delete an alert by ID
// @Tags alerts
// @Accept json
// @Produce json
// @Param id path int true "Alert ID"
// @Success 200 {object} fiber.Map
// @Failure 404 {object} fiber.Map
// @Failure 500 {object} fiber.Map
// @Router /api/v1/alerts/{id} [delete]
func (h *AlertHandler) DeleteAlert(c *fiber.Ctx) error {
	id := c.Params("id")
	var alert models.Alert

	if err := h.db.First(&alert, id).Error; err != nil {
		if err == gorm.ErrRecordNotFound {
			return c.Status(fiber.StatusNotFound).JSON(fiber.Map{
				"error": "Alert not found",
			})
		}
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error":   "Failed to fetch alert",
			"details": err.Error(),
		})
	}

	if err := h.db.Delete(&alert).Error; err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error":   "Failed to delete alert",
			"details": err.Error(),
		})
	}

	return c.JSON(fiber.Map{
		"message": "Alert deleted successfully",
	})
}

// VerifyAlert handles alert verification with token
// @Summary Verify alert
// @Description Verify an alert using a verification token
// @Tags alerts
// @Accept json
// @Produce json
// @Param id path int true "Alert ID"
// @Param verification body map[string]interface{} true "Verification data"
// @Success 200 {object} models.Alert
// @Failure 400 {object} fiber.Map
// @Failure 404 {object} fiber.Map
// @Failure 500 {object} fiber.Map
// @Router /api/v1/alerts/{id}/verify [post]
func (h *AlertHandler) VerifyAlert(c *fiber.Ctx) error {
	alertID := c.Params("id")

	var input struct {
		Token                      string    `json:"token"`
		Status                     string    `json:"status"`
		VerificationDate           time.Time `json:"verificationDate"`
		VerificationTime           time.Time `json:"verificationTime"`
		CIFNo                      string    `json:"cifNo"`
		PersonReporting            string    `json:"personReporting"`
		Village                    string    `json:"village"`
		SubCounty                  string    `json:"subCounty"`
		ContactNumber              string    `json:"contactNumber"`
		SourceOfAlert              string    `json:"sourceOfAlert"`
		AlertCaseName              string    `json:"alertCaseName"`
		AlertCaseAge               int       `json:"alertCaseAge"`
		AlertCaseSex               string    `json:"alertCaseSex"`
		AlertCasePregnantDuration  int       `json:"alertCasePregnantDuration"`
		AlertCaseVillage           string    `json:"alertCaseVillage"`
		AlertCaseParish            string    `json:"alertCaseParish"`
		AlertCaseSubCounty         string    `json:"alertCaseSubCounty"`
		AlertCaseDistrict          string    `json:"alertCaseDistrict"`
		AlertCaseNationality       string    `json:"alertCaseNationality"`
		PointOfContactName         string    `json:"pointOfContactName"`
		PointOfContactRelationship string    `json:"pointOfContactRelationship"`
		PointOfContactPhone        string    `json:"pointOfContactPhone"`
		History                    string    `json:"history"`
		HealthFacilityVisit        string    `json:"healthFacilityVisit"`
		TraditionalHealerVisit     string    `json:"traditionalHealerVisit"`
		Symptoms                   string    `json:"symptoms"`
		Actions                    string    `json:"actions"`
		Feedback                   string    `json:"feedback"`
		VerifiedBy                 string    `json:"verifiedBy"`
	}

	if err := c.BodyParser(&input); err != nil {
		return c.Status(fiber.StatusBadRequest).JSON(fiber.Map{
			"error":   "Invalid request body",
			"details": err.Error(),
		})
	}

	// Validate token
	if input.Token == "" {
		return c.Status(fiber.StatusBadRequest).JSON(fiber.Map{
			"error": "Token is required",
		})
	}

	// Check if token exists and is unused
	var token models.AlertVerificationToken
	if err := h.db.Where("alert_id = ? AND token = ? AND used = ?", alertID, input.Token, false).First(&token).Error; err != nil {
		if err == gorm.ErrRecordNotFound {
			return c.Status(fiber.StatusBadRequest).JSON(fiber.Map{
				"error": "Invalid or already used token",
			})
		}
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error":   "Failed to validate token",
			"details": err.Error(),
		})
	}

	// Get the alert
	var alert models.Alert
	if err := h.db.First(&alert, alertID).Error; err != nil {
		if err == gorm.ErrRecordNotFound {
			return c.Status(fiber.StatusNotFound).JSON(fiber.Map{
				"error": "Alert not found",
			})
		}
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error":   "Failed to fetch alert",
			"details": err.Error(),
		})
	}

	// Update alert with verification data
	alert.Status = input.Status
	alert.VerificationDate = &input.VerificationDate
	alert.VerificationTime = &input.VerificationTime
	alert.CIFNo = strings.ToUpper(input.CIFNo)
	alert.PersonReporting = input.PersonReporting
	alert.Village = input.Village
	alert.SubCounty = input.SubCounty
	alert.ContactNumber = input.ContactNumber
	alert.SourceOfAlert = input.SourceOfAlert
	alert.AlertCaseName = input.AlertCaseName
	alert.AlertCaseAge = input.AlertCaseAge
	alert.AlertCaseSex = input.AlertCaseSex
	alert.AlertCasePregnantDuration = input.AlertCasePregnantDuration
	alert.AlertCaseVillage = input.AlertCaseVillage
	alert.AlertCaseParish = input.AlertCaseParish
	alert.AlertCaseSubCounty = input.AlertCaseSubCounty
	alert.AlertCaseDistrict = input.AlertCaseDistrict
	alert.AlertCaseNationality = input.AlertCaseNationality
	alert.PointOfContactName = input.PointOfContactName
	alert.PointOfContactRelationship = input.PointOfContactRelationship
	alert.PointOfContactPhone = input.PointOfContactPhone
	alert.History = input.History
	alert.HealthFacilityVisit = input.HealthFacilityVisit
	alert.TraditionalHealerVisit = input.TraditionalHealerVisit
	alert.Symptoms = input.Symptoms
	alert.Actions = input.Actions
	alert.Feedback = input.Feedback
	alert.IsVerified = true
	alert.VerifiedBy = input.VerifiedBy

	// Start transaction
	tx := h.db.Begin()

	// Update alert
	if err := tx.Save(&alert).Error; err != nil {
		tx.Rollback()
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error":   "Failed to update alert",
			"details": err.Error(),
		})
	}

	// Mark token as used
	now := time.Now()
	token.Used = true
	token.UsedAt = &now
	if err := tx.Save(&token).Error; err != nil {
		tx.Rollback()
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error":   "Failed to mark token as used",
			"details": err.Error(),
		})
	}

	// Commit transaction
	if err := tx.Commit().Error; err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error":   "Failed to commit transaction",
			"details": err.Error(),
		})
	}

	return c.JSON(fiber.Map{
		"message": "Alert verified successfully",
		"alert":   alert,
	})
}

// GenerateVerificationToken generates a verification token for an alert
// @Summary Generate verification token
// @Description Generate a verification token for an alert
// @Tags alerts
// @Accept json
// @Produce json
// @Param id path int true "Alert ID"
// @Success 200 {object} fiber.Map
// @Failure 404 {object} fiber.Map
// @Failure 500 {object} fiber.Map
// @Router /api/v1/alerts/{id}/generate-token [post]
func (h *AlertHandler) GenerateVerificationToken(c *fiber.Ctx) error {
	alertID := c.Params("id")

	// Check if alert exists
	var alert models.Alert
	if err := h.db.First(&alert, alertID).Error; err != nil {
		if err == gorm.ErrRecordNotFound {
			return c.Status(fiber.StatusNotFound).JSON(fiber.Map{
				"error": "Alert not found",
			})
		}
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error":   "Failed to fetch alert",
			"details": err.Error(),
		})
	}

	// Generate token
	token, err := h.generateToken()
	if err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error":   "Failed to generate token",
			"details": err.Error(),
		})
	}

	// Create verification token
	verificationToken := models.AlertVerificationToken{
		AlertID: alert.ID,
		Token:   token,
		Used:    false,
	}

	if err := h.db.Create(&verificationToken).Error; err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error":   "Failed to create verification token",
			"details": err.Error(),
		})
	}

	return c.JSON(fiber.Map{
		"message": "Verification token generated successfully",
		"token":   token,
		"alertId": alert.ID,
	})
}

// GetVerifiedAlertsCount returns count of verified alerts in the last hour
// @Summary Get verified alerts count
// @Description Get count of alerts verified in the last hour
// @Tags alerts
// @Accept json
// @Produce json
// @Success 200 {object} fiber.Map
// @Failure 500 {object} fiber.Map
// @Router /api/v1/alerts/verified/count [get]
func (h *AlertHandler) GetVerifiedAlertsCount(c *fiber.Ctx) error {
	var count int64

	if err := h.db.Model(&models.Alert{}).Where("is_verified = ? AND created_at >= ?", true, time.Now().Add(-1*time.Hour)).Count(&count).Error; err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error":   "Failed to fetch verified alerts count",
			"details": err.Error(),
		})
	}

	return c.JSON(fiber.Map{
		"count": count,
	})
}

// GetNotVerifiedAlertsCount returns count of unverified alerts in the last hour
// @Summary Get unverified alerts count
// @Description Get count of alerts not verified in the last hour
// @Tags alerts
// @Accept json
// @Produce json
// @Success 200 {object} fiber.Map
// @Failure 500 {object} fiber.Map
// @Router /api/v1/alerts/not-verified/count [get]
func (h *AlertHandler) GetNotVerifiedAlertsCount(c *fiber.Ctx) error {
	var count int64

	if err := h.db.Model(&models.Alert{}).Where("is_verified = ? AND created_at >= ?", false, time.Now().Add(-1*time.Hour)).Count(&count).Error; err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error":   "Failed to fetch unverified alerts count",
			"details": err.Error(),
		})
	}

	return c.JSON(fiber.Map{
		"count": count,
	})
}

// QueryAlerts handles custom alert queries based on verification status and time
// @Summary Query alerts
// @Description Query alerts based on verification status and time criteria
// @Tags alerts
// @Accept json
// @Produce json
// @Param query body map[string]string true "Query parameters"
// @Success 200 {array} models.Alert
// @Failure 400 {object} fiber.Map
// @Failure 500 {object} fiber.Map
// @Router /api/v1/alerts/query [post]
func (h *AlertHandler) QueryAlerts(c *fiber.Ctx) error {
	var input struct {
		Query string `json:"query"`
	}

	if err := c.BodyParser(&input); err != nil {
		return c.Status(fiber.StatusBadRequest).JSON(fiber.Map{
			"error":   "Invalid request body",
			"details": err.Error(),
		})
	}

	var alerts []models.Alert
	query := h.db.Model(&models.Alert{})

	switch input.Query {
	case "verified":
		query = query.Where("is_verified = ?", true)
	case "not_verified_1h":
		query = query.Where("is_verified = ? AND created_at <= ?", false, time.Now().Add(-1*time.Hour))
	case "not_verified_less_1h":
		query = query.Where("is_verified = ? AND created_at > ?", false, time.Now().Add(-1*time.Hour))
	case "not_verified_in_24h":
		query = query.Where("is_verified = ? AND created_at <= ?", false, time.Now().Add(-24*time.Hour))
	default:
		return c.Status(fiber.StatusBadRequest).JSON(fiber.Map{
			"error":         "Invalid query type",
			"valid_queries": []string{"verified", "not_verified_1h", "not_verified_less_1h", "not_verified_in_24h"},
		})
	}

	if err := query.Find(&alerts).Error; err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error":   "Failed to query alerts",
			"details": err.Error(),
		})
	}

	return c.JSON(alerts)
}
