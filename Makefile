.PHONY: up
up:
	./vendor/bin/sail up

.PHONY: down
down:
	./vendor/bin/sail down


.PHONY: in
in:
	docker exec -it checker-laravel.test-1 /bin/bash

.PHONY: test
test:
	./vendor/bin/phpunit
