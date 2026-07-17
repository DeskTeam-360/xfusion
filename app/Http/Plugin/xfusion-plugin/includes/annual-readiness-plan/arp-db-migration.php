<?php
/**
 * ARP database tables — WordPress dbDelta migration (versioned).
 *
 * Option: xfusion_arp_db_version
 * Manual SQL (phpMyAdmin): database/sql/wp_fusion_arp_wizard.sql
 *
 * Bump XFUSION_ARP_DB_VERSION when schema changes; dbDelta adds missing columns/indexes.
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

const XFUSION_ARP_DB_VERSION = '1.0';

/**
 * @return array<string, string>
 */
function xfusion_arp_table_names(): array
{
    global $wpdb;
    $p = $wpdb->prefix;

    return [
        'arps' => $p . 'fusion_arps',
        'future_states' => $p . 'fusion_arp_future_states',
        'readiness_priorities' => $p . 'fusion_arp_readiness_priorities',
        'strategic_priorities' => $p . 'fusion_arp_strategic_priorities',
        'learnings' => $p . 'fusion_arp_learnings',
        'ai_assessments' => $p . 'fusion_arp_ai_assessments',
    ];
}

function xfusion_arp_maybe_migrate_tables(): void
{
    if (get_option('xfusion_arp_db_version') === XFUSION_ARP_DB_VERSION) {
        return;
    }

    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $t = xfusion_arp_table_names();
    $charset = $wpdb->get_charset_collate();

    dbDelta("CREATE TABLE {$t['arps']} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        company_id bigint(20) unsigned NOT NULL,
        year smallint(5) unsigned NOT NULL,
        title varchar(255) NOT NULL DEFAULT '',
        mission text NULL,
        vision text NULL,
        core_values text NULL,
        organizational_description text NULL,
        business_environment text NULL,
        executive_narrative text NULL,
        status varchar(20) NOT NULL DEFAULT 'draft',
        created_by bigint(20) unsigned NULL,
        created_at datetime NULL,
        updated_at datetime NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY arp_company_year_uq (company_id, year),
        KEY arp_status_idx (status)
    ) {$charset};");

    dbDelta("CREATE TABLE {$t['future_states']} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        arp_id bigint(20) unsigned NOT NULL,
        narrative text NOT NULL,
        future_characteristics text NULL,
        desired_culture text NULL,
        desired_customer_experience text NULL,
        desired_employee_experience text NULL,
        desired_leadership_environment text NULL,
        created_at datetime NULL,
        updated_at datetime NULL,
        PRIMARY KEY  (id),
        KEY arpfs_arp_idx (arp_id)
    ) {$charset};");

    dbDelta("CREATE TABLE {$t['readiness_priorities']} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        arp_id bigint(20) unsigned NOT NULL,
        name varchar(255) NOT NULL DEFAULT '',
        cor_capability varchar(40) NOT NULL DEFAULT 'leadership',
        primary_driver varchar(40) NOT NULL DEFAULT 'be_intentional',
        secondary_driver varchar(40) NULL,
        priority_level varchar(20) NOT NULL DEFAULT 'medium',
        description text NULL,
        business_rationale text NULL,
        executive_owner_user_id bigint(20) unsigned NULL,
        expected_impact text NULL,
        priority_rank smallint(5) unsigned NOT NULL DEFAULT 0,
        created_at datetime NULL,
        updated_at datetime NULL,
        PRIMARY KEY  (id),
        KEY arprp_arp_idx (arp_id),
        KEY arprp_rank_idx (arp_id, priority_rank)
    ) {$charset};");

    dbDelta("CREATE TABLE {$t['strategic_priorities']} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        arp_id bigint(20) unsigned NOT NULL,
        readiness_priority_id bigint(20) unsigned NULL,
        title varchar(255) NOT NULL DEFAULT '',
        description text NULL,
        owner_user_id bigint(20) unsigned NULL,
        target_date date NULL,
        success_measures text NULL,
        org_kpi varchar(80) NULL,
        readiness_indicator varchar(80) NULL,
        related_groups varchar(80) NULL,
        kpi text NULL,
        status varchar(20) NOT NULL DEFAULT 'not_started',
        priority_rank smallint(5) unsigned NOT NULL DEFAULT 0,
        created_at datetime NULL,
        updated_at datetime NULL,
        PRIMARY KEY  (id),
        KEY arpsp_arp_idx (arp_id),
        KEY arpsp_owner_idx (owner_user_id),
        KEY arpsp_readiness_idx (readiness_priority_id),
        KEY arpsp_rank_idx (arp_id, priority_rank)
    ) {$charset};");

    dbDelta("CREATE TABLE {$t['learnings']} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        arp_id bigint(20) unsigned NOT NULL,
        type varchar(30) NOT NULL DEFAULT 'assumption',
        description text NOT NULL,
        created_at datetime NULL,
        updated_at datetime NULL,
        PRIMARY KEY  (id),
        KEY arpl_arp_idx (arp_id),
        KEY arpl_type_idx (type)
    ) {$charset};");

    dbDelta("CREATE TABLE {$t['ai_assessments']} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        arp_id bigint(20) unsigned NOT NULL,
        assessment longtext NOT NULL,
        leadership_context text NULL,
        insight_model varchar(60) NULL,
        tokens_used int(10) unsigned NOT NULL DEFAULT 0,
        cost_usd decimal(10,4) NOT NULL DEFAULT 0,
        created_at datetime NULL,
        updated_at datetime NULL,
        PRIMARY KEY  (id),
        KEY arpaa_arp_idx (arp_id)
    ) {$charset};");

    update_option('xfusion_arp_db_version', XFUSION_ARP_DB_VERSION);
}

add_action('plugins_loaded', 'xfusion_arp_maybe_migrate_tables', 5);
