package models

import (
	"time"

	"gorm.io/gorm"
)

// Alert represents a disease alert in the system
type Alert struct {
	ID                         uint           `gorm:"primarykey" json:"id"`
	Status                     string         `gorm:"size:50;not null" json:"status"`
	Date                       time.Time      `gorm:"not null" json:"date"`
	Time                       time.Time      `gorm:"not null" json:"time"`
	CallTaker                  string         `gorm:"size:255;not null" json:"callTaker"`
	CIFNo                      string         `gorm:"size:255;not null" json:"cifNo"`
	PersonReporting            string         `gorm:"size:255;not null" json:"personReporting"`
	Village                    string         `gorm:"size:255;not null" json:"village"`
	SubCounty                  string         `gorm:"size:255;not null" json:"subCounty"`
	ContactNumber              string         `gorm:"size:255;not null" json:"contactNumber"`
	SourceOfAlert              string         `gorm:"size:255;not null" json:"sourceOfAlert"`
	AlertCaseName              string         `gorm:"size:255;not null" json:"alertCaseName"`
	AlertCaseAge               int            `gorm:"not null" json:"alertCaseAge"`
	AlertCaseSex               string         `gorm:"size:50;not null" json:"alertCaseSex"`
	AlertCasePregnantDuration  int            `json:"alertCasePregnantDuration"`
	AlertCaseVillage           string         `gorm:"size:255;not null" json:"alertCaseVillage"`
	AlertCaseParish            string         `gorm:"size:255;not null" json:"alertCaseParish"`
	AlertCaseSubCounty         string         `gorm:"size:255;not null" json:"alertCaseSubCounty"`
	AlertCaseDistrict          string         `gorm:"size:255;not null" json:"alertCaseDistrict"`
	AlertCaseNationality       string         `gorm:"size:255;not null" json:"alertCaseNationality"`
	PointOfContactName         string         `gorm:"size:255;not null" json:"pointOfContactName"`
	PointOfContactRelationship string         `gorm:"size:255;not null" json:"pointOfContactRelationship"`
	PointOfContactPhone        string         `gorm:"size:255;not null" json:"pointOfContactPhone"`
	History                    string         `gorm:"type:text" json:"history"`
	HealthFacilityVisit        string         `gorm:"size:255;not null" json:"healthFacilityVisit"`
	TraditionalHealerVisit     string         `gorm:"size:255;not null" json:"traditionalHealerVisit"`
	Symptoms                   string         `gorm:"type:text" json:"symptoms"`
	Actions                    string         `gorm:"type:text;not null" json:"actions"`
	CaseVerificationDesk       string         `gorm:"type:text" json:"caseVerificationDesk"`
	FieldVerification          string         `gorm:"type:text" json:"fieldVerification"`
	FieldVerificationDecision  string         `gorm:"type:text" json:"fieldVerificationDecision"`
	Feedback                   string         `gorm:"type:text" json:"feedback"`
	LabResult                  string         `gorm:"size:10" json:"labResult"`
	LabResultDate              *time.Time     `json:"labResultDate"`
	IsHighlighted              bool           `gorm:"default:false" json:"isHighlighted"`
	AssignedTo                 string         `gorm:"size:20" json:"assignedTo"`
	AlertReportedBefore        string         `gorm:"size:5;not null" json:"alertReportedBefore"`
	AlertFrom                  string         `gorm:"size:20" json:"alertFrom"`
	Verified                   string         `gorm:"size:10" json:"verified"`
	Comments                   string         `gorm:"size:255" json:"comments"`
	VerificationDate           *time.Time     `json:"verificationDate"`
	VerificationTime           *time.Time     `json:"verificationTime"`
	Response                   string         `gorm:"type:text" json:"response"`
	Narrative                  string         `gorm:"type:text" json:"narrative"`
	FacilityType               string         `gorm:"type:text" json:"facilityType"`
	Facility                   string         `gorm:"type:text" json:"facility"`
	IsVerified                 bool           `gorm:"default:false" json:"isVerified"`
	VerifiedBy                 string         `gorm:"type:text" json:"verifiedBy"`
	Region                     string         `gorm:"type:text" json:"region"`
	CreatedAt                  time.Time      `json:"createdAt"`
	UpdatedAt                  time.Time      `json:"updatedAt"`
	DeletedAt                  gorm.DeletedAt `gorm:"index" json:"-"`
}

// TableName specifies the table name for the Alert model
func (Alert) TableName() string {
	return "alerts"
}
