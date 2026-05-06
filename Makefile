.PHONY: demo demo-warm demo-invalidate help

GATEWAY_URL ?= http://localhost:8000
ADAPTER     ?= wordpress

demo: ## Run smoke-test curl sequence (CI-friendly)
	@echo "=== Health check ==="
	curl -sf $(GATEWAY_URL)/health | python3 -m json.tool
	@echo ""
	@echo "=== Fetch article (cache MISS) ==="
	curl -sf -D - $(GATEWAY_URL)/content/article | head -20
	@echo ""
	@echo "=== Fetch article again (cache HIT) ==="
	curl -sf -D - $(GATEWAY_URL)/content/article | head -5
	@echo ""
	@echo "=== Trigger cache invalidation ==="
	curl -sf -X POST $(GATEWAY_URL)/webhooks/$(ADAPTER) \
	  -H "Content-Type: application/json" \
	  -H "X-Webhook-Secret: demo" \
	  -d '{}' | python3 -m json.tool
	@echo ""
	@echo "=== Fetch article after invalidation (cache MISS again) ==="
	curl -sf -D - $(GATEWAY_URL)/content/article | head -5
	@echo "=== Demo complete ==="

demo-warm: ## Pre-warm cache for all content types
	@echo "Warming cache..."
	curl -sf $(GATEWAY_URL)/content/article > /dev/null && echo "article: warmed"
	curl -sf $(GATEWAY_URL)/content/post > /dev/null && echo "post: warmed"

demo-invalidate: ## Trigger webhook cache invalidation for all adapters
	@echo "Invalidating wordpress cache..."
	curl -sf -X POST $(GATEWAY_URL)/webhooks/wordpress \
	  -H "Content-Type: application/json" \
	  -H "X-Webhook-Secret: demo" \
	  -d '{}' | python3 -m json.tool
	@echo "Invalidating strapi cache..."
	curl -sf -X POST $(GATEWAY_URL)/webhooks/strapi \
	  -H "Content-Type: application/json" \
	  -H "X-Webhook-Secret: demo" \
	  -d '{}' | python3 -m json.tool

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'
