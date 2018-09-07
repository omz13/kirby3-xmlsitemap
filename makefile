.PHONY: tools build sanity zip release

PHPRMT := $(shell command -v RMT 2> /dev/null)
PHPLINT := $(shell command -v ./vendor/bin/parallel-lint 2> /dev/null)
PHPCS := $(shell command -v ./vendor/bin/phpcs 2> /dev/null)
PHPCBF := $(shell command -v ./vendor/bin/phpcbf 2> /dev/null)
PHPMESS := $(shell command -v phpmd 2> /dev/null)
PHPSTAN := $(shell command -v phpstan 2> /dev/null)

default: tools
	composer lint
	composer style
	composer mess

tools:
ifndef PHPLINT
	$(error "pho parallel lint (parallel-lint) is not available; try composer global require jakub-onderka/php-parallel-lint")
endif

ifndef PHPCS
  $(error "php code sniffer (phpcs) is not available; try composer install")
endif

ifndef PHPCBF
  $(error "php code fixer (phpcbf) is not available; try composer install")
endif

ifndef PHPMESS
  $(error "php mess tool (phpmd) is not available; try composer global require phpmd/phpmd")
endif

ifndef PHPSTAN
  $(error "php static analysis tool (phpstan) is not available; try composer global require phpstan/phpstan")
endif
	@echo Toolchain available

build: tools
	composer validate
	composer run-script build

style: tools
	composer style

fix: tools
	composer fix

sanity: tools
	composer validate
	composer run-script sanity

zip: tools
	composer run-script zip

release: tools
ifndef PHPRMT
	  $(error "php release management tool (rmt) is not available; try composer global require liip/rmt")
endif
	./RMT release
	composer run-script zip
