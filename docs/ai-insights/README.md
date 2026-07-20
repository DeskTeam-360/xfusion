# AI Insight JSON Schemas

Reference for data structures across all FUSION AI insight features.

| Audience | Document |
|----------|----------|
| **Product / planning** | [planner-overview.md](./planner-overview.md) — flows, UAT, acceptance criteria (non-technical) |
| **Engineering** | Feature docs below — JSON schemas and code references |

| Document | Feature | LLM endpoint | DB table |
|----------|---------|--------------|----------|
| [one-on-one-meeting-brief.md](./one-on-one-meeting-brief.md) | 1-on-1 AI Meeting Brief™ (Step 2) | `POST /api/v1/one-on-one/meeting-brief` | `wp_fusion_one_on_one_ai_briefs` |
| [one-on-one-meeting-synthesis.md](./one-on-one-meeting-synthesis.md) | 1-on-1 AI Meeting Synthesis™ (Step 6) | `POST /api/v1/one-on-one/meeting-synthesis` | `wp_fusion_one_on_one_ai_syntheses` |
| [arp-readiness-review.md](./arp-readiness-review.md) | ARP AI Readiness Review™ (Step 6) | `POST /api/v1/arp/readiness-review` | `wp_fusion_arp_ai_assessments` |

## LLM configuration (Laravel)

```env
XFUSION_LLM_API_URL=http://<llm-host>:8000
XFUSION_LLM_API_KEY=<same as API_KEY in xfusion-llm .env>
```

Diagnostics: `php artisan xfusion:llm-probe`

## wp-admin dashboards

- **1-on-1 Brief History** — `xfusion-one-on-one-briefs-admin.php`
- **1-on-1 Synthesis History** — `xfusion-one-on-one-synthesis-admin.php`
- **ARP AI** — no admin UI yet (see ARP doc)
