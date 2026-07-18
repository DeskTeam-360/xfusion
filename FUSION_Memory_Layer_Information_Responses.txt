# FUSION Memory Layer — Outstanding Information Requests
## Developer Responses (based on current `xfusion` codebase)

**Prepared for:** FUSION Memory Layer Developer Functional Specification v1.0  
**Source repo:** `xfusion` (Laravel + WordPress plugin)  
**Date:** July 2026

> **Scope note:** This document is based on the Laravel/WordPress repository (`xfusion`) and cross-references the separate Python FastAPI service (`Xfusion-llm` at `DeskTeam-360/xfusion-llm`). ChromaDB internals, embedding logic, and some AI endpoint implementations are confirmed from `Xfusion-llm` where noted; items marked "not confirmed" still require infrastructure team input.

---

## 1. Existing Database

### Architecture

- **Single shared MySQL database** used by both WordPress and Laravel.
- WordPress tables use the `wp_` prefix (configurable via `WP_PREFIX`).
- Laravel accesses WordPress tables via the Corcel `wordpress` connection (`config/corcel.php`).
- FUSION operating-object tables use prefix `wp_fusion_*`.
- **No foreign key constraints** on `wp_fusion_*` tables by design (see `database/sql/wp_fusion_core.sql` notes). Referential integrity is enforced in application code.

### Conceptual ERD (Memory-Relevant Entities)

```
wp_users ──┬── wp_company_employees ── wp_companies
           ├── wp_company_group_details ── wp_company_groups
           ├── wp_gf_entry ── wp_gf_entry_meta
           ├── wp_xfusion_result_evaluations (AI Insights)
           └── wp_fusion_one_on_ones (leader + employee)
                    └── wp_fusion_one_on_one_conversations
                             ├── wp_fusion_one_on_one_ai_briefs
                             ├── wp_fusion_one_on_one_preparations
                             ├── wp_fusion_one_on_one_notes
                             ├── wp_fusion_one_on_one_commitments
                             └── wp_fusion_one_on_one_ai_syntheses

wp_course_groups ── wp_course_group_details ── wp_course_lists (→ wp_gf_form_id)
wp_course_scoring_groups ── wp_course_scoring_group_details (form_id, field_id, weight)

wp_posts (xfusion_knowledge CPT) ── wp_postmeta (category, ChromaDB sync status)

wp_fusion_evidence_log (planned — schema only, not yet written to by application code)
```

---

### Table Inventory

#### Organizations & Users

| Table | Key Columns | Status | Model / Access |
|-------|-------------|--------|----------------|
| `wp_companies` | `id`, `user_id` (owner), `title`, `logo_url`, `company_url` | Active | `App\Models\Company` |
| `wp_company_employees` | `id`, `user_id`, `company_id` | Active | `App\Models\CompanyEmployee` |
| `wp_company_groups` | `id`, `company_id`, `title`, `description` | Active | `App\Models\CompanyGroup` |
| `wp_company_group_details` | `id`, `company_group_id`, `user_id`, `status` (`member`/`leader`) | Active | `App\Models\CompanyGroupDetail` |
| `wp_users` | Standard WordPress user table | Active | Corcel `User` |
| `wp_usermeta` | `user_id`, `meta_key`, `meta_value` | Active | `App\Models\WpUserMeta` |

Relevant usermeta keys: `keap_contact_id`, `keap_tags`, `_sfwd-course_progress` (LearnDash).

---

#### Activities & Assessments

| Table | Key Columns | Status | Notes |
|-------|-------------|--------|-------|
| `wp_course_groups` | `id`, `title`, `type`, `tools` (0=Activities, 1=Development Tools), `order_group` | Active | `App\Models\CourseGroup` |
| `wp_course_lists` | `id`, `wp_gf_form_id`, `lms_topic_id`, `course_title`, `keap_tag` | Active | Links GF form + LearnDash topic |
| `wp_course_group_details` | `course_group_id`, `course_list_id`, `orders` | Active | Junction table |
| `wp_gf_entry` | `id`, `form_id`, `created_by` (user), `date_created`, `status` | Active | Raw Gravity Forms submissions |
| `wp_gf_entry_meta` | `entry_id`, `meta_key`, `meta_value` | Active | Raw field answers |
| `wp_course_scoring_groups` | `id`, `title`, `description` | Active | 5 Behavioral Drivers + 5 COR capabilities |
| `wp_course_scoring_group_details` | `course_scoring_group_id`, `form_id`, `field_id`, `weight` | Active | Weight: 1.0=primary, 0.5=secondary, 0.25=tertiary, 0=skip |

---

#### AI Insights

| Table | Key Columns | Status | Notes |
|-------|-------------|--------|-------|
| `wp_xfusion_result_evaluations` | See below | **Active (production)** | Primary AI insight store |

**`wp_xfusion_result_evaluations` — full column list:**

| Column | Description |
|--------|-------------|
| `id` | Primary key |
| `user_id` | WordPress user ID |
| `created_at`, `evaluated_at`, `inserted_at` | Timestamps |
| `company_information` | WP post ID for company knowledge (0 = default) |
| `scoring_group_id` | `0` = COR Unified Insights; `>0` = legacy per-category |
| `scoring_group_title` | e.g. `COR Unified Insights` |
| `score` | 0–100 (average of COR capabilities × 20 for unified) |
| `evaluation_input` | LONGTEXT JSON — request payload + `generation_context` + `gauge_snapshots` |
| `evaluation` | LONGTEXT JSON — AI output (see structure below) |
| `status` | `draft` \| `published` \| `sandbox` |
| `insight_model` | e.g. `gpt-4o-mini` |
| `prompt_version_id`, `prompt_version_label` | Active prompt version at generation time |
| `prompt_tokens`, `completion_tokens`, `tokens_used`, `cost_usd` | Usage tracking |

**`evaluation` JSON structure (COR Unified):**
```json
{
  "cor_organization_capabilities": "75-100 word insight",
  "performance": {
    "get_real": { "strength": "...", "opportunity": "..." },
    "fill_buckets": { "strength": "...", "opportunity": "..." },
    "be_intentional": { "strength": "...", "opportunity": "..." },
    "foster_grit": { "strength": "...", "opportunity": "..." },
    "drive_growth": { "strength": "...", "opportunity": "..." }
  },
  "key_observation": "100-150 word overall insight",
  "recommended_focus_area": "25-50 words"
}
```

Migration: `database/migrations/2026_06_03_000000_create_wp_xfusion_result_evaluations_table.php`  
WordPress access: `app/Http/Plugin/xfusion-plugin/includes/result-evaluation.php` (no Eloquent model).

---

#### Knowledge Base (RAG source, vectors in ChromaDB)

| Table | Key Columns | Status |
|-------|-------------|--------|
| `wp_posts` | `post_type = xfusion_knowledge` | Active |
| `wp_postmeta` | `_xfusion_knowledge_category`, sync status fields | Active |

Model: `App\Models\XfusionKnowledge`. Vectors stored in ChromaDB (not MySQL).

---

#### Operating Objects — 1-on-1 (IMPLEMENTED)

| Table | Key Columns | Status |
|-------|-------------|--------|
| `wp_fusion_one_on_ones` | `company_id`, `leader_user_id`, `employee_user_id`, `status` | Active |
| `wp_fusion_one_on_one_conversations` | `one_on_one_id`, `scheduled_at`, `held_at`, `meeting_link`, `status` | Active |
| `wp_fusion_one_on_one_ai_briefs` | `conversation_id`, `brief` (JSON), `insight_model`, `prompt_version_id` | Active — **multiple rows per conversation** (regenerate appends; `brief()` = latest) |
| `wp_fusion_one_on_one_preparations` | `conversation_id`, `author_role`, `content`, `is_revealed` | Active |
| `wp_fusion_one_on_one_notes` | `conversation_id`, `section`, `note`, `created_by` | Active |
| `wp_fusion_one_on_one_commitments` | `conversation_id`, `title`, `priority`, `behavioral_driver`, `owner_role`, `status`, `due_date` | Active |
| `wp_fusion_one_on_one_ai_syntheses` | `conversation_id`, `synthesis` (JSON), `insight_model`, `prompt_version_id` | Active — **multiple rows per conversation** (regenerate appends; `synthesis()` = latest) |

7 Eloquent models in `app/Models/`: `OneOnOne`, `OneOnOneConversation`, `OneOnOneAiBrief`, `OneOnOnePreparation`, `OneOnOneNote`, `OneOnOneCommitment`, `OneOnOneAiSynthesis`. Schema: `database/sql/wp_fusion_one_on_one_wizard.sql`.

---

#### Operating Objects — ARP, QBR, 360, ARR

Defined in `database/sql/wp_fusion_core.sql`. **No Eloquent models, Laravel controllers, or `/api/v1` routes exist yet** for these operating objects.

| Component | Tables | DB / API Status | WordPress UI |
|-----------|--------|-----------------|--------------|
| ARP | `wp_fusion_arps`, `_future_states`, `_readiness_priorities`, `_strategic_priorities`, `_learnings`, `_ai_assessments` | Schema only | `[fusion_arp_wizard]` — 7-step wizard shell (frontend; not yet persisting to `wp_fusion_arp_*`) |
| QBR | `wp_fusion_qbrs`, `_evidence_snapshots`, `_ai_assessments`, `_commitments`, `_ai_syntheses` | Schema only | Not started |
| 360 Review | `wp_fusion_360_reviews`, `_evidence_snapshots`, `_ai_assessments`, `_reflections`, `_commitments`, `_ai_syntheses` | Schema only | Not started |
| ARR | `wp_fusion_arrs`, `_evidence_snapshots`, `_ai_assessments`, `_renewal_recommendations`, `_ai_syntheses` | Schema only | Not started |

---

#### Memory Layer — Evidence Log (PLANNED)

| Table | Key Columns | Status |
|-------|-------------|--------|
| `wp_fusion_evidence_log` | `source_type`, `source_id`, `event_type`, `user_id`, `behavioral_driver`, `cor_capability`, `evidence_date`, `metadata` (JSON) | **Schema only — no application writes yet** |

`source_type` values: `one_on_one`, `qbr`, `360`, `arr`, `arp`, `result_evaluation`  
`metadata` must never contain raw Gravity Forms answers (privacy rule).

Evidence snapshots (`wp_fusion_qbr_evidence_snapshots`, `wp_fusion_360_evidence_snapshots`, `wp_fusion_arr_evidence_snapshots`) are also schema-only.

---

### Implementation Summary

| Area | Status |
|------|--------|
| Organizations, Groups, Users | Active |
| Activities, Assessments (GF + LearnDash) | Active |
| AI Insights (COR Unified) | Active |
| 1-on-1 Operating Object | Active (7 tables + API) |
| ARP | Schema only; WP wizard UI shell (`annual-readiness-plan/`) |
| QBR, 360, ARR | Schema only |
| `wp_fusion_evidence_log` | Schema only |
| Evidence snapshots | Schema only |

**Current memory behavior:** Evidence for 1-on-1 is aggregated on-the-fly in `app/Http/Plugin/xfusion-plugin/includes/one-on-one-wizard/step-1-evidence-service.php`. It reads from `wp_xfusion_result_evaluations`, `wp_gf_entry`, `wp_course_scoring_groups`, and `wp_fusion_one_on_one_*` tables. Nothing is written to `wp_fusion_evidence_log` yet.

**Wizard draft gap (important):** Steps 3–4 preparation and conversation notes are still saved to Gravity Forms draft via `save-draft.php` with TODO comments — they are **not always synced** to `wp_fusion_one_on_one_preparations` / `wp_fusion_one_on_one_notes` yet. `generate-synthesis` can accept `preparations`, `notes`, and `commitments` in the request body from the wizard so AI generation uses in-session draft data when DB rows are missing.

---

## 2. Existing APIs

### Request Flow Overview

```
Browser
  │
  ├─ WordPress AJAX (admin-ajax.php) ──────────────────────────────────────┐
  │     │                                                                   │
  │     ├─ COR Insights / legacy eval ──► FastAPI (Xfusion-llm) directly   │
  │     ├─ Knowledge sync ──────────────► FastAPI directly                 │
  │     ├─ 1-on-1 CRUD / wizard ────────► Laravel /api/v1/one-on-one/*    │
  │     ├─ Company / charts ────────────► Laravel /api/v1/companies/*     │
  │     └─ LearnDash / Keap ────────────► Laravel /api/* (legacy)          │
  │                                                                         │
  └─ Laravel Admin Panel (session auth) ──► FastAPI (knowledge upsert)     │
```

**Config:**
- Laravel base URL (WordPress): `XFUSION_LARAVEL_API_BASE` (default: `https://admin.sandbox.xperiencefusion.com`)
- FastAPI base URL: `XFUSION_LLM_API_URL` (default: `http://127.0.0.1:8000`)

---

### Laravel API Routes

**File:** `routes/api.php`

#### Legacy routes (no auth middleware)

| Method | Path | Purpose |
|--------|------|---------|
| POST | `/api/save-pdf-result` | Upload PDF result + Keap tag |
| POST | `/api/keap-gform/` | Keap tag from Gravity Form entry |
| POST | `/api/register/` | Register user to Keap |
| POST | `/api/next-course/` | Mark LearnDash topic complete + Keap tag |
| GET | `/api/user` | Sanctum user (not used by WP plugin) |

#### v1 routes — middleware `fusion.api` (Bearer token)

**Companies & Charts**

| Method | Path | Controller |
|--------|------|------------|
| GET | `/api/v1/companies` | `CompanyPublicController@index` |
| GET | `/api/v1/companies/{company}` | `CompanyPublicController@show` |
| GET | `/api/v1/companies/{company}/participation-charts` | `ParticipationChartsController@show` |
| GET | `/api/v1/course-groups` | `CourseGroupPublicController@index` |

**1-on-1 Alignment Capture**

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/api/v1/one-on-one/pairs` | List 1-on-1 pairs for a user |
| GET | `/api/v1/one-on-one/leader-team` | Leader's team members |
| GET | `/api/v1/one-on-one/meeting-dashboard` | Meeting dashboard data |
| POST | `/api/v1/one-on-one/schedule-for-employee` | Schedule meeting for employee |
| GET | `/api/v1/one-on-one/{oneOnOne}/employee-scoring` | Employee scoring data |
| GET | `/api/v1/one-on-one/{oneOnOne}/conversations` | List conversations |
| POST | `/api/v1/one-on-one/{oneOnOne}/conversations` | Schedule new conversation |
| POST | `/api/v1/one-on-one/conversations/{id}/preparation` | Submit preparation (private) |
| GET | `/api/v1/one-on-one/conversations/{id}/my-preparation` | Get own preparation |
| GET | `/api/v1/one-on-one/conversations/{id}/preparation-status` | Check reveal status |
| POST | `/api/v1/one-on-one/conversations/{id}/reveal` | Reveal preparations at meeting start |
| GET | `/api/v1/one-on-one/conversations/{id}/brief` | Get current AI brief |
| GET | `/api/v1/one-on-one/conversations/{id}/brief-history` | Brief version history |
| GET | `/api/v1/one-on-one/conversations/{id}/briefs/{brief}` | Specific brief version |
| POST | `/api/v1/one-on-one/conversations/{id}/generate-brief` | Generate AI brief |
| POST | `/api/v1/one-on-one/conversations/{id}/generate-synthesis` | Generate AI synthesis |
| GET | `/api/v1/one-on-one/conversations/{id}/synthesis` | Get current synthesis |
| GET | `/api/v1/one-on-one/conversations/{id}/synthesis-history` | Synthesis version history |
| GET | `/api/v1/one-on-one/conversations/{id}/syntheses/{synthesis}` | Specific synthesis version |
| GET/POST | `/api/v1/one-on-one/conversations/{id}/notes` | Conversation notes |
| GET | `/api/v1/one-on-one/conversations/{id}/evidence` | Evidence bundle for wizard Step 1 |
| GET/POST | `/api/v1/one-on-one/conversations/{id}/commitments` | Shared commitments |
| PATCH/POST | `/api/v1/one-on-one/commitments/{id}` | Update commitment |
| POST | `/api/v1/one-on-one/conversations/{id}/complete` | Mark conversation complete |
| POST | `/api/v1/one-on-one/conversations/{id}/status` | Update conversation status |

Controller: `app/Http/Controllers/Api/OneOnOneController.php`

---

### WordPress Plugin → Laravel Bridge

**Files:**
- `app/Http/Plugin/xfusion-plugin/includes/one-on-one-shortcode.php` — AJAX bridge to Laravel
- `app/Http/Plugin/xfusion-plugin/includes/one-on-one-wizard/one-on-one-wizard-shortcode.php` — shortcode `[fusion_one_on_one_wizard]`

WordPress AJAX actions map 1:1 to Laravel v1 endpoints above (e.g. `xfusion_oo_pairs` → `GET /pairs`, `xfusion_oo_generate_brief` → `POST /generate-brief`).

**Wizard-specific AJAX** (in `one-on-one-wizard/`):
- `xfoo_wizard_load_evidence` → `GET /conversations/{id}/evidence`
- `xfoo_wizard_generate_brief` → `POST /conversations/{id}/generate-brief`
- `xfoo_wizard_generate_synthesis` → `POST /conversations/{id}/generate-synthesis` (sends optional `preparations`, `notes`, `commitments` from wizard draft)

**WP Admin history (not in wizard UI):**
- `xfusion-one-on-one-briefs-admin.php` — LLM Prompts → 1-on-1 Brief History
- `xfusion-one-on-one-synthesis-admin.php` — LLM Prompts → 1-on-1 Synthesis History

**Company/charts:** `wordpress_xfusion_company_shortcode.php` → `/api/v1/companies/*`

**LearnDash/Keap:** `gravity-forms-course-list-mark-complete.php` → `POST /api/next-course`

---

### WordPress Plugin → FastAPI (Direct)

| File | FastAPI Endpoint | Trigger |
|------|-----------------|---------|
| `cor-unified-insights.php` | `POST /api/v1/evaluation/evaluate-unified` | Generate COR Unified Insights |
| `send-evaluation-shortcode.php` | `POST /api/v1/evaluation/evaluate` | Legacy per-category evaluation |
| `xfusion-knowledge-cpt.php` | `POST /api/v1/knowledge/upsert` | Save/update knowledge CPT |
| `xfusion-knowledge-cpt.php` | `DELETE /api/v1/knowledge/delete/{post_id}` | Delete knowledge vectors |

---

## 3. AI Endpoints

> **Note:** The Python FastAPI service (`Xfusion-llm`) is a separate repository not included here. Endpoint behavior below is documented from the Laravel/WordPress caller side. Internal OpenAI call logic, RAG retrieval implementation, and ChromaDB query details require review of the `Xfusion-llm` repo.

### Active Endpoints

#### `POST /api/v1/evaluation/evaluate-unified` — COR Unified Insights

- **Caller:** WordPress directly (`cor-unified-insights.php`)
- **Docs:** `AI.md`
- **Model:** Configurable in WP Settings (default: `gpt-4o-mini`)
- **RAG:** Loads all ChromaDB chunks with `category = "COR Performance"`

**Request:**
```json
{
  "user_id": 42,
  "created_at": "2026-06-03T10:30:00Z",
  "company_information": 0,
  "cor_organization_capabilities": {
    "alignment": 4.2,
    "accountability": 3.8,
    "communication": 4.0,
    "leadership": 3.5,
    "execution": 4.1
  },
  "performance": {
    "get_real": {
      "primary": [{ "question": "...", "answer": "..." }],
      "secondary": [],
      "tertiary": []
    }
  },
  "model": "gpt-4o-mini",
  "coach_prompt": "...",
  "user_prompt_template": "...",
  "prompt_version_id": "...",
  "prompt_version_label": "..."
}
```

**Response:** `evaluation` object + `token_usage`. Saved to `wp_xfusion_result_evaluations`.

---

#### `POST /api/v1/evaluation/evaluate` — Legacy Per-Category

- **Caller:** WordPress (`send-evaluation-shortcode.php`)
- **Request:**
```json
{
  "user_id": 42,
  "created_at": "ISO8601",
  "company_information": 0,
  "question_answers": [{ "question": "...", "answer": "..." }]
}
```

---

#### `POST /api/v1/knowledge/upsert` — Index Knowledge to ChromaDB

- **Callers:** WordPress CPT save, Laravel admin (`XfusionLlmKnowledgeService`)
- **Request:**
```json
{
  "wordpress_post_id": 123,
  "category": "COR Performance",
  "content": "plain text content"
}
```

---

#### `DELETE /api/v1/knowledge/delete/{wordpress_post_id}`

- **Callers:** WordPress CPT delete, Laravel model delete hook
- Removes vectors for the given post ID from ChromaDB.

---

#### `POST /api/v1/one-on-one/meeting-brief` — AI Meeting Brief

- **Caller:** Laravel `OneOnOneAiService` → FastAPI
- **Triggered by:** `POST /api/v1/one-on-one/conversations/{id}/generate-brief`
- **Privacy:** Only prior AI syntheses sent as context — never raw preparation text

**Request:**
```json
{
  "conversation_id": 1,
  "leader_user_id": 10,
  "employee_user_id": 20,
  "prior_syntheses": ["..."],
  "evidence_context": { },
  "system_prompt": "...",
  "prompt_version_id": "...",
  "prompt_version_label": "..."
}
```

**Response:** `{ "brief": {...}, "model", "tokens_used", "cost_usd" }`  
**Storage:** New row appended to `wp_fusion_one_on_one_ai_briefs` on each regenerate (history via `brief-history` / `briefs/{id}`).  
**Fallback:** `MeetingBriefFromEvidenceService` (`insight_model: evidence-composer`) if FastAPI fails.

---

#### `POST /api/v1/one-on-one/meeting-synthesis` — AI Meeting Synthesis

- **Caller:** Laravel `OneOnOneAiService` → FastAPI
- **Triggered by:** `POST /api/v1/one-on-one/conversations/{id}/generate-synthesis`
- **Privacy:** Preparation text only sent for the current conversation, after meeting

**Request:**
```json
{
  "conversation_id": 1,
  "leader_user_id": 10,
  "employee_user_id": 20,
  "preparations": { "employee": {...}, "leader": {...} },
  "notes": [{ "section": "...", "note": "..." }],
  "commitments": [{ "title", "description", "owner_role", "status" }],
  "system_prompt": "...",
  "prompt_version_id": "...",
  "prompt_version_label": "..."
}
```

**Response:** `{ "synthesis": {...}, "model", "tokens_used", "cost_usd" }`  
**Storage:** New row appended to `wp_fusion_one_on_one_ai_syntheses` on each regenerate (history via `synthesis-history` / `syntheses/{id}`). Wizard Step 6 shows latest only.  
**Post-processing:** `SynthesisCommitmentSummaryNormalizer` rebuilds `commitment_summary` counts/details from payload commitments (also done in `Xfusion-llm` `one_on_one.py`) to avoid duplicate commitment lines.  
**Fallback:** `MeetingSynthesisFromContextService` (`insight_model: context-composer`) if FastAPI fails.

---

### Planned Endpoints (not yet implemented)

From project architecture docs (`CLAUDE.md`):

| Endpoint | Component |
|----------|-----------|
| `POST /api/v1/qbr/organizational-assessment` | QBR |
| `POST /api/v1/qbr/organizational-synthesis` | QBR |
| `POST /api/v1/360/development-assessment` | 360 Review |
| `POST /api/v1/360/development-synthesis` | 360 Review |
| `POST /api/v1/arr/annual-assessment` | ARR |
| `POST /api/v1/arr/strategic-renewal-synthesis` | ARR |
| `POST /api/v1/arp/readiness-review` | ARP |

---

## 4. Prompt Management

### Storage Locations

| Location | What |
|----------|------|
| `wp_options.xfusion_llm_prompt_registry` | Primary registry — all active and historical prompt versions |
| `wp_options` (legacy, migrated) | `xfusion_llm_prompt_versions`, `xfusion_llm_active_prompt_id` |
| `wp_options` | `xfusion_llm_insight_model`, `xfusion_llm_insight_default_status` |
| File defaults (seed only) | `app/Http/Plugin/xfusion-plugin/prompts/*.md`, `AI-PROMPT.md` |

Prompts are **not stored in the database as primary storage** — they live in `wp_options` (WordPress). File `.md` files are used only to seed initial versions.

Laravel reads active prompts via `App\Services\WordPressLlmPromptService` (reads `wp_options`).

### Prompt Slots

| Slug | Used For | Default File |
|------|----------|--------------|
| `cor_unified_coach` | System prompt → `evaluate-unified` | `AI-PROMPT.md` |
| `cor_unified_user` | User template → `evaluate-unified` | `prompts/unified_user_prompt.md` |
| `one_on_one_brief_system` | System prompt → `meeting-brief` | `prompts/one_on_one_brief_system.md` |
| `one_on_one_synthesis_system` | System prompt → `meeting-synthesis` | `prompts/one_on_one_synthesis_system.md` |

### Versioning Approach

1. WP Admin UI ("LLM Prompts" menu) allows creating multiple versions per slug.
2. Each version has: `id`, `label`, `content`, `created_at`.
3. One `active_id` per slug — only the active version is sent to FastAPI.
4. On every AI generation, `prompt_version_id` and `prompt_version_label` are:
   - Sent in the API request payload
   - Stored in the result record (`wp_xfusion_result_evaluations`, `wp_fusion_one_on_one_ai_briefs`, `wp_fusion_one_on_one_ai_syntheses`)

**Management files:**
- `app/Http/Plugin/xfusion-plugin/includes/xfusion-llm-prompts-registry.php`
- `app/Http/Plugin/xfusion-plugin/includes/xfusion-llm-prompts-admin.php`
- `app/Http/Plugin/xfusion-plugin/includes/xfusion-ai-insights-settings.php`

---

## 5. Existing Memory Components

### What Exists Today

| Component | Implementation | Persistent? |
|-----------|---------------|-------------|
| COR AI Insights | `wp_xfusion_result_evaluations` | Yes |
| 1-on-1 evidence aggregation | `step-1-evidence-service.php` (on-the-fly query) | No — computed at request time |
| 1-on-1 AI briefs | `wp_fusion_one_on_one_ai_briefs` (versioned rows) | Yes |
| 1-on-1 AI syntheses | `wp_fusion_one_on_one_ai_syntheses` (versioned rows) | Yes |
| 1-on-1 commitments | `wp_fusion_one_on_one_commitments` | Yes |
| Knowledge vectors (RAG) | ChromaDB via FastAPI | Yes (external store) |
| Evidence log | `wp_fusion_evidence_log` | **Schema only — not written to** |
| Evidence snapshots (QBR/360/ARR) | `wp_fusion_*_evidence_snapshots` | **Schema only** |

### Current Evidence Aggregation (1-on-1 Wizard Step 1)

**File:** `app/Http/Plugin/xfusion-plugin/includes/one-on-one-wizard/step-1-evidence-service.php`

Evidence blocks assembled on-the-fly from:

| Block | Source |
|-------|--------|
| Individual AI Insights | `wp_xfusion_result_evaluations` (unified, `scoring_group_id = 0`) |
| Activities completed | `wp_gf_entry` JOIN `wp_course_lists` JOIN `wp_course_groups` (`tools = 0`) |
| Development Tools completed | Same, `tools = 1` |
| Behavioral Driver scores | `wp_course_scoring_groups` + aggregated scores per user |
| Previous meetings + commitments | Laravel API → `wp_fusion_one_on_one_*` |

### Embedding & Retrieval

- **Embedding:** Handled inside `Xfusion-llm` (Python). Not visible from this repo.
- **Retrieval:** ChromaDB RAG with `category` metadata filter.
  - **Active:** `COR Performance` (`evaluate-unified`), `fusion_one_on_one` (`meeting-brief`, `meeting-synthesis` in `Xfusion-llm`)
  - **Planned:** `fusion_qbr`, `fusion_360`, `fusion_arp`, `fusion_arr`
- **Knowledge sync:** WordPress CPT save → `POST /api/v1/knowledge/upsert` → ChromaDB. Sync status tracked in `wp_postmeta`.

### Privacy Pipeline (Implemented)

```
Private Reflection → AI Pattern Extraction → Shared Insight
```

Concrete rules in code:
- 1-on-1 preparations: `is_revealed = 0` until meeting starts; neither party sees the other's prep beforehand
- Meeting Brief: only prior AI syntheses as context — never raw preparation text
- Meeting Synthesis: preparation text sent only for the current conversation, post-meeting
- QBR/ARR (planned): only `evaluation` JSON from `wp_xfusion_result_evaluations` — never raw GF answers

---

## 6. Authentication

**No JWT or shared session between WordPress, Laravel, and FastAPI.** Static Bearer tokens are used.

### Browser ↔ WordPress
- Standard WordPress login session (cookie-based)
- AJAX protected by `check_ajax_referer()` per feature namespace (e.g. `xfusion_one_on_one`, `xfoo_wizard_save_draft`)

### WordPress ↔ Laravel (`/api/v1/*`)

| Side | Config Key | File |
|------|-----------|------|
| Laravel | `FUSION_API_TOKEN` | `config/fusion_api.php`, `.env` |
| WordPress | `XFUSION_API_BEARER_TOKEN` | `wp-config.php` |
| WordPress base URL | `XFUSION_LARAVEL_API_BASE` | `one-on-one-shortcode.php` |

Middleware: `app/Http/Middleware/VerifyFusionApiToken.php`  
Behavior: if `FUSION_API_TOKEN` is empty → requests are **not blocked**; if set → `Authorization: Bearer {token}` required.

### WordPress / Laravel ↔ FastAPI (Xfusion-llm)

| Side | Config Key |
|------|-----------|
| Laravel | `XFUSION_LLM_API_KEY`, `XFUSION_LLM_API_URL` (`config/xfusion-llm.php`) |
| WordPress | `xfusion_llm_api_key` option or `XFUSION_LLM_API_KEY` constant |

Header: `Authorization: Bearer {XFUSION_LLM_API_KEY}` (optional if key is empty).

### Laravel Admin Panel
- Standard Laravel session auth (`auth` middleware on `routes/web.php`)

### Sanctum
- Route `GET /api/user` with `auth:sanctum` exists but is **not used** by the WordPress plugin.

---

## 7. Database Choice

**Confirmed from codebase configuration:**

- **Engine:** MySQL (MariaDB compatible)
- **Architecture:** Single shared database for WordPress + Laravel
- **Connection:** `DB_CONNECTION=mysql` (`.env.example`)
- **WordPress prefix:** `WP_PREFIX=wp_` (default)
- **Memory tables:** Planned to live in the **same database** (`wp_fusion_*` tables in `database/sql/wp_fusion_core.sql`)

> **Note — version not confirmed:** The specific MySQL/MariaDB version running in production/staging is not documented in this repository. Please confirm with the infrastructure team.

---

## 8. Background Processing

**From `.env.example` configuration:**

| System | Setting | Value |
|--------|---------|-------|
| Laravel Queue | `QUEUE_CONNECTION` | `database` (jobs table) |
| Laravel Cache | `CACHE_STORE` | `database` |
| Laravel Sessions | `SESSION_DRIVER` | `database` |

**Observed behavior in codebase:**
- AI generation (COR insights, 1-on-1 brief/synthesis) runs **synchronously** during the HTTP request — no queue dispatch found for AI calls
- Knowledge sync to ChromaDB runs synchronously on CPT save
- No WP-Cron jobs or Celery workers referenced in this repo

> **Note — production setup unknown:** Whether Laravel Queue workers, WP-Cron, or other schedulers are running in production/staging is not documented here. Please confirm with the infrastructure team.

---

## 9. ChromaDB

**What is confirmed from this codebase:**

| Aspect | Detail |
|--------|--------|
| Access method | Only via FastAPI (`Xfusion-llm`) — no direct ChromaDB client in Laravel or WordPress |
| Indexing | `POST /api/v1/knowledge/upsert` with `category` metadata |
| Deletion | `DELETE /api/v1/knowledge/delete/{wordpress_post_id}` |
| Active categories | `COR Performance` (COR Unified), `fusion_one_on_one` (1-on-1 brief/synthesis RAG) |
| Planned categories | `fusion_qbr`, `fusion_360`, `fusion_arp`, `fusion_arr` |
| Sync status tracking | `wp_postmeta`: `sync_status`, `synced_at`, `sync_error`, `chunks_added` |
| Config | `config/xfusion-llm.php` — category list, API URL, timeout |

**Files:**
- `app/Http/Plugin/xfusion-plugin/includes/xfusion-knowledge-cpt.php`
- `app/Services/XfusionLlmKnowledgeService.php`
- `app/Models/XfusionKnowledge.php`

> **Note — not confirmed from this repo:** Collection naming strategy, persistence location (disk path), backup approach, embedding model, chunk size, and retrieval parameters are all implemented inside `Xfusion-llm` (Python) which is not available in this repository. Please review the `Xfusion-llm` repo or confirm with the team that maintains it.

---

## 10. Logging & Monitoring

**What exists in codebase configuration:**

| Component | Setting |
|-----------|---------|
| Laravel logging | `LOG_CHANNEL=stack`, `LOG_STACK=single`, `LOG_LEVEL=debug` |
| AI call errors | `Log::warning()` / `Log::error()` in `OneOnOneAiService`, `XfusionLlmKnowledgeService` |
| Laravel Log model | `App\Models\Log` — used for PDF upload events |
| Broadcast | `BROADCAST_CONNECTION=log` (not actively used) |

> **Note — production monitoring unknown:** No APM, distributed tracing, error tracking (e.g. Sentry), or centralized log aggregation is configured or referenced in this repository. Please confirm with the infrastructure team what is used in production/staging.

---

## 11. Backup & Recovery

> **Note — not documented in this repository.** Database backup cadence, ChromaDB backup strategy, and AI service recovery procedures are not defined in the codebase or project docs available to the development team. Please confirm with the infrastructure/operations team.

---

## 12. Deployment

**What can be inferred from codebase:**

| Component | Evidence |
|-----------|----------|
| Laravel | Standard Laravel app (`artisan`, `composer.json`, Vite) |
| WordPress plugin | Lives inside Laravel repo at `app/Http/Plugin/xfusion-plugin/` |
| FastAPI | Separate service, configured via `XFUSION_LLM_API_URL` |
| Staging reference | Default Laravel API base: `https://admin.sandbox.xperiencefusion.com` |

> **Note — deployment workflow unknown:** CI/CD pipelines, deployment scripts, environment promotion process (dev → staging → production), and hosting provider details are not documented in this repository. Please confirm with the infrastructure team.

---

## 13. Security

**What is confirmed from codebase:**

| Aspect | Implementation |
|--------|---------------|
| API tokens | Static Bearer tokens in `.env` / `wp-config.php` (not JWT) |
| Token optional | If `FUSION_API_TOKEN` / `XFUSION_LLM_API_KEY` empty, endpoints are not blocked |
| Environment separation | `.env` per environment (`.env.example` provided) |
| Secrets in repo | `.env` is gitignored; `.env.example` has empty placeholders |
| WordPress AJAX | Nonce verification per action |
| 1-on-1 privacy | `is_revealed` flag on preparations; role-based access in controller |
| Raw GF data | Never sent to QBR/ARR AI prompts (documented rule; enforced in 1-on-1 brief) |

> **Note — infrastructure security unknown:** TLS configuration, firewall rules, secrets management service (e.g. AWS Secrets Manager, Vault), and network isolation between services are not documented in this repository. Please confirm with the infrastructure team.

---

## 14. Acceptance Constraints

> **Note — not documented in this repository.** Performance targets, expected concurrent users, latency requirements, and SLA definitions are not defined in the codebase or available project documentation. Please confirm with the product/client team.

**Informal observations from code:**
- AI generation is synchronous (user waits for OpenAI response)
- FastAPI timeout: 60 seconds (`XFUSION_LLM_TIMEOUT`)
- PDF upload limit: 10MB
- Prior syntheses limit for brief context: 6 conversations

---

## 15. Existing Documentation

### Available in Repository

| Document | Location | Contents |
|----------|----------|----------|
| Project context & architecture | `CLAUDE.md` | Full FUSION OS cycle, stack, DB schema plan, AI endpoints plan, privacy rules, development phases |
| COR Unified Insights API | `AI.md` | Request/response format, tier rules, ChromaDB RAG, DB storage |
| AI coach rules | `AI-PROMPT.md` | System prompt rules for COR evaluation |
| Database schema (FUSION OS) | `database/sql/wp_fusion_core.sql` | All `wp_fusion_*` tables including evidence_log |
| Database schema (1-on-1) | `database/sql/wp_fusion_one_on_one_wizard.sql` | 1-on-1 bootstrap/patches |
| Database schema (evaluations) | `database/sql/wp_xfusion_result_evaluations.sql` | Evaluations table |
| Environment template | `.env.example` | All config keys with descriptions |
| LLM config | `config/xfusion-llm.php` | FastAPI URL, categories, timeout |
| Fusion API config | `config/fusion_api.php` | Bearer token config |
| Prompt files | `app/Http/Plugin/xfusion-plugin/prompts/*.md` | Default prompt templates |
| 1-on-1 brief/synthesis admin | `xfusion-one-on-one-briefs-admin.php`, `xfusion-one-on-one-synthesis-admin.php` | Version history viewers |
| ARP wizard (UI only) | `annual-readiness-plan/` | `[fusion_arp_wizard]` frontend shell |
| Synthesis normalizer | `app/Services/SynthesisCommitmentSummaryNormalizer.php` | Server-side `commitment_summary` formatting |

### Not Available in Repository

- Formal ERD diagram (visual)
- Architecture diagram (visual)
- OpenAPI/Swagger spec for Laravel or FastAPI endpoints
- `Xfusion-llm` (Python FastAPI) source code and its documentation
- Infrastructure/runbook documentation
- Performance benchmarks or load test results

### External Reference Documents (mentioned in `CLAUDE.md`)

Located at `C:\Users\LENOVO\Downloads\xfusion\` (may not be on all developer machines):
- Framework, Developer Specs, and Executive Guide for each component (1-on-1, QBR, 360, ARP, ARR) — 15 `.docx` files
- `Ringkasan FUSION Operating System.md`
- `Saran Database dan AI Architecture.md`

---

## Appendix: Memory Layer Gap Analysis

For the client's Memory Layer specification, here is what exists vs. what is planned:

| Memory Layer Capability | Current State | Gap |
|------------------------|---------------|-----|
| Event-sourced evidence log | Schema in SQL | No application writes |
| Evidence snapshots (frozen per review cycle) | Schema in SQL | Not implemented |
| On-the-fly evidence aggregation (1-on-1) | Working | Not persistent; not generalized to QBR/360/ARR |
| AI insight storage | Working (`wp_xfusion_result_evaluations`) | Only COR Unified; no per-component assessments yet |
| Vector memory (ChromaDB RAG) | Working for `COR Performance` + `fusion_one_on_one` | Other component categories not yet indexed |
| Privacy pipeline | Implemented for 1-on-1 | Needs extension to QBR/ARR |
| Cross-component evidence linking | Not implemented | Requires `wp_fusion_evidence_log` + snapshot tables |

---

## Feature Status Summary (July 2026)

The sections above are the full technical reference. This is a practical summary for the Memory Layer and FUSION OS:

### Working (production / sandbox)

| Area | Features |
|------|----------|
| **Organizations & users** | Company, company groups, employees, WordPress users |
| **Activities & assessments** | Gravity Forms submissions, LearnDash progress, course scoring groups (5 Behavioral Drivers + 5 COR capabilities) |
| **AI Insights (COR)** | COR Unified Insights (`evaluate-unified`), stored in `wp_xfusion_result_evaluations`, draft/published/sandbox status, prompt versioning, ChromaDB RAG category `COR Performance` |
| **Knowledge base (RAG)** | CPT `xfusion_knowledge`, sync to ChromaDB via FastAPI, categories `COR Performance` + `fusion_one_on_one` |
| **1-on-1 — database & API** | 7 `wp_fusion_one_on_one_*` tables, Laravel `/api/v1/one-on-one/*` (pairs, schedule, prep, notes, commitments, evidence, brief, synthesis) |
| **1-on-1 — wizard Step 0** | Meeting gate: schedule new (leader), **Your meetings** with filters (timing + role) and pagination (10 per page) |
| **1-on-1 — wizard Step 1** | Continuous Evidence (on-the-fly from evaluations, GF, scoring, prior meetings) |
| **1-on-1 — wizard Step 2** | AI Meeting Brief™ (generate, latest in wizard, history in wp-admin) |
| **1-on-1 — wizard Step 3–4** | Shared Preparation™ + Alignment Conversation™ (GF draft; TODO sync to DB) |
| **1-on-1 — wizard Step 5** | Shared Commitments™ (CRUD via Laravel API) |
| **1-on-1 — wizard Step 6** | AI Meeting Synthesis™ (generate, latest in wizard, history in wp-admin, `context-composer` fallback, commitment summary normalization) |
| **1-on-1 — privacy** | Prep `is_revealed`, brief uses prior syntheses only, synthesis uses current meeting prep/notes only |
| **Prompt management** | WP Admin LLM Prompts, `wp_options` registry, 4 slots (COR coach/user, 1-on-1 brief/synthesis) |
| **ARP (UI only)** | Shortcode `[fusion_arp_wizard]` — 7-step frontend shell, not yet persisting to `wp_fusion_arp_*` |

### Not working / partial / planned

| Area | Status | Notes |
|------|--------|-------|
| **`wp_fusion_evidence_log`** | Schema only | No application writes yet |
| **Evidence snapshots** (QBR/360/ARR) | Schema only | Not implemented |
| **QBR, 360 Review, ARR** | Schema only | No models, API, or wizard |
| **ARP — persistence & AI** | Partial | Wizard UI exists; DB + `readiness-review` API not yet |
| **Prep & notes → DB** | Partial | Still GF draft (`save-draft.php` TODO); synthesis can accept wizard overrides |
| **Other component AI endpoints** | Planned | QBR assessment/synthesis, 360, ARR, ARP readiness review |
| **Other ChromaDB categories** | Planned | `fusion_qbr`, `fusion_360`, `fusion_arp`, `fusion_arr` not yet indexed |
| **Centralized Memory Layer** | Planned | Cross-component linking requires evidence log + snapshots |
| **Background jobs for AI** | None | All AI generation is synchronous (user waits) |
| **Infrastructure** | Unknown | Backup, monitoring, CI/CD, SLA — not documented in repo |

### Related repositories

| Repo | Role |
|------|------|
| `xfusion` (Laravel + WP plugin) | DB, API, wizard UI, prompt registry |
| `xfusion-llm` (Python FastAPI) | COR evaluation, knowledge vectors, 1-on-1 brief/synthesis |

---

*End of document. Items marked with "Note — not confirmed/unknown" require input from the infrastructure, operations, or `Xfusion-llm` teams.*
