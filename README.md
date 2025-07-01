# Email Writing Assistant AI

The Email Writing Assistant is a full-stack web application designed to help users transform their raw thoughts into polished, context-aware emails using the power of AI. It supports various tones, allows users to provide conversational context from previous emails, and offers flexible Large Language Model (LLM) configuration.

## Features

*   **AI-Powered Email Generation:** Converts user's raw notes and ideas into well-structured email drafts.
*   **Tone Adjustment:** Allows users to specify the desired tone for the generated email (e.g., Professional, Casual, Formal).
*   **Contextual Awareness:** Users can paste a previous email to provide context for generating a reply.
*   **Flexible LLM Integration:**
    *   Supports local LLMs via **Ollama** (e.g., Llama 3, Mistral).
    *   Supports cloud-based LLMs: **OpenAI** (GPT series), **Claude** (Anthropic), **Gemini** (Google), **Groq**, **Cohere**. (Cloud integrations are via their standard API structures).
*   **User Authentication:** Secure user registration and login system using email/password. JWT-based authentication.
*   **Personalized Settings:** Dedicated settings page for:
    *   Selecting preferred LLM provider.
    *   Configuring API keys for cloud-based LLMs.
    *   Setting the local Ollama endpoint URL.
*   **Email History:** Stores a history of generated emails for each user (feature implied by `user_emails_history` table).
*   **Responsive User Interface:** Intuitive and adaptive frontend built with React and Tailwind CSS.
*   **Robust Backend API:** PHP-based API handles business logic, data persistence, and LLM interactions.
*   **Relational Database:** Uses MySQL/MariaDB for structured data storage.

## Technologies Used

*   **Frontend:**
    *   React (with TypeScript)
    *   Vite (build tool)
    *   Tailwind CSS (styling)
    *   Axios (HTTP client)
    *   React Router DOM (navigation)
    *   Lucide React (icons)
*   **Backend:**
    *   PHP (core logic, API)
    *   PDO (for database interaction)
    *   JSON Web Tokens (JWT) for authentication
    *   cURL (for interacting with LLM APIs)
*   **Database:**
    *   MySQL / MariaDB
*   **Deployment Target:**
    *   Optimized for HostGator (LAMP stack environment)

## Installation & Setup Steps

### Prerequisites

*   **Web Server:** Apache or Nginx (HostGator typically provides Apache).
*   **PHP:** Version 8.0 or higher recommended (with cURL, OpenSSL, PDO_MySQL extensions enabled).
*   **Database:** MySQL 5.7+ or MariaDB 10.2+.
*   **Node.js & npm:** Node.js LTS version (e.g., 18.x or 20.x) and npm (comes with Node.js) for frontend asset bundling.
*   **Composer:** (Optional, but recommended if you add PHP dependencies later). This project currently uses no external PHP libraries managed by Composer.
*   **Git:** For cloning the repository.

### 1. Clone Repository

```bash
git clone <repository-url>
cd <repository-name>
```

### 2. Backend Setup

The backend code resides in the `backend/` directory.

**a. Environment Variables:**

*   Navigate to the `backend/` directory.
*   Copy the example environment file: `cp .env.example .env`
*   Edit the `.env` file with your specific configurations:
    *   `DB_HOST`: Your database host (e.g., `localhost`).
    *   `DB_PORT`: Your database port (e.g., `3306`).
    *   `DB_NAME`: The name of the database you will create/use.
    *   `DB_USER`: Your database username.
    *   `DB_PASS`: Your database password.
    *   `JWT_SECRET`: **Crucial!** Change this to a long, random, and strong secret key for signing JWTs.
    *   `ENCRYPTION_KEY`: **Crucial!** Change this to a strong, random key for encrypting API keys in the database. You can generate one using `openssl rand -base64 32`.
    *   `API_BASE_URL`: Defaults to `/api`. This is the prefix for all API routes.
    *   `ALLOWED_ORIGINS`: A comma-separated list of frontend origins allowed for CORS. For local development with Vite (default port 5173), set it to `http://localhost:5173`. If using other ports, add them. For production, set this to your frontend domain.

**b. Database Setup:**

*   Ensure your MySQL/MariaDB server is running.
*   Using a database management tool (phpMyAdmin, DBeaver, MySQL CLI, etc.):
    1.  Create the database specified in `DB_NAME` in your `.env` file (e.g., `CREATE DATABASE email_assistant_db;`).
    2.  Select this database (e.g., `USE email_assistant_db;`).
    3.  Import the table structure by executing the SQL commands found in `backend/database_setup.sql`.

**c. Web Server Configuration (for Apache on HostGator):**

*   Upload the entire project to your HostGator public_html directory or a subdirectory.
*   Ensure your web server's document root points to the `backend/public/` directory for API requests if you want cleaner URLs (e.g., `yourdomain.com/api/...`).
*   Alternatively, if the entire project is in a subdirectory (e.g., `email-assistant`), API requests might be `yourdomain.com/email-assistant/backend/public/api/...` which `index.php` tries to handle.
*   **URL Rewriting (Apache):** To make the API accessible via `yourdomain.com/api/...` (assuming the `backend/public` is configured as a root for this path or via .htaccess rules), you might need an `.htaccess` file in `backend/public/` if not using a virtual host configuration that directly maps to it. A simple one could be:

    ```apacheconf
    <IfModule mod_rewrite.c>
      RewriteEngine On
      RewriteCond %{REQUEST_FILENAME} !-f
      RewriteCond %{REQUEST_FILENAME} !-d
      RewriteRule ^ index.php [QSA,L]
    </IfModule>
    ```
    This ensures all requests to non-existent files/directories under `backend/public/` are routed to `index.php`. The `API_BASE_URL` in `.env` and the router in `index.php` handle the `/api` part.

**d. Start PHP Server (Local Development):**

For local development (not for production on HostGator), you can use PHP's built-in server:

```bash
# From the project root directory
php -S localhost:8000 -t backend/public
```
The API will be available at `http://localhost:8000/api`.

### 3. Frontend Setup

The frontend code resides in the `frontend/` directory.

**a. Install Dependencies:**

```bash
cd frontend
npm install
```
(or `yarn install` if you prefer yarn)

**b. Environment Variables (Frontend):**

*   Create a `.env` file in the `frontend/` directory (e.g., `cp .env.example .env` if an example is provided, or create a new one).
*   Add the following variable, pointing to your running backend API:
    ```
    VITE_API_BASE_URL=http://localhost:8000/api
    ```
    (Adjust the URL and port if your backend is served differently. For production, this would be `https://yourdomain.com/api`).

**c. Start Frontend Development Server:**

```bash
# From the frontend/ directory
npm run dev
```
The React application will typically be available at `http://localhost:5173` (Vite's default).

**d. Build for Production:**

When deploying to HostGator:

```bash
# From the frontend/ directory
npm run build
```
This will create a `dist/` folder inside `frontend/` containing static assets (HTML, CSS, JS). These are the files you need to serve for the frontend.

### 4. Deployment to HostGator (Simplified)

1.  **Backend:** Upload all files within the `backend/` directory to a folder on your HostGator server (e.g., directly in `public_html` or a subfolder like `public_html/email_api`). Configure your `.env` file with production database credentials and secrets. Ensure your domain/subdomain points to `backend/public` or use `.htaccess` rules.
2.  **Frontend:**
    *   Run `npm run build` locally in the `frontend/` directory.
    *   Upload the contents of the `frontend/dist/` directory to the location where you want to serve the frontend (e.g., `public_html/` if the app is at the root, or `public_html/app/` if it's in a subfolder).
    *   Ensure the `VITE_API_BASE_URL` used during the build (or configured at runtime if your setup allows) correctly points to your live backend API URL.
    *   **Single Page Application (SPA) Routing:** For SPAs like React Router, you'll need to configure your Apache server on HostGator to redirect all non-asset requests to your main `index.html` file. Add this to an `.htaccess` file in the directory where your frontend's `index.html` (from `frontend/dist/`) is located:
        ```apacheconf
        <IfModule mod_rewrite.c>
          RewriteEngine On
          RewriteBase / # Or your subdirectory if app is not at root, e.g. /app/
          RewriteRule ^index\.html$ - [L]
          RewriteCond %{REQUEST_FILENAME} !-f
          RewriteCond %{REQUEST_FILENAME} !-d
          RewriteCond %{REQUEST_FILENAME} !-l
          RewriteRule . index.html [L]
        </IfModule>
        ```

## LLM Configuration

LLM providers and their API keys/endpoints are configured on a per-user basis via the **Settings** page in the application after logging in.

*   **Ollama (Local):**
    1.  Install Ollama on your local machine (or a server accessible to the PHP backend).
    2.  Download desired models (e.g., `ollama pull llama3`).
    3.  Ensure Ollama is running.
    4.  In the application's Settings page, select "Ollama (Local)" and enter the Ollama server URL (e.g., `http://localhost:11434` if the PHP backend can reach it at this address. If PHP is on a different machine than Ollama, use the appropriate network address).
*   **Cloud LLMs (OpenAI, Claude, Gemini, Groq, Cohere):**
    1.  Obtain an API key from the respective LLM provider's website.
    2.  In the application's Settings page, select your desired provider.
    3.  Enter the API key into the corresponding field and save. The key will be encrypted before being stored in the database.

The backend will use these saved settings to interact with the chosen LLM when generating emails.

## Usage

1.  **Register:** Create a new account using your email and password.
2.  **Login:** Sign in with your credentials.
3.  **Configure LLM (Settings Page):**
    *   Navigate to the "Settings" page.
    *   Select your preferred LLM provider.
    *   Enter the Ollama endpoint if using Ollama, or the API key if using a cloud provider.
    *   Save your settings.
4.  **Generate Emails (Main Page):**
    *   Navigate to the main email writing interface.
    *   Enter your raw thoughts/notes for the email.
    *   Select the desired tone.
    *   Optionally, paste a previous email for context if you're writing a reply.
    *   Click "Generate Email". The AI will draft an email based on your input and chosen LLM.
    *   Copy the generated email to your clipboard.

## Contributing

Contributions are welcome! Please follow standard coding practices and ensure any new features or fixes are well-tested. (Further guidelines can be added here, e.g., for pull requests, issue tracking).

## License

This project is licensed under the MIT License. (Or specify your chosen license).
```
