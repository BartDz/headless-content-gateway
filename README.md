# CMS Content Gateway

Unified REST + GraphQL API over multiple headless CMS platforms. Single endpoint regardless of which CMS is behind it.

**Stack:** PHP 8.3 · Symfony 7 · API Platform 3 · Redis · Docker

## Supported adapters

| Adapter | Protocol |
|---|---|
| WordPress | WP REST API v2 |
| Strapi v4 | Strapi REST API |
| Contentful | Contentful Delivery API |
| Storyblok | Storyblok CDN API v2 |

## Quick start

Two workflows — pick one:

### Option A: Local dev (PHP + Symfony CLI installed locally)

Requires PHP 8.3, Composer, and Symfony CLI on the host machine.

```bash
cp .env.example .env
# Edit .env — set CMS credentials and URLs

docker compose up -d redis    # Redis only (no app container needed)

composer install
php bin/console cache:clear
symfony server:start          # http://localhost:8000
```

Open http://localhost:8000/demo for the live dashboard.

### Option B: Docker (no local PHP required)

Everything runs in containers — PHP-FPM, nginx, Redis. No `composer install` needed locally.

```bash
cp .env.example .env.docker
# Edit .env.docker — set CMS credentials and URLs

docker compose up -d --build  # builds app image, starts all services
```

App available at http://localhost:8080 (nginx → PHP-FPM → Redis).

> **Do not run** `composer install`, `php bin/console`, or `symfony server:start` when using Option B — the Dockerfile handles dependencies and cache warmup at build time.

## Endpoints

| Method | Path | Description |
|---|---|---|
| GET | `/content/{type}` | Paginated collection |
| GET | `/content/{type}/{slug}` | Single entry |
| POST | `/webhooks/{adapter}` | Cache invalidation (HMAC) |
| GET | `/health` | Adapter status |
| GET | `/docs` | OpenAPI docs |
| GET | `/graphql` | GraphQL (GraphiQL) |
| GET | `/demo` | Dashboard |

## Manual testing

### 1. Check adapter health

```bash
curl http://localhost:8000/health | python3 -m json.tool
# Expected: {"status": "ok", "adapters": {"wordpress": "up", ...}}
```

### 2. Fetch content collection (cache MISS)

```bash
curl -i -H "Accept: application/json" http://localhost:8000/content/article
# X-Cache: MISS on first request
```

### 3. Verify cache HIT on repeat

```bash
curl -i -H "Accept: application/json" http://localhost:8000/content/article
# X-Cache: HIT on subsequent requests (served from Redis)
```

### 4. Fetch single item

```bash
curl -i -H "Accept: application/json" http://localhost:8000/content/article/hello-world
# Returns: {"id": "...", "slug": "hello-world", "fields": {...}, ...}
```

### 5. Multi-locale fallback

```bash
curl -H "Accept-Language: pl,en;q=0.9" \
     -H "Accept: application/json" \
     http://localhost:8000/content/article/hello-world
# Tries pl first, falls back to en on 404
```

### 6. Invalidate cache via webhook

```bash
# Invalidate specific content type
curl -X POST http://localhost:8000/webhooks/wordpress \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Secret: $WEBHOOK_SECRET_WORDPRESS" \
  -d '{"contentType": "article"}'
# Returns: {"invalidated": "wordpress.article", "ok": true}

# Invalidate all wordpress content
curl -X POST http://localhost:8000/webhooks/wordpress \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Secret: $WEBHOOK_SECRET_WORDPRESS" \
  -d '{}'
# Returns: {"invalidated": "wordpress", "ok": true}
```

### 7. Full smoke test sequence

```bash
make demo          # health → MISS → HIT → invalidate → MISS again
make demo-warm     # pre-warm cache for all content types
make demo-invalidate  # force invalidation for all adapters
```

### 8. Cache latency benchmark

```bash
# Measure MISS (CMS roundtrip) vs HIT (Redis only)
time curl -sf http://localhost:8000/content/article > /dev/null   # first: MISS
time curl -sf http://localhost:8000/content/article > /dev/null   # second: HIT

# Typical results with WordPress adapter:
#   MISS: ~120-300ms (WordPress HTTP roundtrip)
#   HIT:  ~2-5ms (Redis only)
```

### 9. GraphQL playground

Open http://localhost:8000/graphql in a browser. Sample query:

```graphql
query {
  contents(contentType: "article") {
    edges {
      node {
        id
        slug
        fields
      }
    }
  }
}
```

## Tests

```bash
./vendor/bin/pest                                    # unit + functional (65 tests)
./vendor/bin/pest --exclude-group=integration        # explicit, skips live-CMS tests
./vendor/bin/pest --group=integration                # requires Docker (WordPress on :8081)
./vendor/bin/phpstan analyse                         # level 6, 0 errors
./vendor/bin/php-cs-fixer fix --dry-run              # style check
./vendor/bin/php-cs-fixer fix --allow-risky=yes      # apply fixes
```

## Configuration

Edit `config/cms_bridge.yaml` to add content types, switch adapters, configure field mappings and cache TTL. No PHP changes needed.

```yaml
content_types:
  article:
    adapter: wordpress
    cache_ttl: 3600
    transformers:
      - markdown_to_html
      - image_url_rewriter
    field_map:
      title: title.rendered
      body: content.rendered
      slug: slug
      publishedAt: date
```

## Docker stack

```bash
# Full stack (app + nginx + Redis + WordPress)
docker compose up -d

# App only serves on http://localhost:8080 via nginx
# Redis on :6379, WordPress stub on :8081

# Build the app image
docker compose build app
```

## Architecture

```
Request → ContentItemProvider / ContentCollectionProvider
            ↓
          AdapterRegistry (selects adapter from cms_bridge.yaml)
            ↓
          CmsAdapter (WP / Strapi / Contentful / Storyblok)
            ↓
          ContentCacheManager (Redis, tag-based)
            ↓
          ContentTransformerPipeline (Markdown → HTML, image rewriting)
            ↓
          ContentEntry (unified model)
```

Webhook `POST /webhooks/{adapter}` validates HMAC signature, then calls `cache.invalidateTags()`.
