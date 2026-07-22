# QBR — AI Organizational Synthesis™ (Step 6)

Data structure reference for generating, storing, and displaying **AI Organizational Synthesis** in the Quarterly Business Review wizard.

## Data flow

```
WordPress Steps 1–5 (evidence, assessment, notes, commitments in Laravel DB)
→ user clicks Generate on Step 6
→ Laravel QbrAiService → Xfusion-llm POST /api/v1/qbr/synthesis
→ normalize → wp_fusion_qbr_ai_syntheses.synthesis
→ Step 6 panel
```

| Layer | Location |
|-------|----------|
| LLM endpoint | `POST /api/v1/qbr/synthesis` |
| Laravel service | `App\Services\QbrAiService::generateSynthesis()` |
| Fallback composer | `App\Services\QbrSynthesisFromContextService` |
| System prompt | `Xfusion-llm/prompts/qbr_synthesis_system.md` |
| ChromaDB RAG | Category `fusion_qbr` |
| DB table | `wp_fusion_qbr_ai_syntheses` |
| Admin dashboard | **Not yet** — data in DB + wizard only |
| Wizard UI | `steps/step-6-synthesis.php`, `qbr-synthesis-service.php` |

Full REST API: [../api/qbr.md](../api/qbr.md)

---

## Request to LLM

Laravel assembles `context` in `QbrController::generateSynthesis()`:

```json
{
  "qbr_id": 12,
  "context": {
    "evidence": { },
    "assessment": { },
    "leadership_context": "Optional leader commentary from Step 3",
    "agreement_rating": "agree | partial | disagree",
    "discussion_notes": "HTML or plain text from Step 4",
    "commitments": [
      {
        "title": "Improve 1-on-1 completion",
        "description": "...",
        "owner_user_id": 45,
        "priority": "high",
        "status": "open"
      }
    ]
  }
}
```

---

## Response from LLM

```json
{
  "synthesis": {
    "executive_summary": "2-4 sentence paragraph",
    "organizational_readiness_summary": {
      "score": 72,
      "trend": "up",
      "narrative": "Readiness is improving based on available evidence."
    },
    "organizational_strengths": ["..."],
    "organizational_opportunities": ["..."],
    "key_risks": ["..."],
    "quarterly_focus": ["..."],
    "commitment_summary": {
      "total": 3,
      "high_priority": 1,
      "in_progress": 1,
      "not_started": 2
    },
    "recommended_areas_of_attention": ["..."],
    "leadership_context_considered": true,
    "discussion_notes_considered": false
  },
  "model": "gpt-4o-mini",
  "tokens_used": 3100,
  "cost_usd": 0.0028
}
```

### Normalization (Python)

- `commitment_summary` counts recomputed from `context.commitments` when present
- `leadership_context_considered` / `discussion_notes_considered` set from actual payload content
- String lists capped at 5 items (attention list max 4)

---

## Regenerate behavior

Each generate appends a new row to `wp_fusion_qbr_ai_syntheses`. Prior versions remain in DB (no admin UI yet).

---

## Fallback composer

When LLM is unavailable, Laravel stores synthesis with `insight_model: context-composer`. Wizard API may return `llm_error` explaining why fallback was used.
