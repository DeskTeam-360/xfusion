# FUSION Operating System™ — Project Context

Dokumen ini berisi semua konteks yang dibutuhkan untuk melanjutkan pengembangan sistem FUSION.

---

## Apa Ini?

**FUSION Operating System™** adalah platform manajemen organisasi dan pengembangan individu berbasis filosofi **Readiness before Performance**. Dibangun di atas WordPress + Laravel + Python LLM service.

---

## Stack Teknologi

| Layer | Teknologi | Fungsi |
|-------|-----------|--------|
| Frontend/UX | WordPress + Custom Plugin (`xfusion-plugin`) | Pengumpulan data via Gravity Forms, display shortcodes |
| LMS | LearnDash | Course, topics, progress tracking |
| Backend API | Laravel (repo ini) | Admin panel, business logic, bridge ke WP database |
| AI Service | Python FastAPI (`Xfusion-llm`) | OpenAI API + ChromaDB RAG → generate insights |
| CRM | Keap | Contact sync, tag management |
| Database | MySQL (shared WP + Laravel, prefix `wp_`) | Satu database diakses dua aplikasi |

---

## Siklus FUSION Operating System

```
Mission → Vision
  ↓
Annual Readiness Plan™ (ARP)       [strategic anchor, tahunan]
  ↓
Quarterly Business Review™ (QBR)   [kesiapan organisasi, per kuartal]
  ↓
1-on-1 Alignment Capture™          [coaching individu, bulanan]
  ↓
Activities • Assessments • Tools • Reflections
  ↓
360 Review™                        [sintesis pengembangan individu, tahunan]
  ↓
Annual Readiness Review™ (ARR)     [pembelajaran organisasi, tahunan]
  ↓
Next Annual Readiness Plan™
```

Setiap komponen punya **pertanyaan utama**:
- **ARP**: *"Harus jadi organisasi seperti apa kita?"*
- **QBR**: *"Seberapa siap kita mengeksekusi strategi?"*
- **1-on-1**: *"Apa yang perlu terjadi selanjutnya untuk meningkatkan alignment?"*
- **360**: *"Seberapa jauh individu ini menjadi lebih siap berkontribusi?"*
- **ARR**: *"Apa yang harus kita lakukan berbeda tahun depan?"*

---

## 5 FUSION Behavioral Drivers™

Ini adalah dimensi pengembangan individu yang jadi tulang punggung seluruh sistem:

| Driver | Slug | Definisi |
|--------|------|----------|
| Get Real™ | `get_real` | Self-awareness, authenticity, ownership, reflection |
| Fill Buckets™ | `fill_buckets` | Energy, relationships, sustainability |
| Be Intentional™ | `be_intentional` | Planning, focus, prioritization |
| Foster Grit™ | `foster_grit` | Resilience, adaptability, perseverance |
| Drive Growth™ | `drive_growth` | Learning, development, improvement |

## 5 COR Organization Capabilities™

| Capability | Slug | Definisi |
|------------|------|----------|
| Alignment | `alignment` | Shared goals and direction |
| Accountability | `accountability` | Ownership and follow-through |
| Communication | `communication` | Collaboration and information flow |
| Leadership | `leadership` | Influence and development of others |
| Execution | `execution` | Translating intentions into results |

---

## Database — Yang Sudah Ada

```
wp_users                          ← WordPress users (semua user)
wp_usermeta                       ← User metadata (keap_contact_id, keap_tags, dll.)
wp_companies                      ← Organisasi/perusahaan
wp_company_employees              ← Relasi user ↔ company
wp_company_groups                 ← Grup dalam perusahaan
wp_company_group_details          ← Relasi user ↔ group
wp_course_scoring_groups          ← Kategori scoring (Get Real, Fill Buckets, dll.)
wp_course_scoring_group_details   ← Form ID + Field ID + Weight per kategori
wp_xfusion_result_evaluations     ← Hasil AI insight per user (UTAMA)
wp_gf_entry                       ← Gravity Forms entries (raw form submissions)
wp_gf_entry_meta                  ← Field values dari Gravity Forms
wp_posts                          ← WordPress posts (termasuk xfusion_knowledge CPT)
wp_postmeta                       ← Post metadata
```

### Tabel `wp_xfusion_result_evaluations` — Struktur Kunci

```sql
id, user_id, created_at, evaluated_at, company_information,
scoring_group_id,        -- 0 = COR Unified Insights
scoring_group_title,     -- 'COR Unified Insights'
score,                   -- 0-100 (rata-rata capabilities × 20)
evaluation_input,        -- JSON: raw request + generation_context + gauge_snapshots
evaluation,              -- JSON: {cor_organization_capabilities, performance{}, key_observation, recommended_focus_area}
status,                  -- 'draft' | 'published' | 'sandbox'
insight_model,           -- 'gpt-4o-mini', 'gpt-4o', dll.
prompt_version_id, prompt_version_label,
cost_usd, tokens_used, inserted_at
```

---

## Database — Yang Perlu Dibuat (FUSION Components)

Belum ada tabel untuk ARP, QBR, 1-on-1, 360, ARR. Perlu dibuat via Laravel Migration dengan prefix `wp_fusion_`:

### ARP
```
wp_fusion_arps                        ← Record utama (org + year + status)
wp_fusion_arp_future_states           ← Narasi future state
wp_fusion_arp_readiness_priorities    ← Kapabilitas yang dibutuhkan
wp_fusion_arp_strategic_priorities    ← Inisiatif utama + owner + KPI
wp_fusion_arp_learnings               ← Asumsi, risiko, learning objectives
wp_fusion_arp_ai_assessments          ← JSON output AI Readiness Review
```

### QBR
```
wp_fusion_qbrs                        ← Record utama (org + group + quarter + year)
wp_fusion_qbr_evidence_snapshots      ← Snapshot evidence saat QBR
wp_fusion_qbr_ai_assessments          ← JSON AI Organizational Assessment + leadership context
wp_fusion_qbr_commitments             ← Quarterly commitments (maks 5, carried forward jika belum selesai)
wp_fusion_qbr_ai_syntheses            ← JSON output akhir QBR
```

### 1-on-1
```
wp_fusion_one_on_ones                 ← Record per pasangan leader-employee
wp_fusion_one_on_one_conversations    ← Satu record per meeting
wp_fusion_one_on_one_ai_briefs        ← JSON AI Meeting Brief (pre-meeting)
wp_fusion_one_on_one_preparations     ← Persiapan employee + leader (terpisah, revealed saat meeting)
wp_fusion_one_on_one_notes            ← Conversation notes per section
wp_fusion_one_on_one_commitments      ← Shared commitments (employee + leader)
wp_fusion_one_on_one_ai_syntheses     ← JSON AI Meeting Synthesis (post-meeting)
```

### 360 Review
```
wp_fusion_360_reviews                 ← Record utama (employee + year)
wp_fusion_360_evidence_snapshots      ← Snapshot evidence setahun
wp_fusion_360_ai_assessments          ← JSON AI Development Assessment
wp_fusion_360_reflections             ← Employee + leader reflection + agreement rating
wp_fusion_360_commitments             ← Annual development commitments (maks 5)
wp_fusion_360_ai_syntheses            ← JSON Annual Development Synthesis
```

### ARR
```
wp_fusion_arrs                        ← Record utama (org + year)
wp_fusion_arr_evidence_snapshots      ← Annual evidence dari semua komponen
wp_fusion_arr_ai_assessments          ← JSON AI Annual Readiness Assessment + executive context
wp_fusion_arr_renewal_recommendations ← Strategic renewal recommendations untuk ARP berikutnya
wp_fusion_arr_ai_syntheses            ← JSON AI Strategic Renewal Synthesis
```

### Cross-cutting
```
wp_fusion_evidence_log               ← PENTING: Log semua evidence events
                                        (source_type, source_id, event_type, user_id, behavioral_driver, cor_capability, evidence_date)
```

---

## AI System — Yang Sudah Ada

### Alur Saat Ini
```
WordPress Gravity Forms
  → xfusion-plugin kumpulkan Q&A per behavioral driver (weight: primary=1.0, secondary=0.5, tertiary=0.25)
  → POST /api/v1/evaluation/evaluate-unified (Laravel)
  → Laravel forward ke Xfusion-llm (Python FastAPI)
  → Xfusion-llm: ChromaDB RAG (category: "COR Performance") + OpenAI → JSON evaluation
  → Simpan ke wp_xfusion_result_evaluations
  → WordPress shortcode tampilkan hasil
```

### Endpoint AI Yang Ada
- `POST /api/v1/evaluation/evaluate-unified` — COR Unified Insights (sudah berjalan)
- `POST /api/v1/knowledge/upsert` — Sync knowledge ke ChromaDB
- `POST /api/v1/knowledge/delete/{id}` — Hapus knowledge dari ChromaDB

### AI Output Structure (evaluate-unified)
```json
{
  "cor_organization_capabilities": "COR Insight (75-100 words)",
  "performance": {
    "get_real":        {"strength": "...", "opportunity": "..."},
    "fill_buckets":    {"strength": "...", "opportunity": "..."},
    "be_intentional":  {"strength": "...", "opportunity": "..."},
    "foster_grit":     {"strength": "...", "opportunity": "..."},
    "drive_growth":    {"strength": "...", "opportunity": "..."}
  },
  "key_observation": "Overall Insight (100-150 words)",
  "recommended_focus_area": "25-50 words"
}
```

### ChromaDB Knowledge Base — Kategori Yang Ada
- `COR Performance` — sudah ada, digunakan oleh evaluate-unified

### ChromaDB Knowledge Base — Yang Perlu Ditambah
- `fusion_one_on_one` — dokumen 1-1 Framework + Executive Guide + Developer Specs
- `fusion_qbr` — dokumen QBR Framework + Executive Guide + Developer Specs
- `fusion_360` — dokumen 360 Framework + Executive Guide + Developer Specs
- `fusion_arp` — dokumen ARP Framework + Executive Guide + Developer Specs
- `fusion_arr` — dokumen ARR Framework + Executive Guide + Developer Specs

> Dokumen sumber ada di folder `xfusion` (Downloads): 15 file .docx. Upload via WordPress → XFusion Knowledge CPT → assign category → auto-sync ke ChromaDB.

### Endpoint AI Yang Perlu Dibuat di Xfusion-llm
```
POST /api/v1/one-on-one/meeting-brief       ← AI Meeting Brief sebelum 1-on-1
POST /api/v1/one-on-one/meeting-synthesis   ← AI synthesis setelah 1-on-1
POST /api/v1/qbr/organizational-assessment  ← AI assessment awal QBR
POST /api/v1/qbr/organizational-synthesis   ← AI synthesis akhir QBR
POST /api/v1/360/development-assessment     ← AI assessment awal 360
POST /api/v1/360/development-synthesis      ← AI synthesis akhir 360
POST /api/v1/arr/annual-assessment          ← AI assessment awal ARR
POST /api/v1/arr/strategic-renewal-synthesis ← AI synthesis akhir ARR
```

### Model Rekomendasi
- Proses sering (1-on-1): `gpt-4o-mini` atau `gpt-4.1-mini`
- Proses tahunan (360, ARR): `gpt-4o` atau `gpt-4.1`

---

## Privacy Principle — WAJIB DIPATUHI

```
Private Reflection → AI Pattern Extraction → Shared Insight
```

**Aturan konkret di kode:**
- Raw Gravity Forms field values → **TIDAK PERNAH** masuk ke prompt AI untuk konteks QBR/ARR
- Yang boleh masuk ke QBR/ARR prompt: hanya field `evaluation` dari `wp_xfusion_result_evaluations` (sudah diproses AI)
- Yang boleh masuk ke 1-on-1 Meeting Brief: hanya AI synthesis dari conversation sebelumnya, bukan preparation text mentah
- Preparation employee/leader untuk 1-on-1: kolom `is_revealed = false` sampai meeting dimulai, kedua pihak tidak bisa lihat persiapan satu sama lain sebelumnya

---

## WordPress Plugin — File Utama

```
app/Http/Plugin/xfusion-plugin/
├── xfusion_plugin.php                          ← Entry point
├── includes/
│   ├── cor-unified-insights.php               ← Kumpul Q&A + call evaluate-unified
│   ├── result-evaluation.php                  ← CRUD wp_xfusion_result_evaluations
│   ├── gravity-forms-bridge.php               ← AJAX bridge ke Gravity Forms
│   ├── xfusion-knowledge-cpt.php              ← Knowledge CPT + sync ke ChromaDB
│   ├── xfusion-ai-insights-settings.php       ← Settings: model, prompt versions
│   ├── send-evaluation-shortcode.php          ← [send_evaluation] shortcode
│   ├── insight-date-filter.php                ← Date filter untuk insights
│   ├── course-scoring-group-gauge-shortcode.php
│   ├── grit-chart-individual.php
│   ├── grit-chart-team.php
│   └── ...
└── prompts/
    └── unified_user_prompt.md                 ← User prompt template untuk evaluate-unified
```

---

## Laravel — Struktur Penting

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   ├── CompanyController.php
│   │   │   ├── CompanyGroupController.php
│   │   │   ├── CourseScoringGroupController.php
│   │   │   └── XfusionKnowledgeController.php
│   │   └── Api/
│   │       ├── ParticipationChartsController.php
│   │       └── CompanyPublicController.php
│   └── Middleware/
│       ├── VerifyFusionApiToken.php            ← Bearer token untuk API v1
│       └── CheckRole.php
├── Models/
│   ├── Company.php, CompanyEmployee.php, CompanyGroup.php
│   ├── CourseScoringGroup.php, CourseScoringGroupDetail.php
│   ├── WpGfEntry.php, WpGfEntryMeta.php       ← Gravity Forms entries
│   ├── WpUser.php, WpUserMeta.php
│   └── XfusionKnowledge.php
└── Services/
    └── ParticipationChartsService.php
routes/
├── api.php                                     ← API routes
└── web.php                                     ← Web routes
```

---

## Laravel API Routes Yang Ada

```
POST  /keap-gform/                              ← Keap tag dari Gravity Form
POST  /register/                               ← Register user ke Keap
POST  /next-course/                            ← Mark topic complete + Keap tag
POST  /save-pdf-result/                        ← Save PDF + Keap custom field

GET   /api/v1/companies                        ← Public company list (protected: fusion.api)
GET   /api/v1/companies/{id}                   ← Company detail
GET   /api/v1/companies/{id}/participation-charts
GET   /api/v1/course-groups                    ← Course group list
```

---

## Prioritas Pengembangan Selanjutnya

### Fase 1 — Fondasi Database (lakukan dulu)
1. Buat migration untuk `wp_fusion_evidence_log`
2. Buat migration untuk semua tabel `wp_fusion_*` per komponen

### Fase 2 — 1-on-1 Alignment Capture (komponen pertama)
1. Laravel: Model + Controller + API routes untuk 1-on-1
2. Xfusion-llm: endpoint `meeting-brief` dan `meeting-synthesis`
3. WordPress plugin: shortcode `[fusion_one_on_one]`
4. ChromaDB: upload dokumen 1-1 ke kategori `fusion_one_on_one`

### Fase 3 — QBR
1. Laravel: Model + Controller + API routes
2. Evidence Aggregation Service (kumpul evidence dari wp_xfusion_result_evaluations)
3. Xfusion-llm: endpoint `organizational-assessment` dan `organizational-synthesis`
4. WordPress plugin: shortcode `[fusion_qbr]`

### Fase 4 — 360 Review
1. Butuh setahun data 1-on-1 sebagai evidence
2. Laravel: Model + Controller + API routes
3. Xfusion-llm: endpoint `development-assessment` dan `development-synthesis`

### Fase 5 — ARP
1. Laravel: Model + Controller + API routes (executive-only)
2. Xfusion-llm: endpoint `arp-readiness-review`

### Fase 6 — ARR
1. Butuh semua komponen di atas
2. Laravel: Model + Controller + API routes
3. Xfusion-llm: endpoint `annual-assessment` dan `strategic-renewal-synthesis`

### Fase 7 — Dashboards
1. Individual Dashboard™ (shortcode WordPress)
2. Leader Dashboard™
3. Executive Dashboard™

---

## Dokumen Referensi

File-file berikut ada di folder `C:\Users\LENOVO\Downloads\xfusion\`:
- `1-1 Framework.docx`, `1-1 Developer Specs.docx`, `1-1 Executive Guide.docx`
- `QBR Framework.docx`, `QBR Developer Specs.docx`, `QBR Executive Guide.docx`
- `360 Framework.docx`, `360 Developer Specs.docx`, `360 Executive Guide.docx`
- `ARP Framework.docx`, `ARP Developer Specs.docx`, `ARP Executive Guide.docx`
- `ARR Framework.docx`, `ARR Developer Specs.docx`, `ARR Executive Guide.docx`
- `Ringkasan FUSION Operating System.md` — ringkasan lengkap dalam Bahasa Indonesia
- `Saran Database dan AI Architecture.md` — saran lengkap database + AI architecture

---

## Catatan Penting

1. **Database shared**: Laravel dan WordPress mengakses database MySQL yang sama. Tabel dengan prefix `wp_` bisa diakses dari keduanya.
2. **Auth**: WordPress user (`wp_users`) digunakan sebagai single source of truth untuk user. Laravel pakai `CustomUserProvider` untuk auth.
3. **Knowledge sync**: Setiap `xfusion_knowledge` post di WordPress auto-sync ke ChromaDB via `POST /api/v1/knowledge/upsert`. Ini adalah cara untuk feed dokumen FUSION ke AI.
4. **Gravity Forms weight system**: `weight=1.0` → primary, `0.5` → secondary, `0.25` → tertiary. Ini menentukan bobot Q&A dalam AI prompt.
5. **AI tidak pernah menghitung skor**: Skor dihitung oleh sistem sebelum dipanggil AI. AI hanya interpretasi dan coaching insight.
