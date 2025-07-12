package models

import (
	"time"

	"gorm.io/gorm"
)

// User represents a system user
type User struct {
	ID          uint           `gorm:"primarykey" json:"id"`
	Username    string         `gorm:"size:50;uniqueIndex;not null" json:"username"`
	Password    string         `gorm:"size:255;not null" json:"-"`
	FirstName   string         `gorm:"size:25;not null" json:"firstName"`
	LastName    string         `gorm:"size:25;not null" json:"lastName"`
	OtherName   string         `gorm:"size:25" json:"otherName"`
	Email       string         `gorm:"size:25;uniqueIndex;not null" json:"email"`
	Affiliation string         `gorm:"size:50;not null" json:"affiliation"`
	UserType    string         `gorm:"size:20" json:"userType"`
	Level       string         `gorm:"size:20" json:"level"`
	CreatedAt   time.Time      `json:"createdAt"`
	UpdatedAt   time.Time      `json:"updatedAt"`
	DeletedAt   gorm.DeletedAt `gorm:"index" json:"-"`
}

// TableName specifies the table name for the User model
func (User) TableName() string {
	return "users"
}
