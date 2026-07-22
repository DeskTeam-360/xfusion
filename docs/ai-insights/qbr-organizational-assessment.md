# QBR — AI Organizational Assessment™ (Step 3)

Data structure reference for generating, storing, and displaying **AI Organizational Assessment** in the Quarterly Business Review wizard.

## Data flow

```
WordPress Step 1/2 (evidence snapshot in Laravel DB)
→ user clicks Generate on Step 3
→ Laravel QbrAiService → Xfusion-llm POST /api/v1/qbr/assessment
→ normalize → wp_fusion_qbr_ai_assessments.assessment
→ Step 3 panel + leadership context (editable)
```

| Layer | Location |
|-------|----------|
| LLM endpoint | `POST /api/v1/qbr/assessment` |
| Laravel service | `App\Services\QbrAiService::generateAssessment()` |
| Fallback composer | `App\Services\QbrAssessmentFromEvidenceService` |
| System prompt | `Xfusion-llm/prompts/qbr_assessment_system.md` |
| ChromaDB RAG | Category `fusion_qbr` |
| DB table | `wp_fusion_qbr_ai_assessments` |
| Admin dashboard | **Not yet** — data in DB + wizard only |
| Wizard UI | `steps/step-3-assessment.php`, `qbr-assessment-service.php` |

Full REST API: [../api/qbr.md](../api/qbr.md)

---

## Request to LLM

Laravel sends:

```json
{
  "qbr_id": 12,
  "evidence": { }
}
```

### `evidence` snapshot (from `QbrEvidenceService`)

Key fields the LLM analyzes:

| Field | Description |
|-------|-------------|
| `overall_readiness_score` | 0–100 or null |
| `overall_readiness_trend` | `up`, `down`, `flat`, or null |
| `cor_capability_trends` | Five COR capabilities with scores |
| `behavioral_driver_trends` | Five behavioral drivers |
| `one_on_one_completion` | `{ rate, completed, scheduled }` |
| `one_on_one_summaries` | Completed 1-on-1 Step 6 `meeting_summary` (items + details) for group members in period |
| `activity_participation` | Transform / Sustain / Revitalize participation — `by_program`, `participants[]` |
| `tool_utilization` | Tool List submissions (`wp_course_groups.tools = 1`) — `submitted_count`, `tools_submitted`, `by_tool[]` |
| `assessment_completion` | Individual Insights completion rate |
| `commitment_completion` | Prior quarter commitment follow-through |
| `qbr_objectives_progress` | ARP objectives progress for the group |
| `kpis` | Custom KPIs from Step 2 |
| `readiness_indicators` | Composite indicator flags |

**Privacy rule:** Only aggregated evidence — never raw Gravity Forms field values.

---

## Response from LLM

```json
{
  "assessment": {
    "overall_readiness": {
      "score": 72,
      "label": "Moderate Strength",
      "trend": "up"
    },
    "confidence_level": {
      "percent": 75,
      "label": "Based on evaluation coverage for 8 of 12 group members."
    },
    "cor_capability_assessment": [
      { "capability": "alignment", "score": 80, "label": "Strength" },
      { "capability": "accountability", "score": 65, "label": "Developing" }
    ],
    "top_strengths": ["..."],
    "top_opportunities": ["..."],
    "emerging_risks": ["..."],
    "emerging_opportunities": ["..."]
  },
  "model": "gpt-4o-mini",
  "tokens_used": 2400,
  "cost_usd": 0.0021,
  "prompt_tokens": 1800,
  "completion_tokens": 600
}
```

### Normalization (Python)

- Scores clamped 0–100
- All five COR capabilities always present
- Labels: `Strength` (≥80), `Developing` (50–79), `Opportunity` (<50), `No data`
- String lists capped at 5 items

---

## Leadership context (human input, Step 3)

Saved separately via Laravel — not sent to LLM on generate unless included in synthesis context:

| Field | Column |
|-------|--------|
| Leadership context | `leadership_context` |
| Agreement rating | `agreement_rating` |

Preserved across regenerate (copied from previous assessment row).

---

## Fallback composer

When LLM is unavailable, Laravel stores assessment with `insight_model: evidence-composer` and `tokens_used: 0`. Response may include `llm_error` in the wizard API envelope.
