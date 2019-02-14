.PHONY: tools build sanity release prerelease

THIS_FILE := $(lastword $(MAKEFILE_LIST))

# the default is to do the 'sanity' checks, i.e. lint, style, mess, and stan.

default: tools
	composer lint
	composer style
	composer mess
	composer stan

# make tools checks that the necessary command line tools are available

tools:
	@echo $@  # print target name
	@echo Checking toolchain

ifeq (,$(wildcard ${HOME}/.composer/vendor/liip/rmt/command.php))
	$(error liip/rmt missing!)
endif

ifeq (,$(wildcard ${CURDIR}/vendor/bin/parallel-lint))
	$(error "parallel lint (jakub-onderka/php-parallel-lint) is not available; try composer install!")
else
	@echo We have parallel lint
endif

ifeq (,$(wildcard ${CURDIR}/vendor/bin/phpcs))
	$(error "php code sniffer (squizlabs/php_codesniffer phpcs) is not available; try composer install")
else
	@echo We have phpcs
endif

ifeq (,$(wildcard ${CURDIR}/vendor/bin/phpcbf))
	$(error "php code beautifier and fixer (squizlabs/php_codesniffer phpcbf) is not available; try make install_tools")
else
	@echo We have code beautifier and fixer
endif

ifeq (,$(wildcard ${CURDIR}/vendor/bin/phpmd))
	$(error "php mess tool (phpmd/phpmd) is not available; try composer install")
else
	@echo We have mess tool
endif

ifeq (,$(wildcard ${CURDIR}/vendor/bin/phpstan))
	$(error "php static analysis tool (phpstan/phpstan) is not available; try composer install")
else
	@echo We have static analysis tool
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

prerelease:
	composer update
	composer normalize
	composer run-script sanity
	composer install --no-dev
	composer dumpautoload -o

release:
	./RMT release

postelease: tools
	composer install
