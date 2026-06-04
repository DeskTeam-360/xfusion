# Import Course Scoring dari CSV COR

## 1. SQL sekali di MySQL

1. `database/sql/wp_course_scoring_group_details_add_weight.sql`
2. `database/sql/wp_course_scoring_group_details_nullable_field_id.sql` — `field_id` boleh NULL jika pertanyaan kosong / tidak ketemu di GF

## 2. Upload CSV ke server

Letakkan file di:

`public/FUSION_COR_Primary_Scale_Mapping.xlsx - Scaled Mapping.csv`

atau path lain lalu pakai `--path=...`

## Debug: field tidak ketemu

Import mencocokkan kolom **Question** ke label field Gravity Forms (semua tipe input: text, textarea, radio, dll.). Admin “Course Scoring Group” hanya menampilkan **radio** dan **number** — field bisa ada di GF tapi tidak muncul di UI admin.

```bash
php artisan course-scoring:import-csv --list-fields=35
```

Bandingkan kolom `label` dengan teks di CSV. Import memperbaiki mojibake kutip (`â€œ` → `"`), smart quotes, dan titik ganda di akhir (`..` → `.`).

## 3. Cek tanpa menulis DB

```bash
php artisan course-scoring:import-csv --dry-run
```

## 4. Replace semua grup + detail

```bash
php artisan course-scoring:import-csv --force
```

Opsi:

- `--path=/full/path/file.csv`
- `--dry-run` — hanya laporan
- `--force` — tanpa konfirmasi (production)
- `--skip-replace` — tidak hapus data lama (hanya tambah/update grup by title; detail bisa bentrok unique key)

## Mapping CSV

| Kolom | Pemakaian |
|-------|-----------|
| 1 Name | Diabaikan |
| 2 Course title | Hapus prefix `123 - ` → cocokkan `wp_gf_form.title` → `form_id` |
| 3 Question | Label field GF (radio/number) → `field_id` |
| 4 Answer | Diabaikan |
| 5–14 | Bobot per `CourseScoringGroup` (judul grup = header kolom) |
| 15–16 | Summary, diabaikan |

Grup yang dibuat (10): Get Real, Fill Buckets, Be Intentional, Foster Grit, Drive Growth, Alignment, Accountability, Communication, Leadership, Execution.
