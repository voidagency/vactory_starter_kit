# Vactory Webform TypeSafe

Classify Webform submissions using the existing Drupal AI TypeSafe provider.
Supports configurable intent, sentiment, urgency, topic and request detection.
This module works independently of the Vactory profile.

## Requirements

- Drupal 10.3+ or Drupal 11; PHP 8.1+ (subject to Drupal's requirements).
- Webform 6.2+.
- Drupal AI 1.3+ (1.x) and `ai_provider_typesafe` 1.0.0-beta1 or compatible 1.x.
- A TypeSafe account and API key for live classification.
- Regular Drupal cron or a queue runner.

The provider is currently beta. The adapter uses its `system_one` operation,
`SystemOneInput`, and validated `SystemOneOutput` contract.

## Installation

From the Drupal project root:

```sh
composer require 'drupal/ai_provider_typesafe:^1.0@beta'
drush en vactory_webform_typesafe -y
```

In this project's DDEV environment, prefix commands with `ddev`.

1. Create an authentication key at `/admin/config/system/keys/add`. Prefer a
   file/environment-backed Key provider so the secret stays out of configuration.
2. Select it at `/admin/config/ai/providers/typesafe`.
3. Open `/admin/config/ai/webform-typesafe`. Set the default model (`jev-latest`),
   maximum attempts (3), and maximum selected input size (20,000 bytes).
4. On a Webform, open **Settings → Emails/Handlers → Add handler** and choose
   **TypeSafe classification**.
5. Select the fields to send and configure the questions. Enable automatic
   classification and/or classification on updates if desired. Both are off by
   default. No existing Webform is automatically opted in during installation.
6. Open **Results → Classification** to view analyses or queue older submissions.

## Question builder

Each expandable question has a machine name, label, type and instructions.
Two empty slots allow adding questions; saving supplies more slots, up to 20.
Select “Remove this question” to remove it. Configuration exports store the
validated definitions as JSON within the Webform handler settings.

- **Choice:** selects one category. Enter `key | description`, one per line.
  Provide `other` and `unclear` categories when relevant.
- **Score:** uses 2–10 ordered descriptions, lowest to highest. The returned
  score can be fractional and ranges from 0 to the number of levels minus one.
- **Noul:** evaluates a statement. Leave criteria empty. Configure the yes/no
  decision threshold and the inclusive uncertainty interval requiring review.

Choice and Score use their returned confidence, not the winning probability,
to decide whether review is required. Noul uses its statement probability and
has no separate confidence field. One uncertain answer flags the submission.
Urgency should be grounded in deadlines and consequences, independently of anger.

The starter configuration includes intent, sentiment, urgency and a callback
request. Add topic, refund request or business-specific questions as needed.
Jev classifies; this module does not generate free-text summaries or replies.

## Results and review

**Results → Classification** supports processing/review status filters and an
exact question/value filter. Use category keys, numeric scores or `yes`/`no`.
Filters use the staff correction where one exists. The list contains analysis
records; submissions without one are available through the historical action.

Each submission has a **Classification** tab with model output, probabilities,
confidence where available, and staff correction fields. Saving marks it reviewed
and records the reviewer/time. Original model output remains separate.

Reanalysis replaces previous results and corrections only on success. While
pending/failed, previous payloads remain stored but are not displayed as current.
The module stores one current record per submission, not a revision history.

Historical processing supports missing, failed, outdated or all analyses and an
optional creation-date range (UTC). It queues up to 50 submissions per batch
iteration. It does not wait for AI responses in the browser.

## Queue processing

```sh
drush queue:run vactory_webform_typesafe
```

Drupal cron also processes this queue. Transient failures use delayed exponential
retries, up to the configured maximum. Run the queue again after the delay, or let
cron pick up retries. Errors shown to staff never contain raw provider exceptions.

Items contain only a submission ID and generation token. Selected content is
read at processing time. Fingerprints cover input, questions, review thresholds
and model configuration. Unchanged completed/pending analyses are not requeued
unless explicitly requested. Superseded items are ignored. If content or settings
change before or during the request, the result is rejected as outdated.

## Permissions and data

- `administer vactory webform typesafe`: global settings.
- `view vactory webform typesafe`: results and submission analysis.
- `run vactory webform typesafe`: historical processing and reanalysis.
- `review vactory webform typesafe`: corrections and review.

Webform's existing handler-administration permissions control handler editing.
Viewing results also requires the underlying Webform/submission access. Queueing
and review actions check submission access again. Analysis entity access is
restricted too; the entity has no public create/update/delete routes.

Only selected simple text, select, radio, checkbox and numeric fields are eligible.
File contents, passwords, emails as dedicated email fields, hidden fields and
composites are excluded. Selected free text can still contain personal information.
The module does not redact arbitrary personal data. Check the site's Drupal AI
logging/event subscribers before sending sensitive text.

No submission content is copied into queue payloads or module error messages.
Deleting a submission deletes its analysis; leftover queue items become no-ops.
Uninstallation requires analysis entities to be removed first, following Drupal's
content-entity safeguards. Source submissions are not modified by this module.

## Verification

A repeatable integration suite uses a fake classifier against the real Drupal,
Webform, storage, form, queue and TypeSafe DTO code:

```sh
ddev drush php:script modules/custom/vactory_webform_typesafe/tests/smoke.php
```

Run on a development/test site. It creates synthetic forms/submissions, switches
temporarily to UID 1, and removes its fixtures and queue items in `finally`.
It does not call TypeSafe. It tests field selection, validation, filtering,
permissions, review, automatic/historical queueing, retries, deduplication,
stale response rejection and deletion cleanup.

Before enabling real automated classification, evaluate representative submissions
in the site's languages and tune questions/thresholds. A passing integration suite
does not measure the model's classification accuracy.
