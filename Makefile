.PHONY: help install test test-unit test-integration coverage cs-fix cs-check psalm docs clean

help: ## Afficher cette aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

install: ## Installer les dépendances
	composer install

test: ## Lancer tous les tests
	./vendor/bin/phpunit

test-unit: ## Lancer les tests unitaires
	./vendor/bin/phpunit tests/Unit

test-integration: ## Lancer les tests d'intégration
	./vendor/bin/phpunit tests/Integration

coverage: ## Générer le rapport de couverture
	./vendor/bin/phpunit --coverage-html tests/coverage/html

cs-fix: ## Corriger le style de code
	./vendor/bin/php-cs-fixer fix src/
	./vendor/bin/php-cs-fixer fix tests/

cs-check: ## Vérifier le style de code
	./vendor/bin/php-cs-fixer fix --dry-run --diff src/
	./vendor/bin/php-cs-fixer fix --dry-run --diff tests/

psalm: ## Analyse statique avec Psalm
	./vendor/bin/psalm

docs: ## Générer la documentation
	@echo "Documentation générée dans README.md"

clean: ## Nettoyer les fichiers temporaires
	rm -rf tests/coverage/
	rm -rf .phpunit.cache/
	rm -f .phpunit.result.cache

validate: cs-check psalm test ## Validation complète (CI)
	@echo "✅ Validation complète réussie"
