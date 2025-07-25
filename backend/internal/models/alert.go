package models

import (
	"time"

	"gorm.io/gorm"
)

// Alert represents a disease alert in the system
type Alert struct {
	ID                         uint           `gorm:"primarykey" json:"id"`
	Status                     *string        `gorm:"size:50" json:"status"`
	Date                       *time.Time     `json:"date"`
	Time                       *time.Time     `json:"time"`
	CallTaker                  *string        `gorm:"size:255" json:"callTaker"`
	CIFNo                      *string        `gorm:"size:255" json:"cifNo"`
	PersonReporting            *string        `gorm:"size:255" json:"personReporting"`
	Village                    *string        `gorm:"size:255" json:"village"`
	SubCounty                  *string        `gorm:"size:255" json:"subCounty"`
	ContactNumber              *string        `gorm:"size:255" json:"contactNumber"`
	SourceOfAlert              *string        `gorm:"size:255" json:"sourceOfAlert"`
	AlertCaseName              *string        `gorm:"size:255" json:"alertCaseName"`
	AlertCaseAge               *int           `json:"alertCaseAge"`
	AlertCaseSex               *string        `gorm:"size:50" json:"alertCaseSex"`
	AlertCasePregnantDuration  *int           `json:"alertCasePregnantDuration"`
	AlertCaseVillage           *string        `gorm:"size:255" json:"alertCaseVillage"`
	AlertCaseParish            *string        `gorm:"size:255" json:"alertCaseParish"`
	AlertCaseSubCounty         *string        `gorm:"size:255" json:"alertCaseSubCounty"`
	AlertCaseDistrict          *string        `gorm:"size:255" json:"alertCaseDistrict"`
	AlertCaseNationality       *string        `gorm:"size:255" json:"alertCaseNationality"`
	PointOfContactName         *string        `gorm:"size:255" json:"pointOfContactName"`
	PointOfContactRelationship *string        `gorm:"size:255" json:"pointOfContactRelationship"`
	PointOfContactPhone        *string        `gorm:"size:255" json:"pointOfContactPhone"`
	History                    *string        `gorm:"type:text" json:"history"`
	HealthFacilityVisit        *string        `gorm:"size:255" json:"healthFacilityVisit"`
	TraditionalHealerVisit     *string        `gorm:"size:255" json:"traditionalHealerVisit"`
	Symptoms                   *string        `gorm:"type:text" json:"symptoms"`
	Actions                    *string        `gorm:"type:text" json:"actions"`
	CaseVerificationDesk       *string        `gorm:"type:text" json:"caseVerificationDesk"`
	FieldVerification          *string        `gorm:"type:text" json:"fieldVerification"`
	FieldVerificationDecision  *string        `gorm:"type:text" json:"fieldVerificationDecision"`
	Feedback                   *string        `gorm:"type:text" json:"feedback"`
	LabResult                  *string        `gorm:"size:10" json:"labResult"`
	LabResultDate              *time.Time     `json:"labResultDate"`
	IsHighlighted              bool           `gorm:"default:false" json:"isHighlighted"`
	AssignedTo                 *string        `gorm:"size:20" json:"assignedTo"`
	AlertReportedBefore        *string        `gorm:"size:5" json:"alertReportedBefore"`
	AlertFrom                  *string        `gorm:"size:20" json:"alertFrom"`
	Verified                   *string        `gorm:"size:10" json:"verified"`
	Comments                   *string        `gorm:"size:255" json:"comments"`
	VerificationDate           *time.Time     `json:"verificationDate"`
	VerificationTime           *time.Time     `json:"verificationTime"`
	Response                   *string        `gorm:"type:text" json:"response"`
	Narrative                  *string        `gorm:"type:text" json:"narrative"`
	FacilityType               *string        `gorm:"type:text" json:"facilityType"`
	Facility                   *string        `gorm:"type:text" json:"facility"`
	IsVerified                 bool           `gorm:"default:false" json:"isVerified"`
	VerifiedBy                 *string        `gorm:"type:text" json:"verifiedBy"`
	Region                     *string        `gorm:"type:text" json:"region"`
	CreatedAt                  time.Time      `json:"createdAt"`
	UpdatedAt                  time.Time      `json:"updatedAt"`
	DeletedAt                  gorm.DeletedAt `gorm:"index" json:"-"`
}

// TableName specifies the table name for the Alert model
func (Alert) TableName() string {
	return "alerts"
}
