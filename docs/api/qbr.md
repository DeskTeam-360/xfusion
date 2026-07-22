# QBR API (`/api/v1/qbrs`)

Controller: `App\Http\Controllers\Api\QbrController`  
WordPress: `[fusion_qbr_wizard]` + `quarterly-business-review/qbr-picker.php` bridge.

**Primary prefix:** `/api/v1/qbrs/*`  
**Legacy alias:** `/api/v1/qbr/*` (same routes)

**Storage:** Laravel is the system of record for all wizard steps (1–7).

**Permissions:** Group **leaders** can create/edit/generate/publish; group **members** can view read-only.

---

## Picker & QBR meta

| Method | Path | Query / body | Description |
|--------|------|--------------|-------------|
| GET | `/leadable-companies?user_id=` | | Groups user leads (create picker) |
| GET | `/list?user_id=` | | QBRs visible to user |
| POST | `/` | `user_id`, `company_group_id`, `quarter`, `year`, … | Create or resume QBR |
| GET | `/{qbr}?user_id=` | | Single QBR + `can_edit` |

**Unique constraint:** one QBR per `(company_group_id, quarter, year)`

---

## Evidence (Steps 1–2)

| Method | Path | Description |
|--------|------|-------------|
| POST | `/{qbr}/evidence/generate` | Step 1 — build & store evidence snapshot |
| GET | `/{qbr}/evidence` | Step 2 — read latest snapshot |
| GET | `/{qbr}/kpis` | Custom KPIs (Step 2) |
| POST | `/{qbr}/kpis` | Replace-all KPI save (`user_id`, `items[]`) |

**DB:** `wp_fusion_qbr_evidence_snapshots`, `wp_fusion_qbr_kpis`  
**Service:** `QbrEvidenceService::buildSnapshot()` — aggregates COR evaluations, 1-on-1, ARP objectives, prior commitments (no raw GF answers)

---

## AI Organizational Assessment (Step 3)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/{qbr}/assessment?user_id=` | Latest assessment + leadership context |
| POST | `/{qbr}/assessment/generate` | Generate via LLM (fallback composer if LLM down) |
| PATCH | `/assessment/context` | Save `leadership_context`, `agreement_rating` |
| POST | `/assessment/context` | Same (WP bridge compat) |

**LLM:** `POST /api/v1/qbr/assessment` (xfusion-llm)  
**DB:** `wp_fusion_qbr_ai_assessments`  
**Schema:** [../ai-insights/qbr-organizational-assessment.md](../ai-insights/qbr-organizational-assessment.md)

---

## Collaboration (Step 4)

| Method | Path | Description |
|--------|------|-------------|
| PATCH/POST | `/{qbr}/discussion-notes` | Save collaborative discussion notes |
| GET | `/{qbr}/decisions` | Key decisions list |
| POST | `/{qbr}/decisions` | Replace-all decisions save |

**DB:** `wp_fusion_qbrs.discussion_notes`, `wp_fusion_qbr_decisions`

---

## Commitments (Step 5)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/{qbr}/commitments` | List quarterly commitments (max 5) |
| POST | `/{qbr}/commitments` | Replace-all save |

**DB:** `wp_fusion_qbr_commitments`

---

## AI Organizational Synthesis (Step 6)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/{qbr}/synthesis?user_id=` | Latest synthesis JSON |
| POST | `/{qbr}/synthesis/generate` | Generate via LLM (fallback composer if LLM down) |

**LLM:** `POST /api/v1/qbr/synthesis` (xfusion-llm)  
**DB:** `wp_fusion_qbr_ai_syntheses`  
**Schema:** [../ai-insights/qbr-organizational-synthesis.md](../ai-insights/qbr-organizational-synthesis.md)

Context assembled from evidence snapshot, Step 3 assessment, leadership context, discussion notes, and Step 5 commitments.

---

## Publish (Step 7)

| Method | Path | Description |
|--------|------|-------------|
| POST | `/{qbr}/publish` | Publish QBR; writes `FusionEvidenceLog` |
| POST | `/{qbr}/archive` | Archive QBR |

---

## AI fallback behavior

Unlike ARP (returns HTTP 502 when LLM fails), QBR follows the **1-on-1 pattern**:

1. Try Xfusion-llm
2. On failure → deterministic composer (`QbrAssessmentFromEvidenceService` / `QbrSynthesisFromContextService`)
3. Response includes `llm_error` when fallback was used

---

## SQL bootstrap

| Scenario | Script |
|----------|--------|
| Fresh install | `database/sql/wp_fusion_core.sql` |
| Existing DB (wizard columns/tables) | `database/sql/wp_fusion_qbr_wizard.sql` |

---

## WordPress bridge

| Area | Path |
|------|------|
| Shortcode | `includes/quarterly-business-review/qbr-wizard-shortcode.php` |
| Picker / proxy | `includes/quarterly-business-review/qbr-picker.php` |
| Step services | `qbr-evidence-service.php`, `qbr-assessment-service.php`, `qbr-synthesis-service.php`, … |
