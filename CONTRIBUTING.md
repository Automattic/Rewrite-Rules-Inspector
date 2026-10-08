# Contributing to Rewrite Rules Inspector

Thank you for your interest in contributing. This document covers setting up a local environment, running the checks, and proposing a change.

## Code of Conduct

This project follows the [Automattic Code of Conduct](https://automattic.com/code-of-conduct/).

## Development Setup

### Prerequisites

- [Node.js](https://nodejs.org/) LTS or later (for `wp-env`)
- [Docker Desktop](https://www.docker.com/products/docker-desktop)
- [Composer](https://getcomposer.org/)

### Setup

1. Clone the repository.
2. Install dependencies:
   ```bash
   composer install
   ```
3. Start the WordPress environment:
   ```bash
   npx wp-env start
   ```
4. Visit http://localhost:8888/wp-admin/tools.php?page=rewrite-rules-inspector and log in with `admin` / `password`.

### Checks

```bash
composer lint                # PHP syntax
composer cs                  # Coding standards
composer test:unit           # Unit tests
composer test:integration    # Integration tests (single site, requires wp-env)
composer test:integration-ms # Integration tests (multisite, requires wp-env)
```

## Workflow

1. Create a branch from `develop`:
   ```bash
   git checkout develop
   git pull origin develop
   git checkout -b fix/short-description
   ```
2. Make your change, with tests.
3. Run the checks locally.
4. Push your branch and open a pull request against `develop`.

## Code Standards

We follow the [WordPress VIP Coding Standards](https://github.com/Automattic/VIP-Coding-Standards), configured in `.phpcs.xml.dist`. Run `composer cs-fix` to fix what PHPCS can fix automatically.

## Tests

Unit tests live in `tests/Unit/` and run without WordPress loaded. Integration tests live in `tests/Integration/` and run inside wp-env.

- New behaviour should come with tests.
- A bug fix should come with a test that fails without the fix.
- Name tests after the behaviour they check, for example `test_url_with_no_matching_rule_is_a_404()`.

## Pull Requests

- Keep each pull request to one change, and explain what it does and why.
- Reference related issues, for example `Fixes #123`.
- Make sure CI passes before requesting a review.
- **Sign your commits.** `develop` and `main` only accept commits with a verified signature, so a pull request with an unsigned commit cannot be merged until it is re-signed and force-pushed. See GitHub's guide to [signing commits](https://docs.github.com/en/authentication/managing-commit-signature-verification/signing-commits).

## Releases

Maintainers cut releases from a `release/x.y.z` branch into `main`. Publishing the GitHub release deploys the plugin to WordPress.org.
