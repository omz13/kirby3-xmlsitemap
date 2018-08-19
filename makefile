.PHONY: zip build

build:
	composer run-script build

zip:
	composer run-script zip
