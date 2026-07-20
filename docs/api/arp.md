# ARP API (`/api/v1/arps`)

Controller: `App\Http\Controllers\Api\ArpController`  
WordPress: `[fusion_arp_wizard]` + `arp-picker.php` / `arp-*-service.php` bridge.

**Primary prefix:** `/api/v1/arps/*`  
**Legacy alias:** `/api/v1/arp/*` (same routes)

**Storage:** Laravel is the system of record for all wizard steps (1–7). Gravity Forms is no longer used for ARP wizard save/load.

---

## Picker & ARP meta

| Method | Path | Query / body | Description |
|--------|------|--------------|-------------|
| GET | `/leadable-companies?user_id=` | | Groups user leads (create picker) |
| GET | `/list?user_id=` | | ARPs visible to user |
| POST | `/` | `user_id`, `company_group_id`, `year`, … | Create ARP |
| GET | `/{arp}?user_id=` | | Single ARP + `can_edit`, `step_progress` |

**Unique constraint:** one ARP per `(company_group_id, year)`

---

## Wizard plan (Steps 1, 2, 5)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/{arp}/plan?user_id=` | Full draft: foundation, future_state, learning |
| GET | `/{arp}/foundation` | Step 1 fields |
| POST | `/{arp}/foundation` | `user_id`, `values` (slug map) |
| GET | `/{arp}/future-state` | Step 2 fields |
| POST | `/{arp}/future-state` | `user_id`, `values` |
| GET | `/{arp}/learning` | Step 5 fields |
| POST | `/{arp}/learning` | `user_id`, `values` |

**DB:** `wp_fusion_arps` (foundation columns), `wp_fusion_arp_future_states`, `wp_fusion_arp_learnings`

---

## Readiness & strategic (Steps 3–4)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/{arp}/readiness-priorities` | List priorities |
| POST | `/{arp}/readiness-priorities` | Replace-all save (`user_id`, `items[]`) |
| GET | `/{arp}/strategic-priorities` | List strategic priorities |
| POST | `/{arp}/strategic-priorities` | Replace-all save (`user_id`, `items[]`) |

**DB:** `wp_fusion_arp_readiness_priorities`, `wp_fusion_arp_strategic_priorities`

---

## AI Readiness Review (Step 6)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/{arp}/readiness-review?user_id=` | Latest assessment + leadership context |
| POST | `/{arp}/readiness-review/generate` | `user_id` — generate / regenerate |
| PATCH | `/{arp}/readiness-review/context` | Save `leadership_context` |
| POST | `/{arp}/readiness-review/context` | Same (WP bridge compat) |

**LLM:** `POST /api/v1/arp/readiness-review` (xfusion-llm)  
**DB:** `wp_fusion_arp_ai_assessments`  
**Schema:** [../ai-insights/arp-readiness-review.md](../ai-insights/arp-readiness-review.md)

Context built by `App\Services\ArpPlanService::aiPlanContext()` from DB only.

---

## Publish (Step 7)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/{arp}/versions` | Version history (no snapshot bodies) |
| POST | `/{arp}/archive-version` | Snapshot current draft as archived |
| POST | `/{arp}/publish` | Publish: bump version, status `active` |

**DB:** `wp_fusion_arp_versions` (snapshot JSON)  
**Evidence:** writes to `wp_fusion_evidence_log` on publish, archive, and AI generate (`ArpEvidenceService`)

---

## Eloquent relationships (`Arp`)

```text
company()              belongsTo Company
companyGroup()         belongsTo CompanyGroup
creator()              belongsTo User
futureState()          hasOne ArpFutureState
learnings()            hasMany ArpLearning
readinessPriorities()  hasMany ArpReadinessPriority
strategicPriorities()  hasMany ArpStrategicPriority
aiAssessments()         hasMany ArpAiAssessment
versions()             hasMany ArpVersion
```

Child models: `belongsTo(Arp)`.  
`ArpStrategicPriority` → `readinessPriority()` belongsTo.

---

## `step_progress` (JSON on `wp_fusion_arps`)

Updated on each step save and on publish / AI generate:

```json
{
  "foundation": true,
  "future_state": false,
  "readiness": true,
  "strategic": true,
  "learning": false,
  "ai_review": true,
  "publish": false
}
```

---

## SQL / deploy

Existing server patch: `database/sql/wp_fusion_arp_server_patch.sql`  
Fresh install: `database/sql/wp_fusion_core.sql`

---

## WordPress AJAX map (selected)

| AJAX action | Laravel path |
|-------------|----------------|
| `xfarp_picker_list` | GET `/list` |
| `xfarp_wizard_load_draft` | GET `/plan` |
| `xfarp_plan_save` | POST `/foundation`, `/future-state`, or `/learning` |
| `xfarp_readiness_load` | GET `/readiness-priorities` |
| `xfarp_readiness_save` | POST `/readiness-priorities` |
| `xfarp_ai_review_generate` | POST `/readiness-review/generate` |
| `xfarp_publish` | POST `/publish` |
