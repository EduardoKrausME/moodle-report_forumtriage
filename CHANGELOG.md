# Changelog

This changelog covers **only the two most recently published GitHub releases** of `report_forumtriage`. Each entry describes verified Git differences between the release tag and its immediately preceding **published release** tag. Later commits on `master` are excluded.

## [1.0.4] - 2026-10-15

Published: 2026-10-15 20:42:21 UTC.

Comparison: [1.0.3...1.0.4](https://github.com/EduardoKrausME/moodle-report_forumtriage/compare/1.0.3...1.0.4)

### Continuous integration

- Updated the GitHub Actions PostgreSQL CI service image from `postgres:16` to **`postgres:17`**.
- Updated the MariaDB CI service image from `mariadb:10` to **`mariadb:11.4`**.
- Replaced the previous MariaDB empty-password environment configuration with `MARIADB_ALLOW_EMPTY_ROOT_PASSWORD=1`; switched the health check from `mysqladmin ping` to `healthcheck.sh --connect --innodb_initialized` with adjusted retries.

### Release metadata and scope

- Updated `version.php` from numeric plugin version `2026101200` / release `1.0.3` to `2026101500` / release `1.0.4`.
- The four-commit published-tag diff changes **only** `.github/workflows/ci.yml` and `version.php`. It contains no application source changes or new forum-analysis behavior. The minimum Moodle build (`2024100700`) remains unchanged. CI database versions are not, by themselves, new production database requirements.

## [1.0.3] - 2026-10-12

Published: 2026-10-12 11:51:44 UTC.

Comparison: [1.0.2...1.0.3](https://github.com/EduardoKrausME/moodle-report_forumtriage/compare/1.0.2...1.0.3)

### Continuous integration and compatibility testing

- Changed the PHP 8.3 CI matrix entry from `MOODLE_502_STABLE` to **`MOODLE_503_STABLE` (Moodle 5.3)**.
- Added a GitHub Actions checkout of `EduardoKrausME/moodle-local_ai_bridge` into `extra-plugins/ai_bridge` and passed `--extra-plugins ./extra-plugins` to `moodle-plugin-ci install` so CI installs the plugin's AI Bridge dependency.

### Release workflow and assets

- Changed the release workflow to pass the commit message via a `RELEASE_MESSAGE` environment variable, rather than interpolating arbitrary commit message text directly into a shell script, reducing shell-injection exposure when parsing version commits.
- Removed the custom plugin ZIP creation and asset upload steps, which previously referred to `local_ai_bridge` paths and archive names. The updated workflow still creates an annotated release tag and publishes a GitHub Release, but this tag diff does **not** establish that the release includes an installable plugin ZIP attachment.
- Added the plugin image asset `pix/icon.png`.

### Release metadata and scope

- Updated `version.php` from numeric plugin version `2026100500` / release `1.0.2` to `2026101200` / release `1.0.3`.
- The five-commit published-tag diff changes only `.github/workflows/ci.yml`, `.github/workflows/release.yml`, `pix/icon.png`, and `version.php`. No runtime code changes or new forum-triage functions were introduced between these published tags; the minimum Moodle build remains `2024100700`.

---

These notes are grounded in Git comparisons of published releases only. They exclude later `master` commits and do not claim that tests or deployments were executed while preparing the changelog.
