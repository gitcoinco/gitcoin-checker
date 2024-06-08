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
```

### To use with ChatGPT

Specify your ChatGPT API Key in .env CHATGPT_API_KEY

### Bugsnag

Set BUGSNAG_API_KEY if you would like to receive errors from both the frontend and backend.

## To boot up the local development environment

```
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

http://localhost

## To build for production, use a separate terminal

```
make in                             # SSH into the php container
npm run build                       # Build for production
```

## Tests

```
make in                             # SSH into the php container
make test                           # Run phpunit tests
```

### Icons

Use the font-awesome 4 library:
https://fontawesome.com/v4/icons/

### Search

Scout is used for search, which uses Meilisearch in the background. To re-index:

php artisan scout:import "App\Models\Round"
php artisan scout:import "App\Models\Project"

## How things work, in broad strokes

### Overview

Running 'php artisan ingest:data', pulls data from the indexer and rounds data in metabase to build a local relational database on how rounds, projects, applications and donations all fit together.

When a new application comes in, it gets evaluated by AI against a set of criteria specified in the round eligibility criteria, and a score is produced. While the prompt is customizable, a generic prompt that is evolved and re-used offers a much lower barrier to entry.

When specifying the AI prompt used for evaluations, a number of variables can be included, such as the project name & description, github history, as well as the round eligibility criteria and application answers.

In addition to the AI evaluations, humans can also add their own evaluations and score any one of the evaluation criteria on the basis of Yes, No or Uncertain, as well as leave comments on why they scored things in a particular way.

Only applications that are in a pending state can be scored.

### Authentication

Users authenticate with their wallets (Only Metamask is supported at the time of writing)

There are three layers of authentication:

1. http://localhost/ro/access-control - A list of wallet addresses that should have access to all rounds, typically used by Gitcoin staff.
2. Any wallet address that is a round operator, automatically gets access to the rounds in which they are an operator.
3. Any additional wallet addresses that do not form part of 1 & 2, can be added here. Round -> Settings -> Application reviewers. This is typically used where additional reviewers are needed for a round.

There's a public section that contains projects, rounds & donations, available from http://localhost/public. This section requires no authentication to access.
