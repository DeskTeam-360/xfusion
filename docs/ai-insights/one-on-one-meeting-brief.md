# 1-on-1 — AI Meeting Brief™

Data structure reference for generating, storing, and displaying the **AI Meeting Brief** (Wizard Step 2).

## Data flow

```
WordPress Step 1 (evidence, read-only)
→ user clicks Generate on Step 2
→ Laravel OneOnOneAiService → Xfusion-llm POST /api/v1/one-on-one/meeting-brief
→ normalize → wp_fusion_one_on_one_ai_briefs.brief → Step 2 panel + wp-admin Brief History
```

| Layer | Location |
|-------|----------|
| LLM endpoint | `POST /api/v1/one-on-one/meeting-brief` |
| Laravel service | `App\Services\OneOnOneAiService::meetingBriefFromEvidence()` |
| System prompt | `Xfusion-llm/prompts/one_on_one_brief_system.md` |
| DB table | `wp_fusion_one_on_one_ai_briefs` |
| Admin dashboard | WP Admin → XFusion LLM → **1-on-1 Brief History** |
| Wizard UI | `steps/step-2-brief.php` (Generate button), `brief-wizard-service.php` |

---

## Request to LLM

Laravel sends the following JSON to FastAPI:

```json
{
  "conversation_id": 123,
  "leader_user_id": 45,
  "employee_user_id": 67,
  "prior_syntheses": [],
  "evidence_context": {},
  "system_prompt": null,
  "prompt_version_id": null,
  "prompt_version_label": null
}
```

### Request fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `conversation_id` | int | yes | `wp_fusion_one_on_one_conversations.id` |
| `leader_user_id` | int | yes | Leader `wp_users.ID` |
| `employee_user_id` | int | yes | Employee `wp_users.ID` |
| `prior_syntheses` | array | no | Up to 6 synthesis JSON objects from **previous** meetings (not this conversation) |
| `evidence_context` | object | no | Step 1 Continuous Evidence bundle (12 sections) |
| `system_prompt` | string | no | Prompt override from WP prompt registry |
| `prompt_version_id` | string | no | WP prompt version ID |
| `prompt_version_label` | string | no | WP prompt version label |

### `evidence_context` (Step 1 input)

Built by `xfoo_wizard_evidence_bundle_for_brief()` in WordPress:

```json
{
  "conversation_id": 123,
  "employee_id": 67,
  "generated_from": "step_1_continuous_evidence",
  "sections": {
    "previous_1_on_1": {
      "title": "Previous 1-on-1",
      "data": {
        "meetings": [
          {
            "id": 99,
            "date": "Jul 15, 2026 · 7:59 PM",
            "date_raw": "2026-07-15T12:59:00+00:00",
            "status": "Completed",
            "status_raw": "completed",
            "leader_name": "Jane Leader",
            "detail": {}
          }
        ]
      }
    },
    "previous_commitments": {
      "title": "Previous Commitments",
      "data": {
        "items": [
          {
            "id": 1,
            "conversation_id": 99,
            "title": "Complete onboarding checklist",
            "priority": "medium",
            "behavioral_driver": "be_intentional",
            "behavioral_driver_label": "Be Intentional",
            "success_indicator": "",
            "owner_role": "employee",
            "status": "open",
            "status_label": "Open",
            "due_date": "",
            "due_date_label": "",
            "meeting": {}
          }
        ]
      }
    },
    "individual_insights": { "title": "Individual Insights™", "data": {} },
    "activities": { "title": "Activities", "data": { "items": [] } },
    "self_assessments": { "title": "Self-Assessments", "data": { "scores": [] } },
    "development_tools": { "title": "Development Tools", "data": { "items": [] } },
    "behavioral_driver_trends": { "title": "Behavioral Driver Trends", "data": { "scores": [] } },
    "ai_insight_trends": { "title": "AI Insight Trends", "data": {} },
    "qbr_priorities": { "title": "QBR Priorities", "data": { "status": "placeholder", "note": "Evidence source not yet connected." } },
    "arp_priorities": { "title": "ARP Priorities", "data": { "status": "placeholder" } },
    "previous_360": { "title": "Previous 360 Review™", "data": { "status": "placeholder" } },
    "organizational_context": { "title": "Organizational Context", "data": { "status": "placeholder" } }
  }
}
```

**Privacy:** Raw preparation text is **not** sent to the LLM. Only `prior_syntheses` + `evidence_context`.

---

## Response from LLM

FastAPI normalizes output into 7 sections. Each section = `{ "items": string[], "details": string }`.

```json
{
  "brief": {
    "alignment_snapshot": {
      "items": ["Bullet 1", "Bullet 2"],
      "details": "Narrative paragraph..."
    },
    "development_snapshot": {
      "items": [],
      "details": ""
    },
    "commitment_review": {
      "items": [],
      "details": ""
    },
    "behavioral_trends": {
      "items": [],
      "details": ""
    },
    "suggested_discussion_areas": {
      "items": [],
      "details": ""
    },
    "emerging_opportunities": {
      "items": [],
      "details": ""
    },
    "potential_barriers": {
      "items": [],
      "details": ""
    }
  },
  "model": "gpt-4o-mini",
  "tokens_used": 624,
  "cost_usd": 0.00018,
  "prompt_tokens": 432,
  "completion_tokens": 192,
  "prompt_version_id": null,
  "prompt_version_label": null
}
```

### Section keys (required)

| Key | UI label |
|-----|----------|
| `alignment_snapshot` | Alignment Snapshot™ |
| `development_snapshot` | Development Snapshot™ |
| `commitment_review` | Commitment Review™ |
| `behavioral_trends` | Behavioral Trends™ |
| `suggested_discussion_areas` | Suggested Discussion Areas™ |
| `emerging_opportunities` | Emerging Opportunities™ |
| `potential_barriers` | Potential Barriers™ |

Normalization rules (`Xfusion-llm/routers/one_on_one.py`):

- Max **4** items per section (`items`)
- `details` = narrative string

---

## Database storage

**Table:** `wp_fusion_one_on_one_ai_briefs`

| Column | Type | Content |
|--------|------|---------|
| `id` | bigint | PK |
| `conversation_id` | bigint | FK conversation |
| `brief` | longtext JSON | 7-section object above |
| `insight_model` | varchar(60) | e.g. `gpt-4o-mini` |
| `tokens_used` | int | |
| `cost_usd` | decimal(10,4) | |
| `created_at` | timestamp | |

Each generate (force refresh) creates a **new row** (version history).

---

## Laravel → WordPress response (wizard)

`POST /api/v1/one-on-one/conversations/{id}/generate-brief`

```json
{
  "success": true,
  "data": {
    "alignment_snapshot": { "items": [], "details": "" },
    "development_snapshot": { "items": [], "details": "" },
    "commitment_review": { "items": [], "details": "" },
    "behavioral_trends": { "items": [], "details": "" },
    "suggested_discussion_areas": { "items": [], "details": "" },
    "emerging_opportunities": { "items": [], "details": "" },
    "potential_barriers": { "items": [], "details": "" }
  },
  "meta": {
    "insight_model": "gpt-4o-mini",
    "generated_at": "2026-07-21T01:00:00+00:00"
  }
}
```

WordPress AJAX `xfoo_wizard_generate_brief` wraps:

```json
{
  "success": true,
  "data": {
    "brief": { "... same as data above ..." },
    "meta": { "insight_model": "...", "generated_at": "..." },
    "evidence_context": { "... bundle sent to LLM ..." }
  }
}
```

---

## Admin dashboard (Brief History)

**Menu:** XFusion LLM → 1-on-1 Brief History  
**File:** `xfusion-one-on-one-briefs-admin.php`

List shows: conversation, leader/employee, company, model, tokens, cost, generated date.

Detail per record:

- Row metadata (model, tokens, cost, pair, conversation link)
- Brief versions for the same conversation
- Each section: bullet list from `items` + paragraph from `details`
- Collapsible raw JSON for debugging

---

## Code references

| File | Role |
|------|------|
| `app/Services/OneOnOneAiService.php` | HTTP bridge to LLM |
| `app/Http/Controllers/Api/OneOnOneController.php` | `generateBrief()` |
| `Xfusion-llm/routers/one_on_one.py` | Endpoint + normalizer |
| `step-1-evidence-service.php` | `evidence_context` builder |
| `brief-wizard-service.php` | Step 2 render |
