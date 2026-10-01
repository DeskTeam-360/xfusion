-- Confirms whether QBR id=7 (Q3 2026, group 14, status=closed) has an
-- evidence snapshot and commitments saved. QBR id=6 (Q4 2026, same group)
-- looks for exactly this record as its "previous quarter" — if either of
-- these come back empty, that's why the "Historical QBR Data" /
-- "Previous Quarterly Commitments" cards on QBR id=6 show no data.

SELECT id, qbr_id, captured_at
FROM wp_fusion_qbr_evidence_snapshots
WHERE qbr_id = 7
ORDER BY id DESC;

SELECT id, qbr_id, status, title
FROM wp_fusion_qbr_commitments
WHERE qbr_id = 7;

-- Sanity check: confirm QBR id=6 really does resolve to qbr_id=7 as its
-- previous quarter (company_group_id/quarter/year must match exactly).
SELECT id, company_group_id, quarter, year, status
FROM wp_fusion_qbrs
WHERE id IN (6, 7);
