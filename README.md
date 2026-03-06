# Reshape IDE

<p align="center">
  <strong>Logic-Focused Educational IDE - Shape your Thinking</strong>
</p>

<p align="center">
  An interactive web-based programming learning platform with code execution, hints, progress tracking, and scoring.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.x+-777BB4?style=flat&logo=php" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-4479A1?style=flat&logo=mysql" alt="MySQL">
  <img src="https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=flat&logo=javascript" alt="JavaScript">
  <img src="https://img.shields.io/badge/License-MIT-green" alt="License">
</p>

---

## 📋 Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Configuration](#configuration)
- [Running the Application](#running-the-application)
- [Deployment](#deployment)
- [API Endpoints](#api-endpoints)
- [Database Schema](#database-schema)
- [Admin Panel](#admin-panel)
- [Contributing](#contributing)
- [License](#license)

---

## ✨ Features

- **Interactive Code Editor** - Write and execute code in a browser-based editor
- **Lesson System** - Structured programming lessons with varying difficulty levels (Easy, Medium, Hard)
- **Hint Engine** - Progressive hints system that rewards completion
- **Progress Tracking** - Track completed lessons, scores, and hints used
- **User Authentication** - Secure signup/login with JWT tokens
- **Scoring System** - Earn points for completing lessons
- **Admin Panel** - Create lessons, manage users, view analytics
- **Responsive Design** - Works on desktop and mobile browsers

---

## 🛠 Tech Stack

| Layer | Technology |
|-------|------------|
| **Backend** | PHP 8.x |
| **Database** | MySQL 8.x |
| **Frontend** | HTML5, CSS3, Vanilla JavaScript |
| **Authentication** | JWT (JSON Web Tokens) |
| **Deployment** | Vercel, PHP built-in server |

---

## 📂 Project Structure

```
reshape/
├── api/                        # PHP API endpoints
│   ├── admin-create-lesson.php # Admin: Create new lesson
│   ├── admin-login.php         # Admin authentication
│   ├── admin-users.php         # Admin: Manage users
│   ├── get-hint.php            # Get hints for lessons
│   ├── get-lesson.php          # Get lesson details
│   ├── login.php               # User login
│   ├── merge-progress.php      # Merge progress data
│   ├── progress.php            # Get user progress
│   ├── signup.php              # User registration
│   ├── submit-score.php        # Submit lesson solution
│   ├── user.php                # User profile
│   ├── validate.php            # Validate code solutions
│   └── lib/                    # Shared libraries
│       ├── db.php              # Database connection
│       └── jwt.php             # JWT utilities
├── public/                     # Frontend static files
│   ├── index.html              # Main application
│   ├── css/
│   │   └── style.css           # Application styles
│   └── js/
│       ├── app.js              # Main application logic
│       ├── editor.js           # Code editor functionality
│       └── hint-engine.js      # Hint system
├── sql/                        # Database scripts
│   ├── init_tables.sql         # Database schema
│   └── init_lessons_hints.sql  # Sample data
├── src/                        # Source code (MVC)
│   ├── autoload.php            # Class autoloader
│   ├── config/                 # Configuration
│   │   ├── app.php             # App configuration
│   │   └── database.php        # Database config
│   ├── lib/                    # Libraries
│   │   └── Jwt.php             # JWT class
│   └── models/                 # Data models
│       ├── LessonModel.php
│       ├── ProgressModel.php
│       └── UserModel.php
├── .env.example                # Environment variables template
├── .env.local                  # Local environment (gitignored)
├── router.php                  # PHP router for built-in server
├── setup.sh                    # Linux/Mac setup script
├── setup.bat                   # Windows setup script
├── run.sh                      # Run development server
└── vercel.json                 # Vercel deployment config
```

---

## 🔧 Prerequisites

- **PHP 8.0 or higher**
- **MySQL 8.0 or higher**
- **Composer** (optional, for autoloading)
- **Vercel CLI** (for Vercel deployment)

---

## 📥 Installation

### 1. Clone the Repository

```bash
git clone https://github.com/effazrayhan/reshape-ide.git
cd reshape-ide
```

### 2. Configure Environment Variables

Copy the example environment file:

```bash
cp .env.example .env.local
```

Edit `.env.local` with your database credentials:

```env
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=reshape_ide
DB_USER=root
DB_PASS=your_password

# Application Settings
APP_ENV=local

# JWT Configuration
JWT_SECRET=your-256-bit-secret-key-change-in-production
JWT_EXPIRY=86400

# Admin Credentials
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=secure_password
```

### 3. Set Up the Database

Create the database and import the schema:

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE reshape_ide;"

# Import tables
mysql -u root -p reshape_ide < sql/init_tables.sql

# Import sample lessons (optional)
mysql -u root -p reshape_ide < sql/init_lessons_hints.sql
```

### 4. Run Setup Script (Optional)

```bash
# Linux/Mac
chmod +x setup.sh
./setup.sh

# Windows
setup.bat
```

---

## 🚀 Running the Application

### Development Server (PHP Built-in)

```bash
# Using the run script
./run.sh

# Or manually
php -S localhost:3699 router.php
```

Then open [http://localhost:3699](http://localhost:3699) in your browser.

### With MySQL

Ensure MySQL is running and accessible with the credentials in your `.env.local`.

---

## ☁️ Deployment

### Vercel (Recommended)

1. Install Vercel CLI:
   ```bash
   npm install -g vercel
   ```

2. Deploy:
   ```bash
   vercel
   ```

3. Set environment variables in Vercel dashboard:
   - `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
   - `JWT_SECRET`, `JWT_EXPIRY`
   - `ADMIN_EMAIL`, `ADMIN_PASSWORD`

### Traditional Web Hosting

1. Upload files to your web server
2. Configure your web server (Apache/Nginx) to point to `public/`
3. Ensure the `api/` directory is accessible via `/api/` route
4. Set up environment variables

---

## 🔌 API Endpoints

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/login.php` | User login |
| POST | `/api/signup.php` | User registration |
| POST | `/api/admin-login.php` | Admin login |

### Lessons

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/get-lesson.php` | Get lesson by ID |
| POST | `/api/admin-create-lesson.php` | Create new lesson (admin) |

### Progress & Scoring

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/progress.php` | Get user progress |
| POST | `/api/submit-score.php` | Submit solution & get score |
| POST | `/api/merge-progress.php` | Sync progress data |

### Hints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/get-hint.php` | Get hint for a lesson |

### Admin

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin-users.php` | List all users (admin) |
| GET | `/api/user.php` | Get user details |

---

## 🗄 Database Schema

### Tables

- **users** - Registered users (email, username, password_hash)
- **lessons** - Programming lessons (title, difficulty, description, starter_code, solution, points)
- **hints** - Hints associated with lessons
- **test_cases** - Test cases for validating solutions
- **user_progress** - User lesson progress (score, hints_used, completed_at)
- **user_scores** - Aggregate user scores (total_score, lessons_completed)

### ER Diagram

```
users 1──∞ user_progress ∞──1 lessons
         └── user_scores

lessons 1──∞ hints
lessons 1──∞ test_cases
```

---

## 👨‍💻 Admin Panel

Access the admin panel by clicking the "Admin" button on the navigation bar after logging in with admin credentials.

### Features

- **User Management** - View all registered users
- **Lesson Creation** - Create new lessons with:
  - Title and difficulty
  - Problem description (HTML)
  - Starter code
  - Solution code
  - Points value
  - Hints (multiple)
  - Test cases (JSON format)

### Example Test Cases Format

```json
[
  {"input": [1, 2], "expected": 3},
  {"input": [5, 3], "expected": 8},
  {"input": [-1, 1], "expected": 0}
]
```

---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 🙏 Acknowledgments

- Built with PHP and Vanilla JavaScript
- Designed for educational purposes
- Inspired by platforms like LeetCode and Codecademy
