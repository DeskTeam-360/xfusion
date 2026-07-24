# IRR — Individual Evidence™ (Steps 1 & 2)

Data structure reference for compiling, storing, and displaying **Individual Evidence** in the Individual Readiness Review wizard.

## Data flow

```
WordPress Step 1 (checklist + Generate)
→ Laravel IrrEvidenceService::buildSnapshot()
→ wp_fusion_360_evidence_snapshots.snapshot (JSON)
→ WordPress Step 2 (review dashboard)
```

| Layer | Location |
|-------|----------|
| Laravel service | `App\Services\IrrEvidenceService` |
| Controller | `App\Http\Controllers\Api\IrrController::generateEvidence()` / `getEvidence()` |
| DB table | `wp_fusion_360_evidence_snapshots` |
| Wizard bridge | `irr-evidence-service.php` |
| Step 1 UI | `steps/step-1-evidence.php` |
| Step 2 UI | `steps/step-2-evidence-review.php` |

### API

| Method | Path | Purpose |
|--------|------|---------|
| POST | `/api/v1/irrs/{irr}/evidence/generate` | Step 1 — rebuild snapshot (leader edit) |
| GET | `/api/v1/irrs/{irr}/evidence?user_id=` | Step 2 — read latest; auto-build if missing |

Aliases: `/api/v1/irr/...`, `/api/v1/360-reviews/...`

---

## Step 1 checklist — evidence sources

| Key | Label | Status | Source when available |
|-----|-------|--------|------------------------|
| `individual_insights` | Individual Insights™ | **Live** | `wp_xfusion_result_evaluations` where `scoring_group_id = 0` (COR Unified), filtered by review year |
| `previous_irr` | Previous IRR™ | **Live** | Prior row in `wp_fusion_360_reviews` for same `employee_user_id` |
| `activities` | Activities | **Live** | GF submissions via Transform / Sustain / Revitalize course groups (`WpGfEntry`) |
| `commitment_completion` | Commitment Completion | **Live** | `wp_fusion_one_on_one_commitments` for employee in review year |
| `self_assessments` | Self-Assessments | **Live** | `wp_course_scoring_groups` weighted averages (Alignment, Accountability, Communication, Leadership, Execution) |
| `behavioral_driver_trends` | Behavioral Driver Trends | **Live** | Same scoring groups — 5 Behavioral Drivers (Get Real, Fill Buckets, …) |
| `reflection_themes` | Reflection Themes | **Not started** | No aggregated source — private journals / reflections not wired |
| `leader_observations` | Leader Observations | **Live** | 1-on-1 Step 6 synthesis `meeting_summary.items` only (never raw notes/prep) |
| `tool_usage` | Tool Usage | **Live** | GF submissions for `wp_course_groups.tools = 1` |
| `organizational_context` | Organizational Context | **Not started** | No individual-level org context feed |
| `one_on_one` | 1-on-1 Alignment Capture™ | **Live** | `wp_fusion_one_on_one_conversations` for employee in review year |
| `qbr_arp_priorities` | QBR & ARP Priorities | **Not started** | Placeholder in 1-on-1 brief bundle; no IRR aggregation yet |

---

## Step 2 dashboard blocks

| UI block | Status | Snapshot field | Notes |
|----------|--------|----------------|-------|
| Behavioral Driver table (You vs Org Avg) | **Live** | `behavioral_driver_trends.drivers[]` | Org avg = mean across `company_group_id` members |
| Monthly driver line chart | **Not started** | `behavioral_driver_monthly` (null) | Needs time-series per month |
| Development Participation donut | **Live** | `development_participation` | Rate = programs with ≥1 submission / 3 program types |
| Commitment Completion donut | **Live** | `commitment_completion` | Status breakdown from 1-on-1 commitments |
| Growth Timeline | **Partial** | `growth_timeline[]` | Quarter + commitment count + first commitment title as focus label |
| Leadership Observations | **Live** | `leader_observations[]` | Deduped bullets from synthesis summaries |
| Strength Trends (Self-Assessment bars) | **Live** | `self_assessment_scores[]` | COR capability scoring groups |
| Development Trends (Strategic Thinking, …) | **Not started** | `development_trends` (null) | No scoring-group mapping in codebase |
| Organizational Alignment bullets | **Not started** | `organizational_alignment` (null) | Needs QBR/ARP linkage design |
| Evidence Highlights (4 stat cards) | **Live** | `evidence_highlights` | YoY trend when prior-year snapshot exists |
| Reflection Themes | **Not started** | `reflection_themes` (null) | Not shown in Step 2 UI yet |

---

## Snapshot JSON (top-level keys)

```json
{
  "review_period": { "year": 2025, "start": "2025-01-01", "end": "2025-12-31" },
  "employee_user_id": 42,
  "evidence_sources": [ { "key": "activities", "label": "...", "available": true } ],
  "individual_insights": { "score": 72, "key_observation": "..." },
  "behavioral_driver_trends": { "drivers": [ { "slug": "get_real", "you": 4.2, "org_avg": 3.8 } ] },
  "self_assessment_scores": [ { "slug": "leadership", "score": 4.1 } ],
  "development_participation": { "rate": 66.7, "total_submissions": 12 },
  "commitment_completion": { "rate": 75, "completed": 9, "total": 12 },
  "one_on_one": { "completed": 8, "total": 10, "rate": 80 },
  "one_on_one_summaries": [ ],
  "leader_observations": [ "..." ],
  "growth_timeline": [ { "quarter": "Q1", "commitment_count": 2 } ],
  "tool_utilization": { "submissions": 5 },
  "evidence_highlights": { "activities_completed": 12, "one_on_ones_completed": 8 },
  "previous_irr": { "id": 3, "year": 2024 },
  "behavioral_driver_monthly": null,
  "development_trends": null,
  "reflection_themes": null,
  "organizational_alignment": null,
  "qbr_arp_priorities": null
}
```

**Privacy:** Same rule as QBR/1-on-1 — never raw Gravity Forms answers in LLM payloads; snapshot uses aggregated counts/scores and AI synthesis JSON only.

---

## Deployment checklist

- [ ] SQL patch applied: `database/sql/wp_fusion_irr_wizard.sql` (review scoping columns)
- [ ] Base table exists: `wp_fusion_360_evidence_snapshots` (from `wp_fusion_core.sql`)
- [ ] Laravel deployed with `IrrEvidenceService` + routes
- [ ] WordPress plugin includes `irr-evidence-service.php`

---

## Next wave (product decisions needed)

1. **Monthly behavioral driver chart** — define granularity (monthly GF avg vs evaluation history)
2. **Development Trends dimensions** — map to scoring groups or derive from AI assessment (Step 3)
3. **Reflection Themes** — source table / privacy rules
4. **QBR & ARP alignment** — link employee commitments to org priorities by name
5. **Activity “Not Assigned”** — requires course assignment model
6. **Step 3 LLM** — `POST /api/v1/360/development-assessment` in Xfusion-llm (not started)
