# Unified COR Insights API

Satu request mengirim semua data COR + performance. Satu response disimpan sebagai **satu baris DB** (`scoring_group_id = 0`).

Alur lama **tetap ada**:
- `[send_evaluation category="Get Real"]` → `/api/v1/evaluation/evaluate` → 1 baris per kategori
- Endpoint & handler per-grup tidak dihapus

Alur **baru** (Generate Insights di `[xfusion_core_readiness]`):
- WordPress → `/api/v1/evaluation/evaluate-unified` → 1 baris `COR Unified Insights`

---

## Request `POST /api/v1/evaluation/evaluate-unified`

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
      "primary": [
        { "question": "Rate your ability to Get Real 1-5", "answer": "4" }
      ],
      "secondary": [
        { "question": "What actions did you take...", "answer": "..." }
      ],
      "tertiary": []
    },
    "fill_buckets": {
      "primary": [],
      "secondary": [],
      "tertiary": []
    },
    "be_intentional": { "primary": [], "secondary": [], "tertiary": [] },
    "foster_grit": { "primary": [], "secondary": [], "tertiary": [] },
    "drive_growth": { "primary": [], "secondary": [], "tertiary": [] }
  }
}
```

**Tier rules** (dari `weight` di `wp_course_scoring_group_details`, sesuai CSV):
| Weight | Tier |
|--------|------|
| `1` / `1.00` | primary |
| `0.5` / `0.50` | secondary |
| `0.25` | tertiary |
| `0` | skip (tidak dikirim) |

**Category keys:** slug dari judul grup (`Get Real` → `get_real`).

---

## Response

```json
{
  "user_id": 42,
  "created_at": "2026-06-03T10:30:00Z",
  "evaluated_at": "2026-06-03T10:31:15Z",
  "company_information": 0,
  "evaluation": {
    "cor_organization_capabilities": "COR Insight (75-100 words)",
    "performance": {
      "get_real": {
        "strength": "Greatest Strength (50-75 words)",
        "opportunity": "Greatest Opportunity (50-75 words)"
      },
      "fill_buckets": {
        "strength": "...",
        "opportunity": "..."
      }
    },
    "key_observation": "Overall Insight (100-150 words)",
    "recommended_focus_area": "Recommended Focus Area (25-50 words)"
  },
  "token_usage": {
    "prompt_tokens": 0,
    "completion_tokens": 0,
    "total_tokens": 0
  }
}
```

---

## Pengaturan AI (WordPress → Settings → XFusion LLM)

- **Insight model** — pilih model OpenAI (`gpt-4o-mini`, `gpt-4o`, dll.)
- **Default insight status** — `draft` (user bisa generate lagi) atau `published`
- **Prompt versions** — system prompt (`coach_prompt`) + **user instruction template** (`user_prompt_template`); pilih versi aktif
- Placeholder user template: `{cor_perf_context}`, `{caps}`, `{performance}`, `{category_hint}` (JSON literal pakai `{{` `}}`)

Setiap generate menyimpan `generation_context` di `evaluation_input`: model, prompt version, daftar knowledge COR Performance (post ID + chunk count).

---

Saat **Generate Insights** (`POST /evaluate-unified`), LLM memuat **semua** chunk Chroma dengan metadata `category = "COR Performance"` dan menyertakannya di prompt sebagai konteks coaching.

- Aturan interpretasi & tone: `AI-PROMPT.md` (disalin ke `Xfusion-llm/prompts/ai_prompt.md`)
- Tambah/edit knowledge di **Xfusion Knowledge** dengan kategori **COR Performance** — otomatis di-sync ke Chroma via `POST /api/v1/knowledge/upsert`
- Kategori tersedia di WordPress CPT (`xfusion_knowledge`) dan Laravel admin (`config/xfusion-llm.php`)

---

## Penyimpanan DB

Tabel: `wp_xfusion_result_evaluations`

| Kolom | Nilai unified |
|-------|----------------|
| `scoring_group_id` | `0` |
| `scoring_group_title` | `COR Unified Insights` |
| `status` | `draft` or `published` — only **published** rows appear on dashboard |
| `evaluation_input` | Full request JSON + `generation_context` (model, prompt version, knowledge used) + `gauge_snapshots` |
| `evaluation` | Full `evaluation` object dari response |
| `score` | Rata-rata capabilities × 20 (skala 0–100) |

---

## File terkait

| Layer | File |
|-------|------|
| LLM API | `Xfusion-llm/routers/evaluation.py` → `POST /evaluate-unified` |
| Coaching rules | `AI-PROMPT.md` → `Xfusion-llm/prompts/ai_prompt.md` |
| COR Performance KB | `Xfusion-llm/database.py` → `get_chunks_by_category("COR Performance")` |
| Knowledge CPT | `xfusion-plugin/includes/xfusion-knowledge-cpt.php` |
| WordPress collect + call | `xfusion-plugin/includes/cor-unified-insights.php` |
| DB insert | `xfusion-plugin/includes/result-evaluation.php` → `xfusion_result_evaluation_insert_unified()` |
| Batch button | `xfusion-plugin/includes/send-evaluation-shortcode.php` → `xfusion_cor_readiness_batch_ajax_handler` |
| Date filter | `xfusion-plugin/includes/insight-date-filter.php` → `xfusion_insight_date_filter_resolve_unified()` |
| Shortcodes | `[xfusion_cor_organization_capabilities]` (COR Insight) · `[xfusion_cor_key_observation]` (Overall Insight) · `[send_evaluation]` (Greatest Strength / Greatest Opportunity) |
