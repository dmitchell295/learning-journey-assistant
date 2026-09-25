# LJA data scripts — CBLS-61

These are the scripts used for the synthetic Moodle dataset, with fixes made for sharing. Target: Moodle 3.6.2 / PHP 7.3. The live site is already populated; uploading these files to GitHub does not require running the seed scripts again.

## Files

- `scripts/moodle/generate_lja_data.php`: five synthetic student personas, eight subjects, four assignments each, split enrolments, grades and feedback. Uses the newer Downloads generator as the base. The older project copy enrolled every student in every course and is not included.
- `scripts/moodle/setup_rubrics.php`: subject-specific criteria and selected levels for newly created rubrics. Existing rubrics are skipped entirely, including their fillings. This is not a repair tool for existing incomplete instances.
- `scripts/moodle/setup_learning_outcomes.php`: four course-linked outcome definitions per subject and their achievement scale. It does not assign student achievement.
- `scripts/moodle/export_rubrics.php`: optional local database export for the generated fixture assignments. Assumes one assignment grade attempt per student; use the live pull for general integration.
- `scripts/pull_moodle_data.py`: live REST pull for assignments, submissions, assignment grades, grade reports, rubric definitions, selected levels and outcome definitions. Uses Python standard library only.
- `moodle/local/ljaoutcomes/`: source of the installed custom read-only outcome plugin.
- `docs/LJA_API_Handoff.md`: API parameters, ID joins and live verification notes.

## Changes from the uploaded scripts

The newer generator previously deleted records outside the configured subject enrolments and replaced existing grades on reruns. Those operations have been removed. New synthetic users receive random, hashed passwords; existing users are left alone. Use Moodle's password reset process if a synthetic account needs interactive access.

The rubric script previously deleted existing definitions and called only `update()`, leaving incomplete instances invisible to the live reader. It now preserves existing rubrics and calls `submit_and_get_grade()` for new fillings. The returned calculated score is not pushed to the gradebook; the synthetic assignment grades remain separate. Assignments pending deletion and assignments outside the four named fixtures are skipped.

The Python script no longer embeds the API token or places it in the URL. It reads `LJA_TOKEN` from the environment and uses POST. `LJA_MOODLE_URL` optionally overrides the hosted endpoint. API warnings are retained. Any failed request stops the pull before writing a new output, rather than silently producing incomplete data. Grade reports cover students found in assignment grades, not all enrolled students.

Rubric scores, assignment grades and outcome scale values are different measures. The competency ordering in the fixture scripts is an explicit dataset convention, not a universal Moodle mapping or a stored student mastery result.

## Read live data (Windows PowerShell)

From the repository root:

```powershell
$secret = Read-Host 'Paste API token' -AsSecureString
$env:LJA_TOKEN = [System.Net.NetworkCredential]::new('', $secret).Password
python .\scripts\pull_moodle_data.py
Remove-Item Env:LJA_TOKEN
```

Keep `moodle_data.json` local. Do not commit tokens, credentials, user password CSVs or database backups.

## Seed a separate Moodle test copy

Back up the test database before running these administrative scripts. They create records and are not a general migration tool. Copy each PHP script into `/var/www/html/admin/cli/`, where its relative config path resolves, then run in this order:

1. `generate_lja_data.php`
2. `setup_rubrics.php`
3. `setup_learning_outcomes.php`

Example from the repository root, with your test container name:

```powershell
docker cp .\scripts\moodle\generate_lja_data.php YOUR_TEST_CONTAINER:/var/www/html/admin/cli/generate_lja_data.php
docker exec YOUR_TEST_CONTAINER php /var/www/html/admin/cli/generate_lja_data.php
```

Repeat for the next two scripts. They run as the Moodle admin CLI user. Failures are reported in console output; a final Done message alone is not proof of complete generation. Generated grades and feedback contain randomness and will not reproduce the exact hosted values on a fresh install.

The plugin is already installed on the hosted site. For another Moodle 3.6.2 installation, copy `moodle/local/ljaoutcomes` to Moodle's `local/ljaoutcomes`, complete the administrator upgrade flow, and add `local_ljaoutcomes_get_course_outcomes` to the restricted LJA service. See its README for capabilities.

## Validation and limits

Earlier hosted checks returned 32 assignments, 80 accessible rubric results and 32 outcome definitions across eight courses. The two discovery warnings for module IDs 2 and 3 correspond to activities marked for deletion. This does not validate future seed runs.

The revised files have been reviewed and the Python collector tested with simulated API responses. PHP syntax and Moodle integration must still be checked on a test copy; this workspace has no PHP/Moodle runtime. No revised seed script has been run on the live site.
