-- Fix: SQLSTATE[22007] "Incorrect string value: '\xE2\x80\x8B'" when saving
-- a Course List (page_title). \xE2\x80\x8B is a zero-width space (U+200B),
-- usually pasted in from Google Docs/Word — it can't be stored if the
-- column's charset isn't a Unicode one (utf8/utf8mb4).
--
-- Step 1: check the current charset/collation on this table + column.
-- Uses SHOW instead of information_schema — shared-hosting DB users
-- (e.g. cPanel) are often denied direct access to information_schema.
SHOW TABLE STATUS WHERE Name = 'wp_course_lists';

SHOW FULL COLUMNS FROM wp_course_lists;

-- Step 2: convert the whole table to utf8mb4 so any pasted Unicode
-- character (not just this one) is accepted going forward. Existing rows
-- are converted in place — safe to run even if some columns are already
-- utf8mb4.
ALTER TABLE wp_course_lists
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
