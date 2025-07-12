package handlers

import (
	"time"

	"github.com/alertsMIS/backend/internal/models"
	"github.com/gofiber/fiber/v2"
	"github.com/golang-jwt/jwt/v5"
	"golang.org/x/crypto/bcrypt"
	"gorm.io/gorm"
)

// UserHandler handles user-related HTTP requests
type UserHandler struct {
	db        *gorm.DB
	jwtSecret string
}

// NewUserHandler creates a new UserHandler
func NewUserHandler(db *gorm.DB, jwtSecret string) *UserHandler {
	return &UserHandler{
		db:        db,
		jwtSecret: jwtSecret,
	}
}

// verifyPassword handles password verification with PHP bcrypt compatibility
func (h *UserHandler) verifyPassword(hashedPassword, plainPassword string) error {
	// Standard bcrypt comparison - this should work with PHP-generated hashes
	// PHP password_hash() with PASSWORD_DEFAULT uses bcrypt with cost 10
	return bcrypt.CompareHashAndPassword([]byte(hashedPassword), []byte(plainPassword))
}

// Register handles user registration
// @Summary Register a new user
// @Description Create a new user account
// @Tags users
// @Accept json
// @Produce json
// @Param user body models.User true "User object"
// @Success 201 {object} models.User
// @Router /api/v1/users/register [post]
func (h *UserHandler) Register(c *fiber.Ctx) error {
	user := new(models.User)

	if err := c.BodyParser(user); err != nil {
		return c.Status(fiber.StatusBadRequest).JSON(fiber.Map{
			"error": "Invalid request body",
		})
	}

	// Hash password
	hashedPassword, err := bcrypt.GenerateFromPassword([]byte(user.Password), bcrypt.DefaultCost)
	if err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error": "Failed to hash password",
		})
	}
	user.Password = string(hashedPassword)

	if err := h.db.Create(user).Error; err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error": "Failed to create user",
		})
	}

	// Remove password from response
	user.Password = ""

	return c.Status(fiber.StatusCreated).JSON(user)
}

// Login handles user authentication
// @Summary Login user
// @Description Authenticate a user and return JWT token
// @Tags users
// @Accept json
// @Produce json
// @Param credentials body map[string]string true "Login credentials"
// @Success 200 {object} fiber.Map
// @Router /api/v1/users/login [post]
func (h *UserHandler) Login(c *fiber.Ctx) error {
	var input struct {
		Username string `json:"username"`
		Password string `json:"password"`
	}

	if err := c.BodyParser(&input); err != nil {
		return c.Status(fiber.StatusBadRequest).JSON(fiber.Map{
			"error": "Invalid request body",
		})
	}

	// Query using the exact field names from the PHP system
	var user struct {
		ID          uint   `json:"id"`
		Username    string `json:"username"`
		Password    string `json:"-"`
		Email       string `json:"email"`
		Affiliation string `json:"affiliation"`
		UserType    string `json:"userType"`
		Level       string `json:"level"`
	}

	// Use First() to check if user exists
	result := h.db.Raw("SELECT id, username, password, email, affiliation, user_type, level FROM users WHERE username = ?", input.Username).First(&user)
	if result.Error != nil {
		if result.Error.Error() == "record not found" {
			return c.Status(fiber.StatusUnauthorized).JSON(fiber.Map{
				"error": "Invalid credentials",
				"debug": fiber.Map{
					"message":  "User not found",
					"username": input.Username,
				},
			})
		}
		return c.Status(fiber.StatusUnauthorized).JSON(fiber.Map{
			"error": "Invalid credentials",
		})
	}

	// Check if password field is empty
	if user.Password == "" {
		return c.Status(fiber.StatusUnauthorized).JSON(fiber.Map{
			"error": "Invalid credentials",
			"debug": fiber.Map{
				"message":  "User found but password field is empty",
				"username": input.Username,
			},
		})
	}

	// Try to verify password with bcrypt
	err := h.verifyPassword(user.Password, input.Password)
	if err != nil {
		// Temporary debug information
		debugInfo := fiber.Map{
			"hash_length":  len(user.Password),
			"bcrypt_error": err.Error(),
			"username":     input.Username,
		}

		// Only add hash prefix if password is not empty
		if len(user.Password) > 10 {
			debugInfo["hash_prefix"] = user.Password[:10] + "..."
		} else {
			debugInfo["hash_prefix"] = user.Password
		}

		return c.Status(fiber.StatusUnauthorized).JSON(fiber.Map{
			"error": "Invalid credentials",
			"debug": debugInfo,
		})
	}

	// Create JWT token
	token := jwt.New(jwt.SigningMethodHS256)
	claims := token.Claims.(jwt.MapClaims)
	claims["user_id"] = user.ID
	claims["username"] = user.Username
	claims["exp"] = time.Now().Add(time.Hour * 24).Unix()

	tokenString, err := token.SignedString([]byte(h.jwtSecret))
	if err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error": "Failed to generate token",
		})
	}

	return c.JSON(fiber.Map{
		"token": tokenString,
		"user": fiber.Map{
			"id":          user.ID,
			"username":    user.Username,
			"email":       user.Email,
			"affiliation": user.Affiliation,
			"userType":    user.UserType,
			"level":       user.Level,
		},
	})
}

// GetProfile handles retrieving user profile
// @Summary Get user profile
// @Description Get the authenticated user's profile
// @Tags users
// @Produce json
// @Security Bearer
// @Success 200 {object} models.User
// @Router /api/v1/users/profile [get]
func (h *UserHandler) GetProfile(c *fiber.Ctx) error {
	userID := c.Locals("user_id").(uint)

	var user models.User
	if err := h.db.First(&user, userID).Error; err != nil {
		return c.Status(fiber.StatusNotFound).JSON(fiber.Map{
			"error": "User not found",
		})
	}

	// Remove password from response
	user.Password = ""

	return c.JSON(user)
}

// Get all users
// @Router /api/v1/users/all [get]
func (h *UserHandler) GetAllUsers(c *fiber.Ctx) error {
	var users []models.User
	if err := h.db.Find(&users).Error; err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error": "Failed to fetch users",
		})
	}
	return c.JSON(users)
}

// Get user by id
// @Router /api/v1/users/id [get]
func (h *UserHandler) GetUserById(c *fiber.Ctx) error {
	userID := c.Params("id")
	var user models.User
	if err := h.db.First(&user, userID).Error; err != nil {
		return c.Status(fiber.StatusNotFound).JSON(fiber.Map{
			"error": "User not found",
		})
	}
	return c.JSON(user)
}

// DebugUsers is a temporary endpoint to debug user data
func (h *UserHandler) DebugUsers(c *fiber.Ctx) error {
	// Use raw SQL to query the users table with PHP field names
	var users []struct {
		ID          uint   `json:"id"`
		Username    string `json:"username"`
		Password    string `json:"-"`
		Email       string `json:"email"`
		Affiliation string `json:"affiliation"`
		UserType    string `json:"userType"`
		Level       string `json:"level"`
	}

	err := h.db.Raw("SELECT id, username, password, email, affiliation, user_type, level FROM users").Scan(&users).Error
	if err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error": "Failed to fetch users",
		})
	}

	// Create a safe version without passwords
	var safeUsers []fiber.Map
	for _, user := range users {
		userInfo := fiber.Map{
			"id":              user.ID,
			"username":        user.Username,
			"email":           user.Email,
			"password_length": len(user.Password),
		}

		// Only add hash prefix if password is not empty
		if len(user.Password) > 10 {
			userInfo["password_prefix"] = user.Password[:10] + "..."
		} else {
			userInfo["password_prefix"] = user.Password
		}

		safeUsers = append(safeUsers, userInfo)
	}

	return c.JSON(fiber.Map{
		"users": safeUsers,
		"count": len(users),
	})
}

// TestBcrypt is a temporary endpoint to test bcrypt functionality
func (h *UserHandler) TestBcrypt(c *fiber.Ctx) error {
	// Test with a known password
	testPassword := "test123"

	// Generate a hash
	hash, err := bcrypt.GenerateFromPassword([]byte(testPassword), bcrypt.DefaultCost)
	if err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error": "Failed to generate hash",
		})
	}

	// Verify the hash
	err = bcrypt.CompareHashAndPassword(hash, []byte(testPassword))
	if err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{
			"error": "Failed to verify hash",
		})
	}

	// Test with wrong password
	err = bcrypt.CompareHashAndPassword(hash, []byte("wrongpassword"))

	return c.JSON(fiber.Map{
		"hash":                        string(hash),
		"hash_length":                 len(hash),
		"test_password":               testPassword,
		"verification_success":        true,
		"wrong_password_verification": err != nil,
	})
}

// Logout handles user logout
// @Router /api/v1/users/logout [post]
func (h *UserHandler) Logout(c *fiber.Ctx) error {
	// Get user info from context (set by auth middleware)
	userID := c.Locals("user_id").(uint)
	username := c.Locals("username").(string)

	return c.JSON(fiber.Map{
		"message":   "Logout successful",
		"user_id":   userID,
		"username":  username,
		"timestamp": time.Now().Unix(),
	})
}
