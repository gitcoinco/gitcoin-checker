.PHONY: up
up:
	./vendor/bin/sail up

.PHONY: down
down:
	./vendor/bin/sail down


.PHONY: in
in:
	docker exec -it checker.php /bin/bash

.PHONY: in-node
in-node:
	docker exec -it nodejs_app /bin/bash

.PHONY: test
test:
	./vendor/bin/phpunit

.PHONY: cc
cc:
	composer clear-all-cache
