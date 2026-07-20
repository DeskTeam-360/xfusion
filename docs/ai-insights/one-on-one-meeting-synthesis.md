# 1-on-1 — AI Meeting Synthesis™

Data structure reference for generating, storing, and displaying **AI Meeting Synthesis** (Wizard Step 6).

## Data flow

```
WordPress wizard (prep + notes + commitments) → Laravel OneOnOneAiService
→ Xfusion-llm POST /api/v1/one-on-one/meeting-synthesis
→ normalize + commitment merge → wp_fusion_one_on_one_ai_syntheses.synthesis
→ Wizard Step 6 + wp-admin Synthesis History
```

| Layer | Location |
|-------|----------|
| LLM endpoint | `POST /api/v1/one-on-one/meeting-synthesis` |
| Laravel service | `App\Services\OneOnOneAiService::meetingSynthesis()` |
| System prompt | `Xfusion-llm/prompts/one_on_one_synthesis_system.md` |
| DB table | `wp_fusion_one_on_one_ai_syntheses` |
| Admin dashboard | WP Admin → XFusion LLM → **1-on-1 Synthesis History** |
| Wizard UI | `steps/step-6-synthesis.php` |

---

## Request to LLM

```json
{
  "conversation_id": 123,
  "leader_user_id": 45,
  "employee_user_id": 67,
  "preparations": {
    "employee": {
      "summary": "What I want to discuss...",
      "priorities": ["Priority A"]
    },
    "leader": {
      "summary": "Leader prep notes..."
    }
  },
  "notes": [
    {
      "section": "priorities",
      "note": "Discussed Q3 goals..."
    },
    {
      "section": "development",
      "note": "Employee wants mentorship..."
    }
  ],
  "commitments": [
    {
      "title": "Complete training module",
      "description": "Finish module 3 by end of month",
      "owner_role": "employee",
      "status": "open"
    },
    {
      "title": "Schedule follow-up",
      "description": "",
      "owner_role": "leader",
      "status": "in_progress"
    }
  ],
  "system_prompt": null,
  "prompt_version_id": null,
  "prompt_version_label": null
}
```

### Request fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `conversation_id` | int | yes | **This** conversation only |
| `leader_user_id` | int | yes | |
| `employee_user_id` | int | yes | |
| `preparations` | object | no | Keys = `employee` \| `leader`, values = JSON prep content |
| `notes` | array | no | `{ section, note }` from meeting notes |
| `commitments` | array | no | Commitments for this conversation |
| `system_prompt` | string | no | Override from WP |
| `prompt_version_*` | string | no | WP prompt version metadata |

**Scope:** Current conversation data only. No prep/notes from other meetings.

### Commitment `owner_role`

| Value | Label |
|-------|-------|
| `employee` | Employee |
| `leader` | Leader |
| `shared` | Shared |

### Commitment `status`

| Value | Label |
|-------|-------|
| `open` | Open |
| `in_progress` | In Progress |
| `done` | Done |

---

## Response from LLM

```json
{
  "synthesis": {
    "meeting_summary": {
      "items": ["Key takeaway 1"],
      "details": "Overall meeting narrative..."
    },
    "alignment_summary": {
      "score": 4.2,
      "label": "Aligned",
      "items": ["Alignment bullet"],
      "details": "Alignment narrative..."
    },
    "development_summary": {
      "items": [],
      "details": ""
    },
    "commitment_summary": {
      "items": [
        "Employee Commitments: 2 active",
        "Leader Commitments: 1 active",
        "Open Commitments: 3 total"
      ],
      "details": "Commitments on record:\n\n• Complete training module\n  Status: Open · Owner: Employee",
      "employee_count": 2,
      "leader_count": 1,
      "open_count": 3
    },
    "emerging_risks": {
      "items": [],
      "details": ""
    },
    "emerging_opportunities": {
      "items": [],
      "details": ""
    },
    "suggested_coaching_topics": {
      "items": [],
      "details": ""
    },
    "recommended_follow_up": {
      "items": [],
      "details": ""
    }
  },
  "model": "gpt-4o-mini",
  "tokens_used": 890,
  "cost_usd": 0.00025,
  "prompt_tokens": 500,
  "completion_tokens": 390
}
```

### Section keys

| Key | UI label | Special structure |
|-----|----------|-------------------|
| `meeting_summary` | Meeting Summary™ | `{ items, details }` |
| `alignment_summary` | Alignment Summary™ | + `score` (1–5), `label` |
| `development_summary` | Development Summary™ | `{ items, details }` |
| `commitment_summary` | Commitment Summary™ | + `employee_count`, `leader_count`, `open_count` |
| `emerging_risks` | Emerging Risks™ | `{ items, details }` |
| `emerging_opportunities` | Emerging Opportunities™ | `{ items, details }` |
| `suggested_coaching_topics` | Suggested Coaching Topics™ | `{ items, details }` |
| `recommended_follow_up` | Recommended Follow-up™ | `{ items, details }` |

### Laravel post-processing

After the LLM response, Laravel runs `SynthesisCommitmentSummaryNormalizer`:

- Counts (`employee_count`, `leader_count`, `open_count`) are recalculated from DB/UI commitments
- `details` is merged with the actual commitment list if the LLM output is incomplete

Python also normalizes `alignment_summary.score` to range **1.0–5.0**.

---

## Database storage

**Table:** `wp_fusion_one_on_one_ai_syntheses`

| Column | Type | Content |
|--------|------|---------|
| `id` | bigint | PK |
| `conversation_id` | bigint | FK |
| `synthesis` | longtext JSON | 8-section object |
| `insight_model` | varchar(60) | |
| `tokens_used` | int | |
| `cost_usd` | decimal(10,4) | |
| `created_at` | timestamp | |

Each regenerate creates a new row (history).

**Reuse:** This synthesis becomes `prior_syntheses` input for the **Meeting Brief** of the next meeting.

---

## Laravel → WordPress response

`POST /api/v1/one-on-one/conversations/{id}/generate-synthesis`

```json
{
  "success": true,
  "data": {
    "meeting_summary": { "items": [], "details": "" },
    "alignment_summary": { "score": 4.0, "label": "Aligned", "items": [], "details": "" },
    "development_summary": { "items": [], "details": "" },
    "commitment_summary": {
      "items": [],
      "details": "",
      "employee_count": 0,
      "leader_count": 0,
      "open_count": 0
    },
    "emerging_risks": { "items": [], "details": "" },
    "emerging_opportunities": { "items": [], "details": "" },
    "suggested_coaching_topics": { "items": [], "details": "" },
    "recommended_follow_up": { "items": [], "details": "" }
  },
  "meta": {
    "insight_model": "gpt-4o-mini",
    "generated_at": "2026-07-21T01:00:00+00:00"
  }
}
```

---

## Admin dashboard (Synthesis History)

**Menu:** XFusion LLM → 1-on-1 Synthesis History  
**File:** `xfusion-one-on-one-synthesis-admin.php`

List: conversation, leader/employee, company, model, tokens, cost, date.

Detail per record:

| Section | Admin render |
|---------|--------------|
| `alignment_summary` | **Score x / 5** + label |
| `commitment_summary` | Employee: N · Leader: N · Open: N |
| All sections | `<ul>` from `items` + paragraph from `details` |
| Raw JSON | Collapsible `<details>` |

Badge **Latest for this meeting** vs **Archived version** per conversation.

---

## Code references

| File | Role |
|------|------|
| `app/Services/OneOnOneAiService.php` | HTTP bridge |
| `app/Services/SynthesisCommitmentSummaryNormalizer.php` | Post-merge commitments |
| `app/Http/Controllers/Api/OneOnOneController.php` | `generateSynthesis()` |
| `Xfusion-llm/routers/one_on_one.py` | Endpoint + `_normalize_synthesis()` |
| `xfusion-one-on-one-synthesis-admin.php` | Admin history UI |
