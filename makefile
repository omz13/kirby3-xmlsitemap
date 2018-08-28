.PHONY: zip build tools

PHPRMT := $(shell command -v RMT 2> /dev/null)
PHPCS := $(shell command -v phpcs 2> /dev/null)
PHPCBF := $(shell command -v phpcbf 2> /dev/null)
PHPMESS := $(shell command -v phpmd 2> /dev/null)
PHPLOC := $(shell command -v phploc 2> /dev/null)

tools:
ifndef PHPRMT
  $(error "php release management tool (rmt) is not available; try composer global require liip/rmt")
endif

ifndef PHPCS
  $(error "php code sniffer (phpcs) is not available; try composer global require squizlabs/php_codesniffer")
endif

ifndef PHPCBF
  $(error "php code fixer (phpcbf) is not available; try composer global require squizlabs/php_codesniffer")
endif

ifndef PHPMESS
  $(error "php mess tool (phpmd) is not available; try composer global require phpmd/phpmd")
endif

ifndef PHPLOC
  $(error "php mess tool (phploc) is not available; try composer global require phploc/phploc")
endif
	@echo Toolchain available

build: tools
	composer run-script build
	phploc src/

sanity: tools
	composer validate
	composer run-script sanity

zip: tools
	composer run-script zip

release: tools
	./RMT release
	composer run-script zip
