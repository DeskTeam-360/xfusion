# FUSION Documentation

Documentation for the Laravel API bridge, WordPress wizards, and AI insight features.

## Structure

| Folder | Audience | Contents |
|--------|----------|----------|
| [**api/**](./api/README.md) | Engineers, integrators | REST endpoints (`/api/v1/*`), auth, request/response patterns |
| [**ai-insights/**](./ai-insights/README.md) | Product + engineering | AI JSON schemas, LLM contracts, UAT planner overview |

## Legacy / reference

| File | Notes |
|------|--------|
| [FUSION_Technical_Specification.md](./FUSION_Technical_Specification.md) | Full platform spec (may lag behind latest ARP/1-on-1 cutover) |
| [FUSION_Remaining_Work_Plan.txt](./FUSION_Remaining_Work_Plan.txt) | Backlog notes |

When in doubt, prefer **api/** and **ai-insights/** for current behavior.

## Quick links

- ARP wizard storage: Laravel `/api/v1/arps/*` — see [api/arp.md](./api/arp.md)
- 1-on-1 wizard storage: Laravel `/api/v1/one-on-one/*` — see [api/one-on-one.md](./api/one-on-one.md)
- LLM service: separate **xfusion-llm** repo; schemas in [ai-insights/](./ai-insights/README.md)
- DB bootstrap: `database/sql/wp_fusion_core.sql`, `database/sql/wp_fusion_arp_server_patch.sql`

## Repos

| Repo | Role |
|------|------|
| **xfusion** (this repo) | Laravel API + WordPress plugin |
| **xfusion-llm** | FastAPI LLM endpoints + prompts |
