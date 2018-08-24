.PHONY: zip build

build:
	composer run-script build

sanity:
	composer validate
	composer style
	composer fix
	composer mess

zip:
	composer run-script zip
