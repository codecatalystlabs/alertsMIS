package models

import (
	"time"

	"gorm.io/gorm"
)

// AlertVerificationToken represents a token used for verifying alerts
type AlertVerificationToken struct {
	ID        uint           `gorm:"primarykey" json:"id"`
	AlertID   uint           `gorm:"not null;index" json:"alertId"`
	Token     string         `gorm:"size:255;not null;uniqueIndex" json:"token"`
	Used      bool           `gorm:"default:false" json:"used"`
	CreatedAt time.Time      `json:"createdAt"`
	UsedAt    *time.Time     `json:"usedAt"`
	DeletedAt gorm.DeletedAt `gorm:"index" json:"-"`
}

// TableName specifies the table name for the AlertVerificationToken model
func (AlertVerificationToken) TableName() string {
	return "alert_verification_tokens"
}
