# Checker

Leveraging the Gitcoin Indexer, this tool simplifies the process for round managers to select projects for inclusion. By defining specific evaluation criteria, managers can utilize ChatGPT for automated project assessments and scoring.

## Development

The project uses Laravel and Vuejs, with Inertia for serving up the SPA. You will need composer and php installed locally.

## Local requirements

php 8.2
composer 2

## To launch with Docker (keep this running in one terminal)

```
cp .env.example .env
composer install                    # Setup composer packages
make up                             # Boot docker-compose up
```

## To build for development, use a separate terminal

```
make in                             # SSH into the php container
php artisan migrate                 # Migrate all the DB tables
php artisan ingest:data             # Ingest data from the indexer and fill the relational DB
npm i                               # Install npm packages
npm run dev                         # Build for local dev
```

## To use with ChatGPT

Specify your ChatGPT API Key in .env CHATGPT_API_KEY

## To build for production, use a separate terminal

```
make in                             # SSH into the php container
npm run build                       # Build for production
```

http://localhost

## Tests

```
make in                             # SSH into the php container
make test                           # Run phpunit tests
```

### Icons

Use the font-awesome 4 library:
https://fontawesome.com/v4/icons/
