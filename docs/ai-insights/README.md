# AI Insights

JSON schemas, LLM contracts, and product planning for FUSION AI-generated insights.

For REST endpoints (save/load/generate from WordPress), see **[../api/](../api/README.md)**.

## Audience

| Document | Who |
|----------|-----|
| [planner-overview.md](./planner-overview.md) | Product, UAT, planning agents — flows & acceptance criteria |
| Feature docs below | Engineering — request/response JSON, normalization, UI mapping |

## Features

| Document | Product | Wizard | Generate button | LLM endpoint | DB table |
|----------|---------|--------|-----------------|--------------|----------|
| [one-on-one-meeting-brief.md](./one-on-one-meeting-brief.md) | 1-on-1 AI Meeting Brief™ | Step 2 | **Step 2** | `POST /api/v1/one-on-one/meeting-brief` | `wp_fusion_one_on_one_ai_briefs` |
| [one-on-one-meeting-synthesis.md](./one-on-one-meeting-synthesis.md) | 1-on-1 AI Meeting Synthesis™ | Step 6 | **Step 6** | `POST /api/v1/one-on-one/meeting-synthesis` | `wp_fusion_one_on_one_ai_syntheses` |
| [arp-readiness-review.md](./arp-readiness-review.md) | ARP AI Readiness Review™ | Step 6 | Step 6 | `POST /api/v1/arp/readiness-review` | `wp_fusion_arp_ai_assessments` |
| [qbr-organizational-assessment.md](./qbr-organizational-assessment.md) | QBR AI Organizational Assessment™ | Step 3 | Step 3 | `POST /api/v1/qbr/assessment` | `wp_fusion_qbr_ai_assessments` |
| [qbr-organizational-synthesis.md](./qbr-organizational-synthesis.md) | QBR AI Organizational Synthesis™ | Step 6 | Step 6 | `POST /api/v1/qbr/synthesis` | `wp_fusion_qbr_ai_syntheses` |

## Laravel → LLM configuration

```env
XFUSION_LLM_API_URL=http://<llm-host>:8000
XFUSION_LLM_API_KEY=<same as API_KEY in xfusion-llm .env>
```

Diagnostics: `php artisan xfusion:llm-probe`  
Auth details: [../api/authentication.md](../api/authentication.md)

## wp-admin history

| Feature | Admin page |
|---------|------------|
| 1-on-1 Brief | XFusion LLM → **1-on-1 Brief History** |
| 1-on-1 Synthesis | XFusion LLM → **1-on-1 Synthesis History** |
| ARP Readiness Review | **Not yet** — data in DB + wizard only |
| QBR Assessment / Synthesis | **Not yet** — data in DB + wizard only |

## Related services (Laravel)

| Feature | Service classes |
|---------|-----------------|
| 1-on-1 Brief / Synthesis | `OneOnOneAiService`, `MeetingBriefFromEvidenceService`, `MeetingSynthesisFromContextService` |
| ARP Review | `ArpAiService`, `ArpPlanService`, `ArpReadinessReviewNormalizer`, `ArpEvidenceService` |
| QBR Assessment / Synthesis | `QbrAiService`, `QbrAssessmentFromEvidenceService`, `QbrSynthesisFromContextService`, `QbrEvidenceService` |

Prompts live in the **xfusion-llm** repo under `prompts/`.
