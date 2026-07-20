# API Authentication

## WordPress → Laravel

Routes under `/api/v1/*` (except legacy unauthenticated routes at `/api/*` root) require middleware **`fusion.api`**.

The WordPress plugin sends:

```http
Authorization: Bearer <XFUSION_API_BEARER_TOKEN>
Accept: application/json
```

Configure in WordPress (typically `wp-config.php` or plugin constants):

```php
define('XFUSION_LARAVEL_API_BASE', 'https://admin.sandbox.xperiencefusion.com');
define('XFUSION_API_BEARER_TOKEN', '<shared-secret>');
```

Laravel validates the token in the `fusion.api` middleware. End-user WordPress login is separate — APIs also receive `user_id` in query/body for authorization checks inside controllers.

## Laravel → LLM (xfusion-llm)

AI generation uses a **different** key in Laravel `.env`:

```env
XFUSION_LLM_API_URL=http://<llm-host>:8000
XFUSION_LLM_API_KEY=<same value as API_KEY in xfusion-llm .env>
```

Diagnostics:

```bash
php artisan xfusion:llm-probe
```

Common failure: `401 Invalid or missing API Key` — keys must match exactly (no trailing whitespace; LLM trims on read).

## Authorization patterns

| Feature | Rule |
|---------|------|
| ARP view | User is member of ARP's `company_group_id` |
| ARP edit / generate / publish | User is **leader** of that group |
| 1-on-1 conversation | User is leader or employee on the pair |
| 1-on-1 prep privacy | Counterpart prep hidden until `POST .../reveal` |

See component docs for per-endpoint `user_id` requirements.
