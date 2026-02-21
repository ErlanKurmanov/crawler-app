# News Aggregator — kaktus.media

A **Laravel + Vue** application that scrapes news from [kaktus.media](https://kaktus.media) and displays it with date filtering and title search functionality.

---

## Requirements

To run via Laravel Sail, you only need:

- Docker Desktop — installed and running
- Git

You do **not** need to install PHP, Node.js, or Composer locally — everything runs inside containers.

> **Windows:** WSL 2 is recommended. In Docker Desktop settings, ensure the "Use the WSL 2 based engine" option is enabled.

---

## Quick Start

### 1. Clone the repository

---

### 2. Create the environment file

```bash
cp .env.example .env
```

Open `.env` and ensure these values are set (they are already correct for Sail by default):

---

### 3. Install PHP dependencies via a temporary container

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php82-composer:latest \
    composer install --ignore-platform-reqs
```

---

### 4. Start Sail (Docker containers)

```bash
./vendor/bin/sail up -d
```

The first run may take several minutes while Docker downloads images.  
The `-d` flag runs containers in the background.

Verify everything is working:

```bash
./vendor/bin/sail ps
```

You should see the `laravel.test` container with status `running`.

---

### 5. Generate the application key

```bash
./vendor/bin/sail artisan key:generate
```

---

### 6. Create the SQLite database file

```bash
./vendor/bin/sail artisan migrate
```

---

### 7. Install npm dependencies and build the frontend

```bash
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

---

### 8. Open the application

Navigate in your browser to:

```
http://localhost
```

That's it — the application is up and running.

---

## Running in Development Mode (Hot Reload)

If you want to make changes to Vue components and see them instantly without rebuilding:

**Terminal 1** — Sail should already be running (`sail up -d`).

**Terminal 2** — Start the Vite dev server:

```bash
./vendor/bin/sail npm run dev
```

Vite will start on port `5173`. Laravel will automatically detect it via the `@vite` directive in the Blade template.

Open in your browser: `http://localhost`

---

## Manual API Testing

After startup, you can verify the backend is responding:

```bash
# News for today
curl "http://localhost/api/news?date=$(date +%Y-%m-%d)"

# Search by title
curl "http://localhost/api/news/search?date=$(date +%Y-%m-%d)&search=Bishkek"
```
