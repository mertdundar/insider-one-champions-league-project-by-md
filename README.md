# Football League Simulation

A 4-team (statically-coded with team strengths within their names for case purposes) football league simulation. Generate fixtures, play matches week by week
(or all at once), see the weekly fixture (and edit any match results), watch the league table update, and see each team's championship
odds from week 4 onward. Built with Laravel (PHP 8.5) and Vue.js (Vue 3 + JavaScript), and SQLite as persistent SQL database. Runs in a single
Docker container.

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
git clone https://github.com/mertdundar/football-league-simulation.git football-league-simulation
cd football-league-simulation
docker compose up --build
```

Then open <http://localhost:8000>.

The build runs the full test suite automatically. Data persists in the `sqlite_data` volume.
