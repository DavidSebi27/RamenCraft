# RamenCraft

A gamified pixel art ramen-building web application. Build ramen bowls, score on tastiness and nutrition, earn XP, unlock achievements, and climb the leaderboard.

**Student:** David Sebestyen | **Class:** 2C | **Course:** Web Development 2 - Inholland University

---

## Quick Start

### Prerequisites
- Docker Desktop installed and running

### Start the application

```bash
cd RamenCraft
docker compose up -d --build
```

Wait ~1-2 minutes for the first build (npm install inside container). Then open:

| Service       | URL                          |
|---------------|------------------------------|
| Frontend      | http://localhost:5173        |
| Backend API   | http://localhost:8000/api    |
| phpMyAdmin    | http://localhost:8081        |

### Stop the application

```bash
docker compose down
```

To reset the database (wipe all data and re-seed):

```bash
docker compose down -v
docker compose up -d --build
```

---

## Login Credentials

### Player Account
- **Email:** `player@ramencraft.com`
- **Password:** `password123`

### Admin Account
- **Email:** `admin@ramencraft.com`
- **Password:** `password123`

You can also register a new account via the Register page.

---

## Project Structure

```
RamenCraft/
  frontend/           # Vue 3 frontend (Vite, Pinia, Tailwind CSS)
    src/
      components/      # Vue components (atoms/molecules/organisms/pages)
      stores/          # Pinia state management
      services/        # Axios API client
      assets/          # Sprites, audio, CSS
      data/            # Ingredient color/sprite mappings
      router/          # Vue Router config
      utils/           # Sound effects utility
  backend/
    app/
      src/
        Controllers/   # PHP REST API controllers
        Services/      # External API service (Open Food Facts)
        Config/        # Database + JWT config
        Framework/     # Base controller class
      public/          # Entry point (index.php with FastRoute)
      vendor/          # Composer dependencies
    nginx.conf         # Nginx config
    PHP.Dockerfile     # PHP-FPM container
  database/
    init.sql           # Database schema + seed data
  docker-compose.yml   # Docker orchestration (5 containers)
  .env                 # Environment variables
```

---

## Tech Stack

| Layer      | Technology                                         |
|------------|---------------------------------------------------|
| Frontend   | Vue 3 (Composition API, script setup), Vue Router 5, Pinia, Axios, Tailwind CSS v4 |
| Backend    | PHP REST API (MVC, PSR-4, Composer), FastRoute     |
| Auth       | JWT (firebase/php-jwt)                              |
| Database   | MySQL (MariaDB) - 11 tables                        |
| External API | Open Food Facts (nutrition data, server-side cached) |
| Deployment | Docker Compose (5 containers: frontend, nginx, php, mysql, phpmyadmin) |

---

## Features

### Gameplay
- Build ramen bowls by selecting ingredients across 5 categories (Bowl, Broth, Noodles, Oil, Protein, Toppings)
- Score calculated from tastiness (ingredient combos) + nutrition (macro balance)
- XP system with 5 rank tiers: Minarai, Jouren, Tsuu, Shokunin, Taisho
- 21 achievements (combo-based, milestone-based, and secret achievements)
- Save favorite bowl configurations and reload them
- Leaderboard ranked by total XP

### Visual
- Custom pixel art sprites by Panna Ehleiter
- Layered bowl builder (64x64 sprites stack perfectly)
- CSS steam animation rising from hot bowls
- Restaurant pixel art backgrounds
- Custom chopstick cursors (SVG)
- 8-bit sound effects (Kenney, CC0) and background music (HydroGene, CC0)

### Technical
- All GET endpoints support pagination and filtering
- Error handling on all endpoints
- JWT authentication with role-based access control (player/admin)
- External API calls cached in database to avoid repeated requests
- Promise-based state management (Pinia stores use .then()/.catch())
- Admin CMS for managing ingredients, pairings, achievements, and users

---

## API Endpoints

### Auth
| Method | Endpoint              | Auth     | Description           |
|--------|----------------------|----------|-----------------------|
| POST   | /api/auth/register   | -        | Register new account  |
| POST   | /api/auth/login      | -        | Login, get JWT token  |
| GET    | /api/auth/me         | Bearer   | Get current user      |

### Categories
| Method | Endpoint         | Filters              |
|--------|-----------------|----------------------|
| GET    | /api/categories | ?search=, ?page=, ?limit= |

### Ingredients (Full CRUD)
| Method | Endpoint              | Auth    | Filters                        |
|--------|-----------------------|---------|--------------------------------|
| GET    | /api/ingredients      | -       | ?category=, ?search=, ?page=   |
| GET    | /api/ingredients/{id} | -       | -                              |
| POST   | /api/ingredients      | Bearer  | -                              |
| PUT    | /api/ingredients/{id} | Bearer  | -                              |
| DELETE | /api/ingredients/{id} | Admin   | -                              |

### Pairings (Full CRUD)
| Method | Endpoint            | Auth    | Filters                         |
|--------|--------------------|---------|---------------------------------|
| GET    | /api/pairings      | -       | ?search=, ?ingredient_id=       |
| POST   | /api/pairings      | Bearer  | -                               |
| PUT    | /api/pairings/{id} | Bearer  | -                               |
| DELETE | /api/pairings/{id} | Admin   | -                               |

### Achievements
| Method | Endpoint                | Auth    | Filters                           |
|--------|------------------------|---------|------------------------------------|
| GET    | /api/achievements      | -       | ?search=, ?requirement_type=       |
| GET    | /api/achievements/mine | Bearer  | ?unlocked=true/false               |
| POST   | /api/achievements/check| Bearer  | -                                  |
| POST   | /api/achievements      | Bearer  | -                                  |
| DELETE | /api/achievements/{id} | Admin   | -                                  |

### Users
| Method | Endpoint         | Auth   | Filters                    |
|--------|-----------------|--------|----------------------------|
| GET    | /api/users      | -      | ?search=, ?role=, ?rank=   |
| GET    | /api/users/{id} | -      | -                          |
| PUT    | /api/users/{id} | Bearer | -                          |
| DELETE | /api/users/{id} | Admin  | -                          |

### Bowls
| Method | Endpoint           | Auth   |
|--------|-------------------|--------|
| POST   | /api/bowls/serve  | Bearer |
| GET    | /api/bowls/history| Bearer |

### Favorites
| Method | Endpoint              | Auth   | Filters    |
|--------|-----------------------|--------|------------|
| GET    | /api/favorites        | Bearer | ?search=   |
| POST   | /api/favorites        | Bearer | -          |
| DELETE | /api/favorites/{id}   | Bearer | -          |

### Nutrition (External API)
| Method | Endpoint                       | Auth  |
|--------|-------------------------------|-------|
| GET    | /api/nutrition/ingredient/{id} | -     |
| POST   | /api/nutrition/seed            | Admin |

### Leaderboard
| Method | Endpoint         | Filters                    |
|--------|-----------------|----------------------------|
| GET    | /api/leaderboard | ?search=, ?rank=, ?page=   |

---

## Environment Variables

The `.env` file at the project root contains:

```
DB_HOST=mysql
DB_NAME=ramencraft
DB_USER=ramencraft
DB_PASS=ramencraft
MYSQL_ROOT_PASSWORD=secret123
JWT_SECRET=de34f1dc113367121f557553a0f9cd1e93f1d4f921de80765695d37be4d14c99
NUTRITION_API_KEY=CROChPJr3ObACwvcdabTerHA4dvLmstFygc6KUz6
```

---

## Database

The SQL creation script is at `database/init.sql`. It runs automatically when the MySQL container starts for the first time.

**Tables:** users, categories, ingredients, pairings, served_bowls, bowl_ingredients, achievements, user_achievements, favorites, favorite_ingredients, nutrition_cache

---

## Postman Collection

A complete Postman test collection is included at `backend/RamenCraft_API.postman_collection.json` with 41 requests across 11 folders. Import it into Postman and run the collection - it creates test data, validates all endpoints, and cleans up after itself.

---

## Credits

- **Game Design & Development:** David Sebestyen
- **Pixel Art & Sprites:** Panna Ehleiter
- **Development Assistance:** Claude (Anthropic)
- **Sound Effects:** Kenney.nl (CC0)
- **Background Music:** HydroGene - "The Quiet Spy" (CC0)
- **Loading Animation:** FalcoDJ (itch.io)
- **Font:** "Press Start 2P" by CodeMan38 (OFL)
- **Nutrition Data:** Open Food Facts (ODbL)
