<?php
/**
 * Step 6 — AI Meeting Synthesis™ (UI shell, static dummy data).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfoo_wizard_step_synthesis_js(): string
{
    return <<<'JS'
synthesis: function () {
    var iconBase = 'https://sandbox.xperiencefusion.com/wp-content/uploads/2026/07/';
    function card(iconSrc, title, body) {
        return '<div class="xfw-insight-card">' +
            '<div class="icon"><img src="' + iconSrc + '" alt="" width="50" height="50"></div>' +
            '<h3>' + title + '</h3>' + body +
            '<a href="#" class="xfw-link">View Details &rarr;</a></div>';
    }
    return '<h2 class="xfw-section-title">Step 6. AI Meeting Synthesis™</h2>' +
        '<p class="xfw-section-desc">FUSION analyzes the conversation, preparation, commitments, and historical trends to create a comprehensive meeting record and actionable insights for both participants.</p>' +
        '<div class="xfw-banner">ℹ️ <span>This summary is AI-generated and based on the data and discussion from this 1-on-1 conversation. Review and reflect on the insights together.</span></div>' +
        '<div class="xfw-grid-2" style="margin-bottom:1rem">' +
        card(iconBase + 'Clipboard-Checkmark-Blue-Icon.svg', 'Meeting Summary™', '<ul><li>Strong alignment on QBR priorities</li><li>Progress made on Project Phoenix</li><li>Roadblocks identified in data access</li><li>Development plan for strategic communication</li></ul>') +
        card(iconBase + 'Arrow-in-Target-Icon-1.svg', 'Alignment Summary™',
            '<p class="xfw-muted">How aligned both participants are on priorities, goals, and expectations.</p>' +
            '<div style="font-size:1.6rem;font-weight:800;color:var(--green)">4.2<span style="font-size:1rem;color:var(--muted);font-weight:400"> / 5</span></div>' +
            '<div class="xfw-muted">Aligned</div>' +
            '<div class="xfw-progress-track" style="margin:.5rem 0"><div class="xfw-progress-fill" style="width:84%"></div></div>') +
        '</div>' +
        '<div class="xfw-grid-2" style="margin-bottom:1rem">' +
        card(iconBase + 'Arrow-in-Target-Icon-1.svg', 'Development Summary™', '<ul><li>Strategic communication skills</li><li>Cross-functional collaboration</li><li>Data-driven decision making</li><li>Leadership presence in stakeholder updates</li></ul>') +
        card(iconBase + 'Clipboard-Checkmark-Icon-1.svg', 'Commitment Summary™', '<ul><li>Employee Commitments: <b>3 active</b></li><li>Leader Commitments: <b>3 active</b></li><li>Open Commitments: <b>6 total</b></li></ul>') +
        '</div>' +
        '<div class="xfw-grid-2" style="margin-bottom:1rem">' +
        card(iconBase + 'Warning-Triangle-Icon-2.svg', 'Emerging Risks™', '<ul><li>Data access delays could impact timeline</li><li>Competing priorities may affect capacity</li><li>Communication bottlenecks with other teams</li></ul>') +
        card(iconBase + 'Trending-Up-Arrow-Icon-Green-1.svg', 'Emerging Opportunities™', '<ul><li>Expand stakeholder communication plan</li><li>Implement micro-learning strategy</li><li>Leverage cross-functional planning session</li></ul>') +
        '</div>' +
        '<div class="xfw-card" style="margin-bottom:1rem">' +
        '<div class="xfw-commit-title"><img src="' + iconBase + 'Orange-Light-Bulb-Icon.svg" alt="" width="40" height="40"><h3>Suggested Coaching Topics™</h3></div>' +
        '<div class="xfw-row">' + ['Strategic Communication', 'Stakeholder Management', 'Data-Driven Decisions', 'Career Growth Planning'].map(function (t) {
            return '<span class="xfw-badge" style="background:#eef2ff;color:#4338ca">' + t + '</span>';
        }).join('') + '</div>' +
        '</div>' +
        '<div class="xfw-card">' +
        '<div class="xfw-commit-title"><img src="' + iconBase + 'Calendar-Icon-Teal.svg" alt="" width="40" height="40"><h3>Recommended Follow-up™</h3></div>' +
        '<ul class="xfw-numbered" style="list-style:none"><li>&#9989; Check-in on data access resolution by May 28</li><li>&#128101; Schedule cross-departmental planning session by June 4</li><li>&#128196; Review communication strategy progress in next 1-on-1</li></ul>' +
        '</div>';
}
JS;
}
