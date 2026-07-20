# ARP — AI Readiness Review™ (Step 6)

Data structure reference for generating, storing, and displaying **AI Readiness Review** in the Annual Readiness Plan wizard.

## Data flow

```
WordPress ARP Steps 1–5 → Laravel ArpPlanContextService (assemble plan_context)
→ ArpAiService → Xfusion-llm POST /api/v1/arp/readiness-review
→ ArpReadinessReviewNormalizer → wp_fusion_arp_ai_assessments.assessment
→ Wizard Step 6 UI (6.1–6.6) + leadership_context (editable)
```

| Layer | Location |
|-------|----------|
| LLM endpoint | `POST /api/v1/arp/readiness-review` |
| Laravel service | `App\Services\ArpAiService::generateReadinessReview()` |
| Context builder | `App\Services\ArpPlanContextService::build()` |
| Normalizer | `App\Services\ArpReadinessReviewNormalizer` |
| System prompt | `Xfusion-llm/prompts/arp_readiness_review_system.md` |
| DB table | `wp_fusion_arp_ai_assessments` |
| Admin dashboard | **Not yet implemented** (data in DB + wizard only) |
| Wizard UI | `steps/step-6-ai-review.php`, `arp-ai-review-service.php` |

---

## Laravel API (WordPress bridge)

| Method | Route | Purpose |
|--------|-------|---------|
| GET | `/api/v1/arp/{arp}/readiness-review` | Load assessment + leadership context |
| POST | `/api/v1/arp/{arp}/readiness-review/generate` | Generate / regenerate AI |
| PATCH | `/api/v1/arp/{arp}/readiness-review/context` | Save leadership context |

WordPress AJAX: `xfarp_ai_review_load`, `xfarp_ai_review_generate`, `xfarp_ai_review_save_context`

---

## Request to LLM

```json
{
  "arp_id": 42,
  "plan_context": {
    "arp": {},
    "foundation": {},
    "future_state": {},
    "readiness_priorities": [],
    "strategic_priorities": [],
    "learning": {}
  },
  "system_prompt": null,
  "model": null
}
```

### `plan_context` — Steps 1–5 sources

Built by `ArpPlanContextService`:

```json
{
  "arp": {
    "id": 42,
    "title": "ARP 2026",
    "year": 2026,
    "status": "draft",
    "company_name": "Operations Group",
    "mission": "From wp_fusion_arps or GF Step 1",
    "vision": "..."
  },
  "foundation": {
    "mission": "...",
    "vision": "...",
    "core_values": "...",
    "organizational_description": "...",
    "business_environment": "...",
    "executive_narrative": "..."
  },
  "future_state": {
    "future_state_narrative": "...",
    "future_characteristics": "...",
    "desired_culture": "...",
    "desired_customer_experience": "...",
    "desired_employee_experience": "...",
    "desired_leadership_environment": "..."
  },
  "readiness_priorities": [
    {
      "name": "Leadership Pipeline",
      "cor_capability": "leadership",
      "primary_driver": "be_intentional",
      "secondary_driver": "foster_grit",
      "priority_level": "high",
      "description": "...",
      "business_rationale": "...",
      "executive_owner_user_id": 45,
      "expected_impact": "...",
      "priority_rank": 0
    }
  ],
  "strategic_priorities": [
    {
      "title": "Reduce turnover 15%",
      "description": "...",
      "owner_user_id": 45,
      "target_date": "2026-12-31",
      "success_measures": "...",
      "org_kpi": "...",
      "readiness_indicator": "...",
      "related_groups": "...",
      "status": "not_started",
      "priority_rank": 0,
      "related_readiness": "Leadership Pipeline"
    }
  ],
  "learning": {
    "assumptions": "...",
    "risks": "...",
    "opportunities": "...",
    "learning_objectives": "...",
    "leadership_questions": "..."
  }
}
```

### Data source per step

| Step | Storage | `plan_context` key |
|------|---------|-------------------|
| 1 Foundation | `wp_fusion_arps` (mission, vision, …) | `foundation` |
| 2 Future State | `wp_fusion_arp_future_states` | `future_state` |
| 3 Readiness | `wp_fusion_arp_readiness_priorities` | `readiness_priorities[]` |
| 4 Strategic | `wp_fusion_arp_strategic_priorities` | `strategic_priorities[]` |
| 5 Learning | `wp_fusion_arp_learnings` | `learning` |
| ARP meta | `wp_fusion_arps` | `arp` |

API: `GET/POST /api/v1/arps/{id}/foundation`, `future-state`, `learning`, `plan`

---

## Response from LLM

```json
{
  "assessment": {
    "strategic_alignment": {
      "score": 84,
      "label": "Strong Alignment",
      "color": "#5f9a3f",
      "summary": "Paragraph evaluating mission/future state/strategic link...",
      "strengths": [
        "Clear connection between future state and strategic priorities",
        "Well-defined readiness capabilities aligned to COR"
      ]
    },
    "readiness_assessment": {
      "score": 76,
      "label": "Readiness Score",
      "color": "#c4a035",
      "summary": "Paragraph on organizational readiness...",
      "strengths_count": 7,
      "development_count": 4,
      "critical_gaps_count": 1
    },
    "gaps": [
      {
        "area": "Data Analytics Capability",
        "description": "Limited analytics may impact measurement...",
        "impact": "High",
        "priority": "Medium"
      }
    ],
    "priority_alignment": {
      "score": 82,
      "label": "Alignment Score",
      "color": "#5f9a3f",
      "summary": "Strategic priorities alignment narrative...",
      "dimensions": [
        { "label": "Future State Alignment", "percent": 88 },
        { "label": "Readiness Priority Alignment", "percent": 84 },
        { "label": "Resource Alignment", "percent": 78 },
        { "label": "Timeline Alignment", "percent": 76 }
      ]
    },
    "risk_summary": {
      "high": 2,
      "medium": 4,
      "low": 6,
      "strengths": 3
    },
    "focus_areas": [
      "Strengthen data analytics capabilities...",
      "Build change management capacity..."
    ]
  },
  "model": "gpt-4o-mini",
  "tokens_used": 1226,
  "cost_usd": 0.00043,
  "prompt_tokens": 678,
  "completion_tokens": 548
}
```

### Normalization rules (`ArpReadinessReviewNormalizer`)

| Field | Rule |
|-------|------|
| Donut `score` | 0–100 integer |
| `color` | `#5f9a3f` if score ≥ 80, `#c4a035` if ≥ 65, else `#ea580c` (LLM hex override if valid) |
| Gap `impact`, `priority` | `High` \| `Medium` \| `Low` |
| `dimensions[].percent` | 0–100 |
| `strengths`, `focus_areas` | Max 6 / 8 strings |
| `risk_summary.*` | Non-negative integers |

---

## Database storage

**Table:** `wp_fusion_arp_ai_assessments`

| Column | Type | Content |
|--------|------|---------|
| `id` | bigint | PK |
| `arp_id` | bigint | FK `wp_fusion_arps` |
| `assessment` | longtext JSON | Assessment object (sections 6.1–6.6) |
| `leadership_context` | text | **Manual** executive input — not from AI |
| `insight_model` | varchar(60) | |
| `tokens_used` | int | |
| `cost_usd` | decimal(10,4) | |
| `created_at` | timestamp | Generate timestamp |

**Regenerate:** Always **inserts a new row**. `leadership_context` is copied from the previous row.

---

## Laravel → WordPress response

### GET load / POST generate (success)

```json
{
  "success": true,
  "data": {
    "has_assessment": true,
    "assessment": { "... full assessment object ..." },
    "leadership_context": "Executive notes for the year...",
    "insight_model": "gpt-4o-mini",
    "generated_at": "2026-07-21T01:00:00+00:00",
    "can_edit": true,
    "tokens_used": 1226,
    "cost_usd": 0.00043
  }
}
```

### PATCH leadership context

```json
{
  "success": true,
  "data": {
    "leadership_context": "...",
    "saved_at": "1:05 AM"
  }
}
```

---

## Wizard Step 6 UI mapping

| UI section | JSON path | Components |
|------------|-----------|------------|
| **6.1** Strategic Alignment Summary™ | `assessment.strategic_alignment` | Donut score, summary, checklist `strengths[]` |
| **6.2** Organizational Readiness Assessment™ | `assessment.readiness_assessment` | Donut + strengths/development/gaps counts |
| **6.3** Potential Gaps™ | `assessment.gaps[]` | Table: area, description, impact, priority |
| **6.4** Priority Alignment™ | `assessment.priority_alignment` | Donut + progress bars `dimensions[]` |
| **6.5** Risk Summary™ | `assessment.risk_summary` | 4 cards: high/medium/low/strengths |
| **6.6** Suggested Areas of Focus™ | `assessment.focus_areas[]` | Focus list |
| Leadership Context™ | `leadership_context` (separate column) | Editable textarea, max 2000 chars |

**Before generate:** Sections 6.1–6.6 are **empty**. **Generate AI Insights** / **Regenerate AI Insights** button sits above 6.1.

---

## Dashboard

There is currently **no** wp-admin history page for ARP AI (unlike 1-on-1 Brief/Synthesis History).

Query example:

```sql
SELECT id, arp_id, insight_model, tokens_used, cost_usd, created_at
FROM wp_fusion_arp_ai_assessments
WHERE arp_id = 42
ORDER BY id DESC;
```

The `assessment` column holds the full JSON; `leadership_context` holds manual text.

---

## Code references

| File | Role |
|------|------|
| `app/Services/ArpPlanContextService.php` | Assemble Steps 1–5 |
| `app/Services/ArpAiService.php` | LLM bridge + DB insert |
| `app/Services/ArpReadinessReviewNormalizer.php` | UI schema |
| `app/Http/Controllers/Api/ArpController.php` | API endpoints |
| `arp-ai-review-service.php` | WP AJAX bridge |
| `steps/step-6-ai-review.php` | Dynamic render |
| `Xfusion-llm/routers/arp.py` | LLM endpoint |
| `database/sql/wp_fusion_arp_wizard.sql` | Table schema |
