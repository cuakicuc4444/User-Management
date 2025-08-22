package main

import (
	"encoding/json"
	"fmt"
	"log"
	"net/http"
	"regexp"
	"strconv"
	"strings"
	"time"
)

type User struct {
	ID         int    `json:"id"`
	Username   string `json:"user_name"`
	FirstName  string `json:"first_name"`
	LastName   string `json:"last_name"`
	Email      string `json:"email"`
	Status     string `json:"status"`
	TimeCreate string `json:"time_create"`
	DeletedAt  string `json:"deleted_at,omitempty"`
}

type Account struct {
	ID        int    `json:"id"`
	UserName  string `json:"user_name"`
	Email     string `json:"email"`
	Password  string `json:"password"`
	CreatedAt string `json:"created_at"`
}

func enableCORS(w http.ResponseWriter, r *http.Request) bool {
	w.Header().Set("Access-Control-Allow-Origin", "*")
	w.Header().Set("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS")
	w.Header().Set("Access-Control-Allow-Headers", "Content-Type, Accept")
	if r.Method == "OPTIONS" {
		w.WriteHeader(http.StatusOK)
		return true
	}
	return false
}

var accounts []Account
var currentAccountID int = 1

func isAccountEmailValid(email string) bool {
	emailCheck := regexp.MustCompile(`^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$`)
	return emailCheck.MatchString(email)
}

func getAccounts(w http.ResponseWriter, _ *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(accounts)
}

func createAccount(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	var acc Account
	if err := json.NewDecoder(r.Body).Decode(&acc); err != nil {
		http.Error(w, "Invalid input", http.StatusBadRequest)
		return
	}
	if acc.UserName == "" {
		http.Error(w, "Username required", http.StatusBadRequest)
		return
	}
	if !isAccountEmailValid(acc.Email) {
		http.Error(w, "Invalid email", http.StatusBadRequest)
		return
	}
	for _, a := range accounts {
		if a.Email == acc.Email {
			http.Error(w, "Email already exists", http.StatusConflict)
			return
		}
		if a.UserName == acc.UserName {
			http.Error(w, "Username already exists", http.StatusConflict)
			return
		}
	}
	acc.ID = currentAccountID
	currentAccountID++
	acc.CreatedAt = time.Now().Format(time.RFC3339)
	accounts = append(accounts, acc)
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(acc)
}

func findAccountByID(id int) (*Account, int) {
	for i, a := range accounts {
		if a.ID == id {
			return &accounts[i], i
		}
	}
	return nil, -1
}

func updateAccount(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	idStr := strings.TrimPrefix(r.URL.Path, "/accounts/put/")
	id, err := strconv.Atoi(idStr)
	if err != nil {
		http.Error(w, "Invalid account ID", http.StatusBadRequest)
		return
	}
	acc, _ := findAccountByID(id)
	if acc == nil {
		http.Error(w, "Account not found", http.StatusNotFound)
		return
	}
	var input Account
	if err := json.NewDecoder(r.Body).Decode(&input); err != nil {
		http.Error(w, "Invalid input", http.StatusBadRequest)
		return
	}
	if input.Password == "" {
		http.Error(w, "Password required", http.StatusBadRequest)
		return
	}
	acc.Password = input.Password
	json.NewEncoder(w).Encode(acc)
}

func deleteAccount(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	idStr := strings.TrimPrefix(r.URL.Path, "/accounts/delete/")
	id, err := strconv.Atoi(idStr)
	if err != nil {
		http.Error(w, "Invalid account ID", http.StatusBadRequest)
		return
	}
	_, idx := findAccountByID(id)
	if idx == -1 {
		http.Error(w, "Account not found", http.StatusNotFound)
		return
	}
	accounts = append(accounts[:idx], accounts[idx+1:]...)
	json.NewEncoder(w).Encode(map[string]string{"message": "Account deleted successfully"})
}

var users []User
var currentID int = 1

func getUsers(w http.ResponseWriter, _ *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(users)
}

func isEmailValid(email string) bool {
	emailCheck := regexp.MustCompile(`^[a-zA-Z0-9]+(?:[@][a-zA-Z0-9]+)(?:[.][a-zA-Z0-9]+)+$`)
	return emailCheck.MatchString(email)
}

func createUser(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")

	var user User
	err := json.NewDecoder(r.Body).Decode(&user)
	if err != nil {
		http.Error(w, "Invalid input", http.StatusBadRequest)
		return
	}

	if user.Username == "" || user.FirstName == "" || user.LastName == "" || user.Email == "" {
		http.Error(w, "Missing required fields", http.StatusBadRequest)
		return
	}

	if !isEmailValid(user.Email) {
		http.Error(w, "Invalid email", http.StatusBadRequest)
		return
	}
	for _, u := range users {
		if u.DeletedAt == "" {
			if u.Username == user.Username {
				http.Error(w, "Username already exists", http.StatusConflict)
				return
			}
			if u.Email == user.Email {
				http.Error(w, "Email already exists", http.StatusConflict)
				return
			}
		}
	}
	user.ID = currentID
	currentID++
	user.TimeCreate = time.Now().Format(time.RFC3339)
	user.Status = "Active"
	users = append(users, user)
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(user)
}

func findUserByID(id int) (*User, int) {
	for i, u := range users {
		if u.ID == id {
			return &users[i], i
		}
	}
	return nil, -1
}

func updateUser(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")

	idStr := strings.TrimPrefix(r.URL.Path, "/users/put/")
	id, err := strconv.Atoi(idStr)
	if err != nil {
		http.Error(w, "Invalid user ID", http.StatusBadRequest)
		return
	}

	user, _ := findUserByID(id)
	if user == nil {
		http.Error(w, "User not found", http.StatusNotFound)
		return
	}

	var input User
	err = json.NewDecoder(r.Body).Decode(&input)
	if err != nil {
		http.Error(w, "Invalid input", http.StatusBadRequest)
		return
	}

	if input.Email != "" && !isEmailValid(input.Email) {
		http.Error(w, "Invalid email", http.StatusBadRequest)
		return
	}

	for _, u := range users {
		if u.ID != user.ID && u.DeletedAt == "" {
			if input.Username != "" && u.Username == input.Username {
				http.Error(w, "Username already exists", http.StatusConflict)
				return
			}
			if input.Email != "" && u.Email == input.Email {
				http.Error(w, "Email already exists", http.StatusConflict)
				return
			}
		}
	}

	if input.Username != "" {
		user.Username = input.Username
	}
	if input.FirstName != "" {
		user.FirstName = input.FirstName
	}
	if input.LastName != "" {
		user.LastName = input.LastName
	}
	if input.Email != "" {
		user.Email = input.Email
	}
	if input.Status != "" && (input.Status == "Active" || input.Status == "Inactive") {
		user.Status = input.Status
		if input.Status == "Active" {
			user.DeletedAt = ""
		}
	}
	json.NewEncoder(w).Encode(user)
}

func deleteUser(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")

	idStr := strings.TrimPrefix(r.URL.Path, "/users/delete/")
	id, err := strconv.Atoi(idStr)
	if err != nil {
		http.Error(w, "Invalid user ID", http.StatusBadRequest)
		return
	}

	user, _ := findUserByID(id)
	if user == nil {
		http.Error(w, "User not found", http.StatusNotFound)
		return
	}

	if user.DeletedAt != "" {
		http.Error(w, "User already deleted", http.StatusBadRequest)
		return
	}
	user.Status = "Inactive"
	user.DeletedAt = time.Now().Format(time.RFC3339)
	json.NewEncoder(w).Encode(map[string]string{"message": "User deleted successfully"})
}

func getDeletedUsers(w http.ResponseWriter, _ *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	var deleted []User
	for _, u := range users {
		if u.Status == "Inactive" {
			deleted = append(deleted, u)
		}
	}
	json.NewEncoder(w).Encode(deleted)
}

func main() {

	http.HandleFunc("/users/get", func(w http.ResponseWriter, r *http.Request) {
		if enableCORS(w, r) {
			return
		}
		if r.Method == "GET" {
			getUsers(w, r)
		} else {
			http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		}
	})

	http.HandleFunc("/users/add", func(w http.ResponseWriter, r *http.Request) {
		if enableCORS(w, r) {
			return
		}
		if r.Method == "POST" {
			createUser(w, r)
		} else {
			http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		}
	})

	http.HandleFunc("/users/put/", func(w http.ResponseWriter, r *http.Request) {
		if enableCORS(w, r) {
			return
		}
		if r.Method == "PUT" {
			updateUser(w, r)
		} else {
			http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		}
	})

	http.HandleFunc("/users/delete/", func(w http.ResponseWriter, r *http.Request) {
		if enableCORS(w, r) {
			return
		}
		if r.Method == "DELETE" {
			deleteUser(w, r)
		} else {
			http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		}
	})

	http.HandleFunc("/users/deleted", func(w http.ResponseWriter, r *http.Request) {
		if enableCORS(w, r) {
			return
		}
		if r.Method == "GET" {
			getDeletedUsers(w, r)
		} else {
			http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		}
	})

	http.HandleFunc("/accounts/get", func(w http.ResponseWriter, r *http.Request) {
		if enableCORS(w, r) {
			return
		}
		if r.Method == "GET" {
			getAccounts(w, r)
		} else {
			http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		}
	})

	http.HandleFunc("/accounts/add", func(w http.ResponseWriter, r *http.Request) {
		if enableCORS(w, r) {
			return
		}
		if r.Method == "POST" {
			createAccount(w, r)
		} else {
			http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		}
	})

	http.HandleFunc("/accounts/put/", func(w http.ResponseWriter, r *http.Request) {
		if enableCORS(w, r) {
			return
		}
		if r.Method == "PUT" {
			updateAccount(w, r)
		} else {
			http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		}
	})

	http.HandleFunc("/accounts/delete/", func(w http.ResponseWriter, r *http.Request) {
		if enableCORS(w, r) {
			return
		}
		if r.Method == "DELETE" {
			deleteAccount(w, r)
		} else {
			http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		}
	})

	fmt.Println("Server running at http://localhost:8080")
	log.Fatal(http.ListenAndServe(":8080", nil))
}
