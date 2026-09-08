-- Clear all test data for 1-on-1, ARP, QBR, ARR, and 360/IRR.
--
-- DESTRUCTIVE — this deletes every row in every FUSION component table
-- listed below. Only run this against a sandbox/testing database.
-- Company/Group/User/Course tables are untouched.
--
-- Usage: mysql -u <user> -p <database> < clear_fusion_test_data.sql

SET FOREIGN_KEY_CHECKS = 0;

-- 1-on-1 Alignment Capture™
TRUNCATE TABLE wp_fusion_one_on_one_ai_briefs;
TRUNCATE TABLE wp_fusion_one_on_one_ai_syntheses;
TRUNCATE TABLE wp_fusion_one_on_one_commitments;
TRUNCATE TABLE wp_fusion_one_on_one_notes;
TRUNCATE TABLE wp_fusion_one_on_one_preparations;
TRUNCATE TABLE wp_fusion_one_on_one_conversations;
TRUNCATE TABLE wp_fusion_one_on_ones;

-- Annual Readiness Plan™ (ARP)
TRUNCATE TABLE wp_fusion_arp_ai_assessments;
TRUNCATE TABLE wp_fusion_arp_future_states;
TRUNCATE TABLE wp_fusion_arp_learnings;
TRUNCATE TABLE wp_fusion_arp_readiness_priorities;
TRUNCATE TABLE wp_fusion_arp_strategic_priorities;
TRUNCATE TABLE wp_fusion_arp_versions;
TRUNCATE TABLE wp_fusion_arps;

-- Quarterly Business Review™ (QBR)
TRUNCATE TABLE wp_fusion_qbr_ai_assessments;
TRUNCATE TABLE wp_fusion_qbr_ai_syntheses;
TRUNCATE TABLE wp_fusion_qbr_commitments;
TRUNCATE TABLE wp_fusion_qbr_decisions;
TRUNCATE TABLE wp_fusion_qbr_evidence_snapshots;
TRUNCATE TABLE wp_fusion_qbr_kpis;
TRUNCATE TABLE wp_fusion_qbrs;

-- Annual Readiness Review™ (ARR)
TRUNCATE TABLE wp_fusion_arr_ai_assessments;
TRUNCATE TABLE wp_fusion_arr_ai_syntheses;
TRUNCATE TABLE wp_fusion_arr_evidence_snapshots;
TRUNCATE TABLE wp_fusion_arr_executive_reflections;
TRUNCATE TABLE wp_fusion_arr_renewal_recommendations;
TRUNCATE TABLE wp_fusion_arrs;

-- Individual Readiness Review™ / 360 Review
TRUNCATE TABLE wp_fusion_360_ai_assessments;
TRUNCATE TABLE wp_fusion_360_ai_syntheses;
TRUNCATE TABLE wp_fusion_360_commitments;
TRUNCATE TABLE wp_fusion_360_conversation_agreements;
TRUNCATE TABLE wp_fusion_360_evidence_snapshots;
TRUNCATE TABLE wp_fusion_360_reviews;

-- Shared cross-cutting evidence log (events from ALL of the above).
-- Comment this out if you want to keep the audit trail.
TRUNCATE TABLE wp_fusion_evidence_log;

SET FOREIGN_KEY_CHECKS = 1;
