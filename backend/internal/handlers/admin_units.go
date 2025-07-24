package handlers

import (
	"strconv"

	"github.com/alertsMIS/backend/internal/models"
	"github.com/gofiber/fiber/v2"
	"gorm.io/gorm"
)

// AdminUnitsHandler handles administrative units-related HTTP requests
type AdminUnitsHandler struct {
	db *gorm.DB
}

// NewAdminUnitsHandler creates a new AdminUnitsHandler
func NewAdminUnitsHandler(db *gorm.DB) *AdminUnitsHandler {
	return &AdminUnitsHandler{db: db}
}

// GetAllRegions fetches all regions
// @Summary Get all regions
// @Description Get all regions in the system
// @Tags admin-units
// @Accept json
// @Produce json
// @Success 200 {array} models.Region
// @Failure 500 {object} fiber.Map
// @Router /api/v1/admin-units/regions [get]
func (h *AdminUnitsHandler) GetAllRegions(c *fiber.Ctx) error {
	var regions []models.Region
	if err := h.db.Find(&regions).Error; err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error":   "Failed to fetch regions",
			"details": err.Error(),
		})
	}
	return c.JSON(regions)
}

// GetDistrictsByRegion fetches districts for a specific region
// @Summary Get districts by region
// @Description Get all districts for a specific region
// @Tags admin-units
// @Accept json
// @Produce json
// @Param region_id path int true "Region ID"
// @Success 200 {array} models.District
// @Failure 400 {object} fiber.Map
// @Failure 500 {object} fiber.Map
// @Router /api/v1/admin-units/regions/{region_id}/districts [get]
func (h *AdminUnitsHandler) GetDistrictsByRegion(c *fiber.Ctx) error {
	regionID := c.Params("region_id")
	regionIDUint, err := strconv.ParseUint(regionID, 10, 32)
	if err != nil {
		return c.Status(fiber.StatusBadRequest).JSON(fiber.Map{
			"error": "Invalid region ID",
		})
	}
	var districts []models.District
	if err := h.db.Where("region_id = ?", uint(regionIDUint)).Find(&districts).Error; err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error":   "Failed to fetch districts",
			"details": err.Error(),
		})
	}
	return c.JSON(districts)
}

// GetAllDistricts fetches all districts
// @Summary Get all districts
// @Description Get all districts in the system
// @Tags admin-units
// @Accept json
// @Produce json
// @Success 200 {array} models.District
// @Failure 500 {object} fiber.Map
// @Router /api/v1/admin-units/districts [get]
func (h *AdminUnitsHandler) GetAllDistricts(c *fiber.Ctx) error {
	var districts []models.District
	if err := h.db.Find(&districts).Error; err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error":   "Failed to fetch districts",
			"details": err.Error(),
		})
	}
	return c.JSON(districts)
}

// GetSubcountiesByDistrict fetches subcounties for a specific district
// @Summary Get subcounties by district
// @Description Get all subcounties for a specific district
// @Tags admin-units
// @Accept json
// @Produce json
// @Param district_id path int true "District ID"
// @Success 200 {array} models.Subcounty
// @Failure 400 {object} fiber.Map
// @Failure 500 {object} fiber.Map
// @Router /api/v1/admin-units/districts/{district_id}/subcounties [get]
func (h *AdminUnitsHandler) GetSubcountiesByDistrict(c *fiber.Ctx) error {
	districtID := c.Params("district_id")
	districtIDUint, err := strconv.ParseUint(districtID, 10, 32)
	if err != nil {
		return c.Status(fiber.StatusBadRequest).JSON(fiber.Map{
			"error": "Invalid district ID",
		})
	}
	var subcounties []models.Subcounty
	if err := h.db.Where("district_id = ?", uint(districtIDUint)).Find(&subcounties).Error; err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error":   "Failed to fetch subcounties",
			"details": err.Error(),
		})
	}
	return c.JSON(subcounties)
}

// GetAllSubcounties fetches all subcounties
// @Summary Get all subcounties
// @Description Get all subcounties in the system
// @Tags admin-units
// @Accept json
// @Produce json
// @Success 200 {array} models.Subcounty
// @Failure 500 {object} fiber.Map
// @Router /api/v1/admin-units/subcounties [get]
func (h *AdminUnitsHandler) GetAllSubcounties(c *fiber.Ctx) error {
	var subcounties []models.Subcounty
	if err := h.db.Find(&subcounties).Error; err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error":   "Failed to fetch subcounties",
			"details": err.Error(),
		})
	}
	return c.JSON(subcounties)
}
