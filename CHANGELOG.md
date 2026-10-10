# Changelog

## 0.2.4 — 2026-10-10

- Extend Nextcloud compatibility to versions 25–35.
- Correct the manifest element order to satisfy the App Store schema.
- Handle users without a backend and SAML data without an MFA mapping safely.
- Skip step-up only when `user_saml` explicitly reports the mapped MFA value `1`.
- Remove the unused application-configuration dependency from the login listener.
- Add isolated authentication regression tests against the Nextcloud public API.
- Package only release files, with reproducible archive metadata.
- Pin the signing image to Nextcloud 35.0.1 and track its patch updates with Renovate.
- Make signing wait for readiness and clean up its temporary container on failure.

This release has no bundled PHP or JavaScript dependencies. Composer packages are
development-only and are excluded from release archives. All 15 listener tests
pass against the Nextcloud 25.0.9 and 35.0.1 public APIs on PHP 8.4. Automated
listener tests do not replace end-to-end SSO/MFA checks on a Nextcloud deployment.
