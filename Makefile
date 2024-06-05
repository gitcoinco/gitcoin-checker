# Start the containers
.PHONY: up
up:
	./vendor/bin/sail up --build

# Stop the containers
.PHONY: down
down:
	./vendor/bin/sail down


# SSH into the container
.PHONY: in
in:
	docker exec -it checker.php /bin/bash


# Run the tests
.PHONY: test
test:
	./vendor/bin/phpunit

# Clear all the cache
.PHONY: cc
cc:
	composer clear-all-cache

# Index all the searchable models
.PHONY: index
index:
	php artisan scout:import "App\Models\Round"
	php artisan scout:import "App\Models\Project"

