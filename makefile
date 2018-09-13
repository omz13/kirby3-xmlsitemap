.PHONY: tools build sanity zip release

PHPRMT := $(shell command -v ./vendor/bin/RMT 2> /dev/null)
PHPLINT := $(shell command -v ./vendor/bin/parallel-lint 2> /dev/null)
PHPCS := $(shell command -v ./vendor/bin/phpcs 2> /dev/null)
PHPCBF := $(shell command -v ./vendor/bin/phpcbf 2> /dev/null)
PHPMESS := $(shell command -v ./vendor/bin/phpmd 2> /dev/null)
PHPSTAN := $(shell command -v ./vendor/bin/phpstan 2> /dev/null)

# the default is to do the 'sanity' checks, i.e. lint, style, mess, and stan.

default: tools
	composer lint
	composer style
	composer mess
	composer stan

# make tools checks that the necessary command line tools are available

tools:
	@echo Checking toolchain
ifndef PHPRMT
	$(error "php release management tool (rmt) is not available; try composer install --dev")
endif

ifndef PHPLINT
	$(error "php parallel lint (jakub-onderka/php-parallel-lint) is not available; try composer install --dev")
endif

ifndef PHPCS
  $(error "php code sniffer (squizlabs/php_codesniffer phpcs) is not available; try composer install --dev")
endif

ifndef PHPCBF
  $(error "php code beautifier and fixer (squizlabs/php_codesniffer phpcbf) is not available; try make install_tools")
endif
	@# check coding standards available
	$(if $(shell $(PHPCS) -i | grep omz13-k3p; if [ $$? -eq 1 ] ; then exit 1 ; fi), , $(error cs omz13-k3p not available; try composer install --dev))
	$(if $(shell $(PHPCS) -i | grep PHPCompatibility; if [ $$? -eq 1 ] ; then exit 1 ; fi), , $(error cs PHPCompatibility not available; composer install --dev))
	$(if $(shell $(PHPCS) -i | grep SlevomatCodingStandard; if [ $$? -eq 1 ] ; then exit 1 ; fi), , $(error cs slevomat not available; try composer install --dev))

ifndef PHPMESS
	$(error "php mess tool (phpmd/phpmd) is not available; try composer install --dev")
endif

ifndef PHPSTAN
	$(error "php static analysis tool (phpstan/phpstan) is not available; try composer install --dev")
endif

	@echo Toolchain is available

lint: tools
	composer run-script lint

build: tools
	composer validate
	composer run-script build

fix: tools
	composer run-script fix

style: tools
	composer run-script style

stan: tools
	composer run-script stan

sanity: tools
	composer validate
	composer run-script sanity

zip: tools
	composer run-script zip

release: tools
	composer normalize
	./RMT release
	composer run-script zip
