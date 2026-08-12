CLI_ARGS := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
$(eval $(sort $(subst :,\:,$(CLI_ARGS))):;@:)

PRIMARY_GOAL := $(firstword $(MAKECMDGOALS))
ifeq ($(PRIMARY_GOAL),)
    PRIMARY_GOAL := help
endif

ifeq ($(PRIMARY_GOAL),test-local)
test-local: ## Run PHPUnit on the host (MySQL via 127.0.0.1:3306).
	DB_HOST=127.0.0.1 DB_PORT=3306 REDIS_PASSWORD=redis.password ./vendor/bin/phpunit $(CLI_ARGS)
endif

ifeq ($(PRIMARY_GOAL),test)
test: ## Alias for test-local.
	DB_HOST=127.0.0.1 DB_PORT=3306 REDIS_PASSWORD=redis.password ./vendor/bin/phpunit $(CLI_ARGS)
endif

ifeq ($(PRIMARY_GOAL),composer)
composer: ## Run Composer.
	composer $(CLI_ARGS)
endif

ifeq ($(PRIMARY_GOAL),psalm)
psalm: ## Run Psalm.
	./vendor/bin/psalm $(CLI_ARGS)
endif

ifeq ($(PRIMARY_GOAL),help)
help: ## This help.
	@awk 'BEGIN { printf "\nUsage:\n  make \033[36m<target>\033[0m\n" } \
	/^#$$/ { blank = 1; next } \
	blank && /^# [a-zA-Z]/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 3); blank = 0; next } \
	/^[a-zA-Z_-]+:([^=]|$$)/ { \
		split($$0, parts, "##"); \
		target = parts[1]; sub(/:.*/, "", target); \
		desc = parts[2]; \
		gsub(/^[[:space:]]+|[[:space:]]+$$/, "", desc); \
		printf "  \033[36m%-25s\033[0m %s\n", target, desc; \
		blank = 0; \
	}' $(MAKEFILE_LIST)
endif
