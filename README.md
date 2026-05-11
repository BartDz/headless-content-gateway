# CMS Content Gateway

You have an app pulling content from WordPress. Your client wants to switch to Contentful. Normally - a week of rewriting code. With this project - you change one word in a config file and you're done. The gateway sits between your frontend and your CMS, translating requests so you always call the same API regardless of what's behind it. Supports WordPress, Contentful, Strapi, and Storyblok.

**This is for you if:**
- You're migrating from one CMS to another and don't want to touch your frontend
- You manage multiple clients on different CMS platforms and want one standard API
- You want to migrate gradually - some content already in the new CMS, some still in the old one, site stays up the whole time
- You need Redis caching without writing your own cache logic - the gateway handles it automatically (first request ~200ms, subsequent ~3ms)
- You have a multilingual site and need language fallback (pl → en)

**Don't bother if:**
- You have one CMS and no plans to change it - you don't need a middleman
- You need a full CMS with an admin panel - this is an API layer only, not a CMS
- Your slugs differ between platforms at scale - you need to unify them first (see "Migration" section)

**Stack:** PHP 8.3 · Symfony 7 · API Platform 3 · Redis · Docker

## How it works

```
Your app / frontend
        ↓
GET /content/article/how-to-grow-tomatoes
        ↓
  CMS Content Gateway  ←→  Redis (cache)
        ↓
  WordPress / Contentful / Strapi / Storyblok
```

The gateway fetches content from your CMS and stores it in Redis. Subsequent requests are served from cache without hitting the CMS. When you publish new content - a webhook reaches the gateway and the cache is invalidated automatically.

## Supported platforms

| Adapter | Protocol |
|---|---|
| WordPress | WP REST API v2 |
| Strapi v4 | Strapi REST API |
| Contentful | Contentful Delivery API |
| Storyblok | Storyblok CDN API v2 |
| Sanity | Sanity HTTP API + GROQ |

Any direction between all five platforms.

## Migration guide (e.g. WordPress → Storyblok)

### The one requirement: identical slugs

The gateway identifies content by slug, not by ID. If an article in WordPress has slug `how-to-grow-tomatoes`, it must have slug `how-to-grow-tomatoes` in Storyblok too. That's the only condition.

A few exceptions? Use slug mapping in config:

```yaml
content_types:
  article:
    adapter: storyblok
    slug_map:
      "old-wp-slug": "new-storyblok-slug"
      "hello_world": "hello-world"
```

Hundreds of exceptions? Generate this YAML from a CSV exported from both platforms.

### Gradual migration strategy

You don't have to move everything at once. You can spend weeks with some content still on WordPress and some already on Storyblok - the site works the entire time.

```yaml
content_types:
  article:
    adapter: wordpress     # old articles still here
  product:
    adapter: storyblok     # products already migrated
  landing:
    adapter: storyblok     # landing pages already migrated
```

Once you finish migrating articles - change `wordpress` to `storyblok` in one line.

### Migration steps

1. Run the gateway (see "Quick start" below)
2. Point your frontend at the gateway instead of directly at WordPress - one-time cost
3. Move content to Storyblok, keeping slugs identical
4. Switch content types in `cms_bridge.yaml` as you go
5. Once everything is migrated - shut down WordPress

## Quick start

Two options - pick one:

### Option A: local dev (PHP + Symfony CLI installed on your machine)

Requires PHP 8.3, Composer, and Symfony CLI.

```bash
cp .env.example .env
# Edit .env - add your CMS credentials and URLs

docker compose up -d redis    # Redis only, everything else runs locally

composer install
php bin/console cache:clear
symfony server:start          # http://localhost:8000
```

Live dashboard: http://localhost:8000/demo

### Option B: Docker (no local PHP needed)

Everything in containers - PHP-FPM, nginx, Redis, WordPress stub.

```bash
cp .env.example .env.docker
# Edit .env.docker - add your CMS credentials and URLs

docker compose up -d --build
```

App available at http://localhost:8080.

> **Do not run** `composer install`, `php bin/console`, or `symfony server:start` with Option B - the Dockerfile handles all of that at build time.

## Endpoints

| Method | Path | Description |
|---|---|---|
| GET | `/content/{type}` | Paginated content list |
| GET | `/content/{type}/{slug}` | Single entry |
| POST | `/webhooks/{adapter}` | Cache invalidation (HMAC-verified) |
| GET | `/health` | Adapter status |
| GET | `/docs` | OpenAPI docs (Swagger UI) |
| GET | `/graphql` | GraphQL (GraphiQL) |
| GET | `/demo` | Demo dashboard |

## Usage examples

### Fetch a list of articles

```bash
curl -H "Accept: application/json" http://localhost:8080/content/article
```

Response includes `X-Cache: MISS` on the first request, `X-Cache: HIT` on subsequent ones.

### Fetch a single article

```bash
curl -H "Accept: application/json" http://localhost:8080/content/article/how-to-grow-tomatoes
```

### Fetch with language fallback

```bash
curl -H "Accept-Language: pl,en;q=0.9" \
     -H "Accept: application/json" \
     http://localhost:8080/content/article/how-to-grow-tomatoes
# Tries Polish first, falls back to English if not found
```

### Invalidate cache after publishing (webhook)

```bash
# Specific content type
curl -X POST http://localhost:8080/webhooks/wordpress \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Secret: $WEBHOOK_SECRET_WORDPRESS" \
  -d '{"contentType": "article"}'

# All WordPress content
curl -X POST http://localhost:8080/webhooks/wordpress \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Secret: $WEBHOOK_SECRET_WORDPRESS" \
  -d '{}'
```

### Check adapter health

```bash
curl http://localhost:8080/health | python3 -m json.tool
# {"status": "ok", "adapters": {"wordpress": "up", "contentful": "up", ...}}
```

### Cache latency benchmark

```bash
time curl -sf http://localhost:8080/content/article > /dev/null   # MISS: ~200ms
time curl -sf http://localhost:8080/content/article > /dev/null   # HIT:  ~3ms
```

### Smoke test

```bash
make demo            # health → MISS → HIT → invalidate → MISS again
make demo-warm       # pre-warm cache for all content types
make demo-invalidate # force invalidation for all adapters
```

### GraphQL

Open http://localhost:8080/graphql in a browser:

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

## Configuration

All routing logic lives in `config/cms_bridge.yaml` - no PHP changes needed to add content types or switch adapters.

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
  product:
    adapter: contentful
    cache_ttl: 900
    field_map:
      title: fields.title
      body: fields.description
      slug: fields.slug
```

## Tests

```bash
./vendor/bin/pest                              # unit + functional
./vendor/bin/pest --group=integration         # requires Docker (WordPress on :8081)
./vendor/bin/phpstan analyse                  # static analysis, level 6
./vendor/bin/php-cs-fixer fix --dry-run       # style check
./vendor/bin/php-cs-fixer fix                 # apply fixes
```

## Architecture

```
Request → ContentItemProvider / ContentCollectionProvider
            ↓
          AdapterRegistry (picks adapter from cms_bridge.yaml)
            ↓
          CmsAdapter (WP / Strapi / Contentful / Storyblok)
            ↓
          ContentCacheManager (Redis, tag-based invalidation)
            ↓
          ContentTransformerPipeline (Markdown → HTML, image URL rewriting)
            ↓
          ContentEntry (unified model)
```

Webhook `POST /webhooks/{adapter}` validates HMAC signature, then invalidates cache by tags.
