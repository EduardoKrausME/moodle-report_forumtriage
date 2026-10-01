# Moodle Forum Triage report

`report_forumtriage` is a teacher-only course report for Moodle that helps a teacher triage forum discussions
without ranking or scoring students.

The plugin follows a deterministic-first design. Moodle/PHP decides what the current teacher is allowed to see, counts
visible posts, detects whether a teacher replied, calculates elapsed time and objective priority, and identifies long or
unanswered threads. AI is used only for semantic interpretation such as detecting a discussion that still appears
unresolved, summarising a topic, and clustering similar questions.

## Security and forum visibility

The report capability is `report/forumtriage:view`, granted by default only to teacher and editingteacher archetypes at
course context. This capability only opens the report; it does not grant access to forum content.

For every forum and discussion, the collector keeps Moodle's own access rules in the path. It uses the current user's
visible `cm_info`, `mod/forum:viewdiscussion`, `forum_get_discussions()`, `forum_user_can_see_discussion()`,
and `forum_user_can_see_post()`. As a result, separate groups, visible groups, groupings, timed discussions, deleted
posts, private replies, availability, Q&A visibility, and module capability overrides continue to be enforced
by `mod_forum`.

Teacher participation is detected with `mod/forum:editanypost` in the forum module context. This intentionally respects
custom roles and overrides instead of guessing from a role shortname.

## Deterministic triage

PHP calculates:

- visible post count and reply count;
- visible author IDs and author count;
- timestamps and last activity;
- whether the discussion has no visible reply;
- whether a teacher-like user replied;
- whether the first post looks like a question using a cheap local heuristic;
- whether the discussion exceeds the configured long-thread threshold;
- objective priority.

Priority is not an AI score. By default:

- high: a participant-started unanswered discussion is older than 24 hours, or a participant-started discussion without
  a teacher reply is older than 48 hours;
- medium: participant-started no-response/no-teacher-response discussions or long threads;
- normal: everything else.

Thresholds are configurable in Site administration > Reports > Forum triage settings.

## AI minimisation

Only discussions selected as semantic candidates are sent to AI Bridge, capped by the configured maximum. The request
does not include full names or Moodle user IDs. Authors are replaced with request-local pseudonyms such as `U1`
and `T1`. Only the first post plus up to seven recent visible replies are sent, with each post converted to plain text
and truncated.

The model is asked for strict JSON. Returned discussion IDs are validated against the exact allowlist sent in the
request. Invented IDs are discarded. AI priority is never authoritative: the final priority is always the value
calculated by PHP.

The plugin does not persist prompts, raw AI responses, or analyses. Its Privacy API provider is therefore
a `null_provider`.

## Report sections

The course report provides:

- Needs attention;
- No response;
- Possibly unresolved;
- Recurring questions;
- Most discussed topics.

Filters include forum, visible group, activity period, only discussions without a teacher reply, and only recent
questions. Every result links directly to the Moodle discussion.
