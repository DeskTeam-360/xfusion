-- -----------------------------------------------------------------------------
-- wp_fusion_one_on_one_conversations: mandatory step-by-step completion +
-- resume-at-last-step. `last_step` is the furthest STEPS[].key this
-- conversation's wizard has unlocked (e.g. 'commitments') — reopening the
-- wizard resumes there instead of always starting at Step 1.
-- -----------------------------------------------------------------------------
ALTER TABLE `wp_fusion_one_on_one_conversations`
    ADD COLUMN IF NOT EXISTS `last_step` VARCHAR(20) NULL
        COMMENT 'evidence | brief | preparation | conversation | commitments | synthesis'
        AFTER `status`;
