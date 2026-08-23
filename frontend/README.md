# Learning Journey Assistant — Frontend

React + Vite frontend for the mastery dashboard.

## Getting Started

1. Install dependencies:
2. Run the dev server:
3. Open http://localhost:5173/ in your browser.

## Current Status (as of Sprint 1)

- Dashboard wireframe is built and styled to match the team's Figma reference
  (La Trobe crimson branding, phone-frame layout, bottom tab nav).
- All data shown is currently **hardcoded placeholder data** — nothing is
  connected to the backend API yet.
- Tab clicks in the bottom nav currently only change visual active state;
  they don't switch page content yet.

## Component Structure
## What's Next (CBLS-45 / CBLS-46)

- **CBLS-45**: Confirm/extend the static dashboard with more realistic
  placeholder data (multiple subjects, more detailed strengths/gaps lists).
- **CBLS-46**: Review the Backend lead's API endpoint response shapes
  (`GET /subject/:id`, `GET /assessment/:id`, etc. — see CBLS-32/35) and
  confirm the field names/structure will map cleanly onto these components
  before wiring in real data.

## Where Placeholder Data Lives

All placeholder values are currently hardcoded directly inside the JSX of
each component (e.g. `72%` in `UnderstandingLevel.jsx`, static text in
`Strengths.jsx`/`Gaps.jsx`/`ProgressTrends.jsx`). These will need to be
replaced with props or state once connected to the backend API.

## Design Reference

Visual direction follows the team's Figma prototype: 
https://five-misty-53477158.figma.site/
