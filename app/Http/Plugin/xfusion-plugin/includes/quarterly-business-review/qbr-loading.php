<?php
/**
 * Shared loading UI helpers for the QBR wizard.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfqbr_wizard_loading_js(): string
{
    return <<<'JS'
window.xqbrSpinnerHtml = function (message) {
    var text = message || 'Loading…';
    return '<div class="xqbr-spinner-row" role="status" aria-live="polite">' +
        '<span class="xqbr-spinner" aria-hidden="true"></span>' +
        '<span>' + text + '</span>' +
        '</div>';
};

window.xqbrSkeletonLine = function (widthClass) {
    return '<div class="xqbr-skeleton-line ' + (widthClass || 'w-full') + '"></div>';
};

window.xqbrSkeletonPrioCard = function (index) {
    return '<div class="xqbr-prio-card xqbr-skeleton-card" aria-hidden="true">' +
        '<div class="xqbr-prio-rail"><span class="xqbr-skeleton-circle">' + (index + 1) + '</span></div>' +
        '<div class="xqbr-prio-body">' +
        '<div class="xqbr-prio-grid xqbr-prio-grid-4">' +
        window.xqbrSkeletonLine('w-90') +
        window.xqbrSkeletonLine('w-60') +
        window.xqbrSkeletonLine('w-40') +
        window.xqbrSkeletonLine('w-50') +
        '</div>' +
        '<div class="xqbr-prio-grid xqbr-prio-grid-4">' +
        window.xqbrSkeletonLine('w-70') +
        window.xqbrSkeletonLine('w-80') +
        window.xqbrSkeletonLine('w-35') +
        window.xqbrSkeletonLine('w-20') +
        '</div>' +
        '<div class="xqbr-prio-grid xqbr-prio-grid-1">' +
        window.xqbrSkeletonLine('w-full') +
        window.xqbrSkeletonLine('w-95') +
        '</div>' +
        '</div></div>';
};

window.xqbrRenderListLoading = function (listEl, cardCount, message) {
    if (!listEl) {
        return;
    }
    var count = Math.max(1, Math.min(5, cardCount || 3));
    var cards = '';
    for (var i = 0; i < count; i += 1) {
        cards += window.xqbrSkeletonPrioCard(i);
    }
    listEl.innerHTML = '<div class="xqbr-loading-stack">' +
        window.xqbrSpinnerHtml(message) +
        '<div class="xqbr-prio-list xqbr-skeleton-list">' + cards + '</div>' +
        '</div>';
};

window.xqbrRenderSummaryLoading = function (summaryEl) {
    if (!summaryEl) {
        return;
    }
    summaryEl.innerHTML = '<div class="xqbr-stat-list xqbr-skeleton-stats">' +
        window.xqbrSkeletonLine('w-75') +
        window.xqbrSkeletonLine('w-55') +
        window.xqbrSkeletonLine('w-60') +
        window.xqbrSkeletonLine('w-50') +
        window.xqbrSkeletonLine('w-65') +
        window.xqbrSkeletonLine('w-45') +
        '</div>';
};

window.xqbrSetAutosaveLoading = function (message) {
    if (typeof window.xqbrSetAutosaveStatus !== 'function') {
        return;
    }
    window.xqbrSetAutosaveStatus(
        '<span class="xqbr-spinner xqbr-spinner-inline" aria-hidden="true"></span> ' + (message || 'Saving draft…'),
        false,
        true
    );
};
JS;
}
