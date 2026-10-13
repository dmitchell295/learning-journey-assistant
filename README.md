# Learning Journey Assistant

A student dashboard that turns Moodle rubric results and feedback into AI-estimated mastery of each learning outcome, then shows strengths and skill gaps.

CSE3CAP Capstone Project, La Trobe University. Team ScrumAndCoke (Jira project CBLS).

| | |
|---|---|
| Live dashboard | https://dmitchell295.github.io/learning-journey-assistant/ |
| Live API | https://learning-journey-assistant-jf6l.onrender.com |
| Repository | https://github.com/dmitchell295/learning-journey-assistant |

> The API runs on a free Render plan and sleeps after about 15 minutes idle. The first request can take up to a minute. The free Postgres database also expires after 30 days unless renewed.

## How it fits together

```
Moodle (rubrics, grades, outcomes)
   -> moodle_client.py (Moodle web service calls)
   -> AI mastery estimator (Prototype AI Gap Detection Engine/)
   -> live_ai_data.py + save_results.py (store results)
   -> Flask REST API (app.py) + database (SQLite locally, Postgres on Render)
   -> React dashboard (frontend/), hosted on GitHub Pages
```

## Repository layout

| Path | What it is |
|---|---|
| `app.py` | Flask API: database routes and `/moodle/...` proxy routes |
| `models.py` | SQLAlchemy models (Student, Subject, Assignment, Assessment, rubric tables and others) |
| `moodle_client.py` | Thin wrapper around the Moodle REST web service |
| `Prototype AI Gap Detection Engine/` | AI mastery estimator and test samples |
| `live_ai_data.py`, `save_results.py` | Pull live Moodle data, run the estimator, save results to the database |
| `seed_test_data.py`, `validate_personas.py` | Local test helpers (local testing only) |
| `frontend/` | React + Vite dashboard |
| `moodle/local/ljaoutcomes/` | Custom read-only Moodle plugin that exposes course learning outcomes |
| `scripts/` | Moodle data generation scripts and the live data pull script |
| `docs/` | Documentation (see below) |
| `.github/workflows/deploy-pages.yml` | Builds and publishes the frontend to GitHub Pages on every push to `main` |

## Documentation

All in `docs/`:

- User Documentation: how to read the dashboard
- System Maintenance and Technical Documentation and Handover: architecture, schema, deployment, maintenance
- `API_CONTRACT.md`: every backend endpoint
- `LJA_API_Handoff.md`: Moodle API functions and how IDs join together
- `SCHEMA_API_ALIGNMENT.md`, `DATA_SCRIPTS.md`
- `FINAL_PROJECT_BACKGROUND_AND_IMPLEMENTATION.md`, `FINAL_TESTING_EVALUATION_AND_DISCUSSION.md`, `FINAL_LIMITATIONS_FUTURE_WORK_AND_CONCLUSION.md`
- `INTEGRATION_TEST_PLAN`, `SPRINT_3_RETROSPECTIVE.md`

## Run it locally

Requirements: Python 3.10 or newer, Node.js 20 or newer, Git.

### Backend

```bash
git clone https://github.com/dmitchell295/learning-journey-assistant.git
cd learning-journey-assistant
python -m venv .venv
source .venv/bin/activate        # Windows: .venv\Scripts\activate
pip install -r requirements.txt
```

Create a file named `.env` in the project root (it is git-ignored, never commit it):

```
MOODLE_URL=https://lja-moodle.australiaeast.cloudapp.azure.com
MOODLE_TOKEN=<ask the team for the Moodle web service token>
```

`moodle_client.py` reads both values when it loads, so they must be set before the app starts. Load them into your shell, for example `export $(grep -v '^#' .env | xargs)` on Linux or macOS, or set them with `$env:MOODLE_URL=...` in PowerShell.

Run the API:

```bash
python app.py
```

It listens on http://127.0.0.1:5000. With no `DATABASE_URL` set it creates a local SQLite file, `learning_journey.db`. Open http://127.0.0.1:5000/ to check it returns `Learning Journey Assistant API is running`.

### Frontend

```bash
cd frontend
npm install
npm run dev
```

Open http://localhost:5173. The dashboard calls `http://127.0.0.1:5000` unless `VITE_API_URL` is set.

### Optional: generate AI results

The AI scripts need an Anthropic API key in the environment. Keep it on your own machine and never put it on Render or in GitHub.

```bash
export ANTHROPIC_API_KEY=<your key>
python live_ai_data.py
```

## Configuration

| Variable | Used by | Purpose |
|---|---|---|
| `MOODLE_URL` | backend | Base URL of the Moodle site |
| `MOODLE_TOKEN` | backend | Moodle web service token (secret) |
| `DATABASE_URL` | backend | Database connection string. Defaults to local SQLite. `postgres://` is rewritten to `postgresql://` automatically (secret) |
| `VITE_API_URL` | frontend build | Backend base URL. Defaults to `http://127.0.0.1:5000` |
| `ANTHROPIC_API_KEY` | AI scripts only | Anthropic API key (secret) |

Secrets are never stored in this repository. Share them privately with team members only.

## Deployment

**Backend (Render).** A Render web service built from this repository. Build command `pip install -r requirements.txt`, start command `gunicorn app:app`. Set `MOODLE_URL`, `MOODLE_TOKEN` and `DATABASE_URL` in the service's environment settings. Do not add `ANTHROPIC_API_KEY` there. CORS allows only `https://dmitchell295.github.io` and `http://localhost:5173`, set in `app.py`. Change this list if the frontend moves to a new address.

**Frontend (GitHub Pages).** The workflow in `.github/workflows/deploy-pages.yml` runs on every push to `main`. It builds `frontend/` with `VITE_API_URL` pointing at the Render service and publishes the result. Pages must be set to deploy from GitHub Actions in the repository settings.

## API overview

Database routes: `/subjects`, `/subject/<id>`, `/students`, `/assessments`, `/assessment/<id>`, `/rubric/<id>`, `/student/<id>/summary`.

Moodle proxy routes: `/moodle/courses`, `/moodle/course/<id>/outcomes`, `/moodle/course/<id>/grades`, `/moodle/course/<id>/assignments`, `/moodle/rubric/<id>`, `/moodle/assignment/<id>/grades`.

Full details and example responses are in `docs/API_CONTRACT.md`.

## Known limitations

- Student data is synthetic, generated for a test Moodle site. There is no login.
- Mastery scores are AI estimates, not official grades.
- The bottom navigation tabs only highlight. All sections are on a single scrolling page.
- Free hosting means a slow first request and a database that expires after 30 days unless renewed.
- The mapping from rubric criteria to learning outcomes is a team decision and a simplification.

See `docs/FINAL_LIMITATIONS_FUTURE_WORK_AND_CONCLUSION.md` for the full discussion.

## Team

Dylan Mitchell, Lewis Murray, Md Shakhawat Hossain Rakib, Su Su, Karthik Vijay.
