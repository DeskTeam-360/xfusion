# 1-on-1 API (`/api/v1/one-on-one`)

Controller: `App\Http\Controllers\Api\OneOnOneController`  
WordPress: `[fusion_one_on_one_wizard]` + `one-on-one-shortcode.php` AJAX bridge.

**Storage:** Laravel is the system of record for preparations, notes, commitments, briefs, and syntheses. Step 1 evidence still **reads** Gravity Forms / scoring data for display only.

---

## Pairs & scheduling

| Method | Path | Description |
|--------|------|-------------|
| GET | `/pairs?user_id=` | Pairs for user (leader or employee) |
| GET | `/leader-team?user_id=` | Leader's team members |
| GET | `/meeting-dashboard?user_id=` | Dashboard meetings list |
| POST | `/schedule-for-employee` | Leader schedules meeting for employee |
| GET | `/{oneOnOne}/employee-scoring` | Employee scoring from course groups |
| GET | `/{oneOnOne}/conversations` | List conversations for pair |
| POST | `/{oneOnOne}/conversations` | Schedule new conversation |

---

## Wizard draft (Steps 3–4)

| Method | Path | Body / query | Description |
|--------|------|--------------|-------------|
| GET | `/conversations/{id}/wizard-draft` | `user_id`, optional `scope=wizard\|evidence`, `wizard_admin` | Load prep + conversation notes |
| POST | `/conversations/{id}/wizard-draft/preparation` | `user_id`, `employee`, `leader` (slug maps) | Save preparation JSON |
| POST | `/conversations/{id}/wizard-draft/conversation-notes` | `user_id`, `values` (section → text) | Save meeting notes |
| POST | `/conversations/{id}/preparation` | `author_role`, `content` or `values` | Single-role prep (legacy bridge) |
| GET | `/conversations/{id}/my-preparation?user_id=` | | Own prep only |
| GET | `/conversations/{id}/preparation-status` | | Submitted flags (no content) |
| POST | `/conversations/{id}/reveal` | | Reveal both preps; status → in_progress |

**DB tables:** `wp_fusion_one_on_one_preparations`, `wp_fusion_one_on_one_notes`

---

## Meeting workflow

| Method | Path | Description |
|--------|------|-------------|
| GET | `/conversations/{id}/notes` | All notes |
| POST | `/conversations/{id}/notes` | Add one note (`section`, `note`, `created_by`) |
| GET | `/conversations/{id}/commitments` | List commitments |
| POST | `/conversations/{id}/commitments` | Create commitment |
| POST | `/commitments/{id}` | Update commitment (full row) |
| PATCH | `/commitments/{id}` | Update commitment |
| POST | `/conversations/{id}/complete` | Complete meeting |
| POST | `/conversations/{id}/status` | Update status (`user_id`, `status`) |
| GET | `/conversations/{id}/evidence` | Server-side evidence aggregation |

**DB table:** `wp_fusion_one_on_one_commitments`

---

## AI Meeting Brief (Step 2)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/conversations/{id}/brief` | Latest brief JSON |
| GET | `/conversations/{id}/brief-history` | All brief versions (metadata) |
| GET | `/conversations/{id}/briefs/{briefId}` | One archived brief |
| POST | `/conversations/{id}/generate-brief` | Generate via LLM (+ optional `evidence_context`) |

**LLM:** `POST /api/v1/one-on-one/meeting-brief` (xfusion-llm)  
**DB:** `wp_fusion_one_on_one_ai_briefs`  
**Schema:** [../ai-insights/one-on-one-meeting-brief.md](../ai-insights/one-on-one-meeting-brief.md)

Wizard: user clicks **Generate** on **Step 2** (evidence assembled from Step 1).

---

## AI Meeting Synthesis (Step 6)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/conversations/{id}/synthesis` | Latest synthesis JSON |
| GET | `/conversations/{id}/synthesis-history` | Version list |
| GET | `/conversations/{id}/syntheses/{synthesisId}` | One archived synthesis |
| POST | `/conversations/{id}/generate-synthesis` | Generate via LLM; optional body: `preparations`, `notes`, `commitments` |

**LLM:** `POST /api/v1/one-on-one/meeting-synthesis` (xfusion-llm)  
**DB:** `wp_fusion_one_on_one_ai_syntheses`  
**Schema:** [../ai-insights/one-on-one-meeting-synthesis.md](../ai-insights/one-on-one-meeting-synthesis.md)

Wizard: user clicks **Generate** on **Step 6** (after Steps 3–5).

---

## Eloquent relationships (`OneOnOneConversation`)

```text
oneOnOne()
preparations()   hasMany OneOnOnePreparation
notes()          hasMany OneOnOneNote
commitments()    hasMany OneOnOneCommitment
brief()          hasOne OneOnOneAiBrief (latest)
briefs()         hasMany
synthesis()      hasOne OneOnOneAiSynthesis (latest)
syntheses()      hasMany
```

---

## WordPress AJAX map (selected)

| AJAX action | Laravel path |
|-------------|----------------|
| `xfusion_oo_pairs` | GET `/pairs` |
| `xfusion_oo_meeting_dashboard` | GET `/meeting-dashboard` |
| `xfoo_wizard_load_draft` | GET `/wizard-draft` |
| `xfoo_wizard_save_draft` | POST `/wizard-draft/preparation` or `/conversation-notes` |
| `xfoo_wizard_generate_brief` | POST `/generate-brief` |
| `xfoo_wizard_generate_synthesis` | POST `/generate-synthesis` |
| `xfoo_wizard_get_commitments` | GET `/commitments` |
