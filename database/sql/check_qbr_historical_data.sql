-- Diagnostic: "Historical QBR Data" / "Previous Quarterly Commitments" not
-- showing even though the prior quarter's QBR is published.
-- Mirrors Qbr::previousQuarter() exactly — it matches on
-- (company_group_id, quarter, year), nothing else. Replace QBR_ID below
-- with the CURRENT QBR you're testing (the one missing historical data).

-- 1. The current QBR: its group/quarter/year, and what previousQuarter()
--    will search for (prev_quarter/prev_year).
SELECT
    id AS qbr_id,
    company_group_id,
    quarter,
    year,
    status,
    CASE WHEN quarter > 1 THEN quarter - 1 ELSE 4 END AS prev_quarter,
    CASE WHEN quarter > 1 THEN year ELSE year - 1 END AS prev_year
FROM wp_fusion_qbrs
WHERE id = QBR_ID;

-- 2. Does a QBR exist that matches that exact (company_group_id,
--    prev_quarter, prev_year)? If this returns 0 rows, previousQuarter()
--    returns null and ALL historical fields show as unavailable —
--    regardless of whether some other QBR for this company is published.
--    Run this after filling in the three values from query #1.
SELECT id, company_group_id, quarter, year, status
FROM wp_fusion_qbrs
WHERE company_group_id = COMPANY_GROUP_ID
  AND quarter = PREV_QUARTER
  AND year = PREV_YEAR;

-- 3. All QBRs for this group, regardless of quarter/year — use this to
--    spot a mismatch (e.g. the "previous" QBR the user published is
--    actually tagged with a different group_id, or an unexpected
--    quarter/year).
SELECT id, company_group_id, quarter, year, status, created_at
FROM wp_fusion_qbrs
WHERE company_group_id = COMPANY_GROUP_ID
ORDER BY year, quarter;

-- 4. If #2 DOES return a row, confirm it actually has an evidence
--    snapshot and commitments saved (both are required for the
--    "Historical QBR Data" / "Previous Quarterly Commitments" cards to
--    show real numbers instead of "no data").
SELECT id, qbr_id, captured_at
FROM wp_fusion_qbr_evidence_snapshots
WHERE qbr_id = (SELECT id FROM wp_fusion_qbrs WHERE company_group_id = COMPANY_GROUP_ID AND quarter = PREV_QUARTER AND year = PREV_YEAR)
ORDER BY id DESC;

SELECT id, qbr_id, status
FROM wp_fusion_qbr_commitments
WHERE qbr_id = (SELECT id FROM wp_fusion_qbrs WHERE company_group_id = COMPANY_GROUP_ID AND quarter = PREV_QUARTER AND year = PREV_YEAR);
