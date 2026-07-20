# FUSION API Reference

Base URL (sandbox example): `https://admin.sandbox.xperiencefusion.com/api/v1`

All routes below use middleware **`fusion.api`** (Bearer token). WordPress wizards call these via server-side proxy (`xfusion_oo_api_request`, `xfarp_picker_api_request`) — the token never reaches the browser.

## Documents

| Document | Scope |
|----------|--------|
| [authentication.md](./authentication.md) | Bearer token, env vars, diagnostics |
| [one-on-one.md](./one-on-one.md) | 1-on-1 Alignment Capture™ wizard API |
| [arp.md](./arp.md) | Annual Readiness Plan™ wizard API |

## Public routes (same prefix, no wizard)

| Method | Path | Controller |
|--------|------|------------|
| GET | `/companies` | `CompanyPublicController@index` |
| GET | `/companies/{company}` | `CompanyPublicController@show` |
| GET | `/companies/{company}/participation-charts` | `ParticipationChartsController@show` |
| GET | `/course-groups` | `CourseGroupPublicController@index` |

## Response envelope

Most wizard endpoints return:

```json
{
  "success": true,
  "data": { }
}
```

Errors:

```json
{
  "success": false,
  "message": "Human-readable reason"
}
```

HTTP status: `403` forbidden, `422` validation, `502` LLM failure, etc.

## Eloquent models (FUSION OS)

| Component | Models (`app/Models/`) |
|-----------|-------------------------|
| 1-on-1 | `OneOnOne`, `OneOnOneConversation`, `OneOnOnePreparation`, `OneOnOneNote`, `OneOnOneCommitment`, `OneOnOneAiBrief`, `OneOnOneAiSynthesis` |
| ARP | `Arp`, `ArpFutureState`, `ArpReadinessPriority`, `ArpStrategicPriority`, `ArpLearning`, `ArpAiAssessment`, `ArpVersion` |
| Cross-cutting | `FusionEvidenceLog` (ARP publish / AI events) |

## Code entry points

| Area | Path |
|------|------|
| Routes | `routes/api.php` (prefix `v1`) |
| 1-on-1 controller | `app/Http/Controllers/Api/OneOnOneController.php` |
| ARP controller | `app/Http/Controllers/Api/ArpController.php` |
| WP 1-on-1 bridge | `app/Http/Plugin/.../one-on-one-shortcode.php` |
| WP ARP bridge | `app/Http/Plugin/.../annual-readiness-plan/arp-picker.php` |
