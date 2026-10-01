-- Checks whether QBR id=6's own evidence snapshot was captured BEFORE
-- QBR id=7 was published/snapshotted. Step 1 (getEvidence) just returns
-- the latest already-saved snapshot for the CURRENT QBR — it does not
-- recompute automatically. If QBR 6's snapshot is older than QBR 7's,
-- it was generated back when there was no "previous quarter" data yet,
-- and it will keep showing stale "no historical data" until someone
-- re-generates it (Step 1 "Regenerate evidence" action).

SELECT id, qbr_id, captured_at
FROM wp_fusion_qbr_evidence_snapshots
WHERE qbr_id = 6
ORDER BY id DESC;

-- For reference — QBR 7's snapshots (already confirmed: latest 2026-09-30 14:20:59).
SELECT id, qbr_id, captured_at
FROM wp_fusion_qbr_evidence_snapshots
WHERE qbr_id = 7
ORDER BY id DESC;
