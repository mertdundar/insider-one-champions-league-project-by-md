# Insider One Champions League Simulation by Mert Dündar

A 4-team (statically-coded with team strengths within their names for case purposes) football league simulation. Generate fixtures, play matches week by week
(or all at once as bonus), see the weekly fixture (and edit any match results as bonus), watch the league table update, and see each team's championship
odds from week 4 onward. Built with Laravel (PHP 8.5) and Vue.js (Vue 3 + JavaScript), and SQLite as persistent SQL database (even though it was not expected) runs in a single
Docker container.

## [Live Demo](https://insider-one-champions-league-project-by.onrender.com)

Accessible via Render (**could take a while to load due to Free Tier shutting down after 60 seconds inactivity**)

[https://insider-one-champions-league-project-by.onrender.com](https://insider-one-champions-league-project-by.onrender.com)


## Requirements

The only thing you need is **Docker** (Docker Desktop includes everything).

- **Windows / macOS:** install [Docker Desktop](https://www.docker.com/products/docker-desktop/)
- **Linux:** install [Docker Engine](https://docs.docker.com/engine/install/) and the Compose plugin

Verify it's working:

```
docker compose version
```

## Run

Clone the repository and start the app:

```
git clone https://github.com/mertdundar/insider-one-champions-league-project-by-md.git champions-league-project
cd champions-league-project
docker compose up --build
```

Then open <http://localhost:8000>.

The build runs the full test suite automatically. Data persists in the `sqlite_data` volume.
