.PHONY: zip build

build:
	composer run-script build

sanity:
	composer validate
	composer run-script sanity

zip:
	composer run-script zip

release:
	composer run-script release
	./rmt release
