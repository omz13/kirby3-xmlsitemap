.PHONY: tools build sanity zip release

PHPRMT := $(shell command -v RMT 2> /dev/null)
PHPLINT := $(shell command -v ./vendor/bin/parallel-lint 2> /dev/null)
PHPCS := $(shell command -v ./vendor/bin/phpcs 2> /dev/null)
PHPCBF := $(shell command -v ./vendor/bin/phpcbf 2> /dev/null)
PHPMESS := $(shell command -v ./vendor/bin/phpmd 2> /dev/null)
PHPSTAN := $(shell command -v ./vendor/bin/phpstan 2> /dev/null)

default: tools
	composer lint
	composer style
	composer mess
	composer stan

tools:
ifndef PHPLINT
	$(error "php parallel lint (parallel-lint) is not available; try composer install")
endif

ifndef PHPCS
  $(error "php code sniffer (phpcs - squizlabs/php_codesniffer) is not available; try composer install")
endif

ifndef PHPCBF
  $(error "php code beautifier and fixer (phpcbf - squizlabs/php_codesniffer) is not available; try composer install")
endif

	$(if $(shell $(PHPCS) -i | grep omz13-k3p; if [ $$? -eq 1 ] ; then exit 1 ; fi), , $(error cs omz13-k3p not available; try composer install))
	$(if $(shell $(PHPCS) -i | grep SlevomatCodingStandard; if [ $$? -eq 1 ] ; then exit 1 ; fi), , $(error cs slevomat not available; try composer install))

ifndef PHPMESS
	$(error "php mess tool (phpmd/phpmd) is not available; try composer install")
endif

ifndef PHPSTAN
	$(error "php static analysis tool (phpstan/phpstan) is not available; try composer install")
endif
	@echo Toolchain available

lint: tools
	composer run-script lint

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
