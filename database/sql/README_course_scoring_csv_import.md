# Import Course Scoring dari CSV COR

## 1. Kolom `weight` (sekali)

Jalankan di MySQL:

`database/sql/wp_course_scoring_group_details_add_weight.sql`

## 2. Upload CSV ke server

Letakkan file di:

`public/FUSION_COR_Primary_Scale_Mapping.xlsx - Scaled Mapping.csv`

atau path lain lalu pakai `--path=...`

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
