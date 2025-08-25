package main

import (
	"crypto/md5"
	"database/sql"
	"encoding/json"
	"fmt"
	"log"
	"net/http"
	"regexp"
	"strconv"
	"strings"
	"time"

	_ "github.com/go-sql-driver/mysql"
)

type User struct {
	ID         int        `json:"id"`
	UserName   string     `json:"user_name"`
	FirstName  string     `json:"first_name"`
	LastName   string     `json:"last_name"`
	Email      string     `json:"email"`
	Status     string     `json:"status"`
	TimeCreate time.Time  `json:"time_create"`
	DeletedAt  *time.Time `json:"deleted_at,omitempty"`
}
type Account struct {
	ID        int       `json:"id"`
	Status    string    `json:"status"`
	UserName  string    `json:"user_name"`
	Email     string    `json:"email"`
	Password  string    `json:"password"`
	CreatedAt time.Time `json:"created_at"`
}

var db *sql.DB

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

func isAccountEmailValid(email string) bool {
	emailCheck := regexp.MustCompile(`^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$`)
	return emailCheck.MatchString(email)
}

func getAccounts(w http.ResponseWriter, _ *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	rows, err := db.Query("SELECT id, user_name, email, password, status, created_at FROM accounts")
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	defer rows.Close()
	var accounts []Account
	for rows.Next() {
		var acc Account
		if err := rows.Scan(&acc.ID, &acc.UserName, &acc.Email, &acc.Password, &acc.Status, &acc.CreatedAt); err != nil {
			http.Error(w, err.Error(), http.StatusInternalServerError)
			return
		}
		accounts = append(accounts, acc)
	}
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
	var exists int
	err := db.QueryRow("SELECT COUNT(*) FROM accounts WHERE email=? OR user_name=?", acc.Email, acc.UserName).Scan(&exists)
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	if exists > 0 {
		http.Error(w, "Email or Username already exists", http.StatusConflict)
		return
	}
	hashedPassword := fmt.Sprintf("%x", md5.Sum([]byte(acc.Password)))
	status := acc.Status
	if status == "" {
		status = "active"
	}
	stmt, err := db.Prepare("INSERT INTO accounts (user_name, email, password, status) VALUES (?, ?, ?, ?)")
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	res, err := stmt.Exec(acc.UserName, acc.Email, hashedPassword, status)
	if err != nil {
		http.Error(w, err.Error(), http.StatusBadRequest)
		return
	}
	id, _ := res.LastInsertId()
	acc.ID = int(id)
	acc.Status = status
	acc.CreatedAt = time.Now()
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(acc)
}

func updateAccount(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	idStr := strings.TrimPrefix(r.URL.Path, "/accounts/put/")
	id, err := strconv.Atoi(idStr)
	if err != nil {
		http.Error(w, "Invalid account ID", http.StatusBadRequest)
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
	hashedPassword := fmt.Sprintf("%x", md5.Sum([]byte(input.Password)))
	stmt, err := db.Prepare("UPDATE accounts SET password=? WHERE id=?")
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	_, err = stmt.Exec(hashedPassword, id)
	if err != nil {
		http.Error(w, err.Error(), http.StatusBadRequest)
		return
	}
	json.NewEncoder(w).Encode(input)
}

func deleteAccount(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	idStr := strings.TrimPrefix(r.URL.Path, "/accounts/delete/")
	id, err := strconv.Atoi(idStr)
	if err != nil {
		http.Error(w, "Invalid account ID", http.StatusBadRequest)
		return
	}
	stmt, err := db.Prepare("DELETE FROM accounts WHERE id=?")
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	_, err = stmt.Exec(id)
	if err != nil {
		http.Error(w, err.Error(), http.StatusBadRequest)
		return
	}
	json.NewEncoder(w).Encode(map[string]string{"message": "Account deleted successfully"})
}

func getUsers(w http.ResponseWriter, _ *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	rows, err := db.Query("SELECT id, user_name, first_name, last_name, email, status, time_create, deleted_at FROM users")
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	defer rows.Close()
	var users []User
	for rows.Next() {
		var u User
		var deletedAt sql.NullTime
		if err := rows.Scan(&u.ID, &u.UserName, &u.FirstName, &u.LastName, &u.Email, &u.Status, &u.TimeCreate, &deletedAt); err != nil {
			http.Error(w, err.Error(), http.StatusInternalServerError)
			return
		}
		if deletedAt.Valid {
			u.DeletedAt = &deletedAt.Time
		}
		users = append(users, u)
	}
	json.NewEncoder(w).Encode(users)
}

func isEmailValid(email string) bool {
	emailCheck := regexp.MustCompile(`^[a-zA-Z0-9]+(?:[@][a-zA-Z0-9]+)(?:[.][a-zA-Z0-9]+)+$`)
	return emailCheck.MatchString(email)
}

func createUser(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	var user User
	if err := json.NewDecoder(r.Body).Decode(&user); err != nil {
		http.Error(w, "Invalid input", http.StatusBadRequest)
		return
	}
	if user.UserName == "" || user.FirstName == "" || user.LastName == "" || user.Email == "" {
		http.Error(w, "Missing required fields", http.StatusBadRequest)
		return
	}
	if !isEmailValid(user.Email) {
		http.Error(w, "Invalid email", http.StatusBadRequest)
		return
	}

	var exists int
	err := db.QueryRow("SELECT COUNT(*) FROM users WHERE user_name=?", user.UserName).Scan(&exists)
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	if exists > 0 {
		http.Error(w, "Username already exists", http.StatusConflict)
		return
	}
	err = db.QueryRow("SELECT COUNT(*) FROM users WHERE email=?", user.Email).Scan(&exists)
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	if exists > 0 {
		http.Error(w, "Email already exists", http.StatusConflict)
		return
	}
	stmt, err := db.Prepare("INSERT INTO users (user_name, first_name, last_name, email, status) VALUES (?, ?, ?, ?, ?)")
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	res, err := stmt.Exec(user.UserName, user.FirstName, user.LastName, user.Email, user.Status)
	if err != nil {
		http.Error(w, err.Error(), http.StatusBadRequest)
		return
	}
	id, _ := res.LastInsertId()
	user.ID = int(id)
	user.TimeCreate = time.Now()
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(user)
}

func updateUser(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	idStr := strings.TrimPrefix(r.URL.Path, "/users/put/")
	id, err := strconv.Atoi(idStr)
	if err != nil {
		http.Error(w, "Invalid user ID", http.StatusBadRequest)
		return
	}
	var input User
	if err := json.NewDecoder(r.Body).Decode(&input); err != nil {
		http.Error(w, "Invalid input", http.StatusBadRequest)
		return
	}
	if input.Email != "" && !isEmailValid(input.Email) {
		http.Error(w, "Invalid email", http.StatusBadRequest)
		return
	}
	var exists int
	err = db.QueryRow("SELECT COUNT(*) FROM users WHERE (user_name=? OR email=?) AND id<>? AND deleted_at IS NULL", input.UserName, input.Email, id).Scan(&exists)
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	if exists > 0 {
		http.Error(w, "Email or Username already exists", http.StatusConflict)
		return
	}
	stmt, err := db.Prepare("UPDATE users SET user_name=?, first_name=?, last_name=?, email=?, status=? WHERE id=?")
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	_, err = stmt.Exec(input.UserName, input.FirstName, input.LastName, input.Email, input.Status, id)
	if err != nil {
		http.Error(w, err.Error(), http.StatusBadRequest)
		return
	}
	json.NewEncoder(w).Encode(input)
}

func deleteUser(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	idStr := strings.TrimPrefix(r.URL.Path, "/users/delete/")
	id, err := strconv.Atoi(idStr)
	if err != nil {
		http.Error(w, "Invalid user ID", http.StatusBadRequest)
		return
	}
	stmt, err := db.Prepare("UPDATE users SET status='Inactive', deleted_at=NOW() WHERE id=?")
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	_, err = stmt.Exec(id)
	if err != nil {
		http.Error(w, err.Error(), http.StatusBadRequest)
		return
	}
	json.NewEncoder(w).Encode(map[string]string{"message": "User deleted successfully"})
}

func getDeletedUsers(w http.ResponseWriter, _ *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	rows, err := db.Query("SELECT id, user_name, first_name, last_name, email, status, time_create, deleted_at FROM users WHERE status='Inactive'")
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	defer rows.Close()
	var deleted []User
	for rows.Next() {
		var u User
		var deletedAt sql.NullTime
		if err := rows.Scan(&u.ID, &u.UserName, &u.FirstName, &u.LastName, &u.Email, &u.Status, &u.TimeCreate, &deletedAt); err != nil {
			http.Error(w, err.Error(), http.StatusInternalServerError)
			return
		}
		if deletedAt.Valid {
			u.DeletedAt = &deletedAt.Time
		}
		deleted = append(deleted, u)
	}
	json.NewEncoder(w).Encode(deleted)
}

func main() {
	var err error
	db, err = sql.Open("mysql", "root:@tcp(localhost:3306)/userdb?parseTime=true")
	if err != nil {
		log.Fatal(err)
	}
	defer db.Close()

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
