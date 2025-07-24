package models

type Region struct {
	ID        uint   `gorm:"primaryKey" json:"id"`
	RegionUID string `gorm:"size:20;not null" json:"regionUid"`
	Region    string `gorm:"size:50;not null" json:"region"`
}

func (Region) TableName() string { return "regions" }

type District struct {
	ID          uint   `gorm:"primaryKey" json:"id"`
	DistrictUID string `gorm:"size:20;not null" json:"districtUid"`
	District    string `gorm:"size:50;not null" json:"district"`
	RegionID    uint   `json:"regionId"`
}

func (District) TableName() string { return "districts" }

type Subcounty struct {
	ID           uint   `gorm:"primaryKey" json:"id"`
	SubcountyUID string `gorm:"size:20;not null" json:"subcountyUid"`
	Subcounty    string `gorm:"size:50;not null" json:"subcounty"`
	DistrictID   uint   `json:"districtId"`
}

func (Subcounty) TableName() string { return "subcounties" }
