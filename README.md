ERM for Cleaning Business

## 🚀 Getting Started

These instructions will get you a copy of the project up and running on your local machine for development and testing purposes.

### Prerequisites
* PHP (version 7.4 or higher recommended)
* Composer
* MySQL (or compatible MariaDB)
* A web server (Apache, Nginx, or use PHP's built-in server for development)

### Installation
1.  **Clone the repository:**
    ```bash
    git clone [your-repository-url]
    cd your-project-name
    ```
2.  **Install PHP dependencies:**
    ```bash
    composer install
    ```
3.  **Database Setup:**
    * Create a new MySQL database for the project.
    * Import the database schema from the provided SQL file (e.g., `database_schema.sql`) into your newly created database.
        ```bash
        mysql -u your_db_user -p your_database_name < database_schema.sql
        ```
4.  **Environment Configuration:**
    * Copy the example environment file:
        ```bash
        cp .env.example .env
        ```
    * Edit the `.env` file with your local database credentials (host, database name, username, password) and any other environment-specific settings. (Alternatively, update `config/database.php` if not using `.env` initially).

5.  **Running the Application (Development):**
    You can use PHP's built-in web server for quick development. Navigate to the `public/` directory and run:
    ```bash
    cd public
    php -S localhost:8000
    ```
    Then open `http://localhost:8000` in your browser.

## 🏛️ Key Architectural Decisions
* **Layered Architecture:** Separating concerns into distinct functional layers.
* **Modular Design:** Building functionalities as interconnected modules.
* **Object-Oriented PHP:** Utilizing classes and objects for a structured codebase.
* **Front Controller Pattern:** A single entry point (`public/index.php`) for all requests.
* **Dependency Management with Composer:** For managing external libraries and autoloading.
* **Normalized Permissions:** For flexible and queryable role-based access control.
* **Soft Deletes:** For data integrity and recoverability on key entities.

## 🤝 Contributing
We welcome contributions from team members! To contribute:
1.  Ensure you have the project set up locally.
2.  Create a new branch for your feature or bug fix: `git checkout -b feature/your-feature-name` or `git checkout -b fix/issue-description`.
3.  Commit your changes with clear, descriptive messages.
4.  Push your branch to the repository: `git push origin feature/your-feature-name`.
5.  Open a Pull Request (PR) against the `main` or `develop` branch (please clarify the target branch with the team lead).
6.  Ensure your PR includes a summary of changes and addresses any relevant issues.

## 🔮 Future Plans (High-Level)
* Advanced reporting and analytics.
* Mobile-responsive enhancements or a dedicated mobile app interface.
* Third-party integrations (e.g., accounting software).
* Enhanced notification system (email, SMS).

---

Thank you for being a part of this project! Let's build something great together.

Project Structure :
 ```bash
├── public/                   # Web server's document root
│   ├── index.php             # Front controller
│   └── assets/               # CSS, JS, images (Bootstrap assets here)
├── src/                      # PHP application code (PSR-4, namespace App\)
│   ├── Core/                 # Core functionalities (Router, DB Connection, Auth logic, Base classes)
│   ├── Layer1_UserCompanySite/ # Modules for Layer 1
│   ├── Layer2_Staff/         # Modules for Layer 2
│   ├── Layer3_Supervisor/    # Modules for Layer 3
│   ├── Layer4_Payroll/       # Modules for Layer 4
│   └── Layer5_Admin/         # Modules for Layer 5
├── templates/                # HTML templates (e.g., Twig, Blade, or plain PHP)
├── config/                   # Configuration files (database.php, app.php)
├── vendor/                   # Composer dependencies (Managed by Composer)
├── tests/                    # Unit and integration tests (Good practice to include)
├── .env.example              # Example environment variables
├── .env                      # Local environment variables (Should be in .gitignore)
└── composer.json             # Composer project definition
 ```
