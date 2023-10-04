# Checker

## Development

The project uses Laravel and Vuejs, with Inertia for serving up the SPA.

## To launch with Docker

```
make up
```

## To ssh into the container and run npm

```
make in
npm run dev
```

http://localhost

## Overview

A system aimed at Round Managers that checks the on-chain transactions of Gitcoin and provides a set of interfaces for reviewing projects using a combination of human viewers and AI, which can be based on a set of evaluation criteria for a specific round.

## Tests

```
make in
make test
```
