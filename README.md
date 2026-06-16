# Smart Learning Hub

Smart Learning Hub is an interactive ecosystem designed to accelerate study habits and build lasting knowledge through courses, quizzes, flashcards, and live notes.

## Project Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/yourusername/smart-learning-hub.git
   cd smart-learning-hub
   ```

2. **Server Environment:**
   Ensure you have a local PHP development server running (like XAMPP, WAMP, Laravel Herd, or simply PHP's built-in server) and a MySQL/MariaDB database.

## Environment Configuration

1. **Create your configuration file:**
   Copy the example environment file to create your own local configuration:
   ```bash
   cp .env.example .env
   ```

2. **Configure credentials:**
   Open the new `.env` file and fill in your database and SMTP details.
   *(Note: The `.env` file is excluded from Git tracking to keep your secrets safe).*

## Database Import

1. Open your database management tool (e.g., phpMyAdmin, TablePlus, MySQL Workbench).
2. Create a new empty database (e.g., `smart_learning_hub`).
3. Import the `schema.sql` file provided in the repository root to create all required tables.
   Alternatively, you can run:
   ```bash
   mysql -u root -p smart_learning_hub < schema.sql
   ```

## Running the Application Locally

Start your local PHP server. If you are using PHP's built-in web server, run this from the project root:
```bash
php -S localhost:8000
```
Then, open your browser and navigate to `http://localhost:8000`.

**Demo Accounts:**
- Student: `student@hub.com` / `student123`
- Admin: `admin@hub.com` / `admin123`
