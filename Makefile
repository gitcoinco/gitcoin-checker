.PHONY: up
up:
	./vendor/bin/sail up

.PHONY: down
down:
	./vendor/bin/sail down


.PHONY: in
in:
	docker exec -it checker.php /bin/bash

.PHONY: test
test:
	./vendor/bin/phpunit
