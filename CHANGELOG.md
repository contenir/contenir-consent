# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project
adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased] - 2026-10-21

### Added

- The consent configuration read from a site's settings (`ConsentConfig`, `SettingKeys`): the regional mode, the
  country header, basic or advanced Google Consent Mode, the revision, the banner's title and description, and the
  privacy policy link.
- Regional modes (`RegionalMode`): ask everyone first, ask only visitors from the EEA, the UK and Switzerland by a
  country header (`Country`), or tell visitors without asking.
- Four categories (`Category`) and their services (`Service`, `Services`), with the Tracking add-on's tags listed
  from the codes a site has set and a site's own services from its config.
- The banner and preferences on vanilla-cookieconsent 3.1.0, bundled in `assets/` with its licence (`Banner`,
  `Assets`): the wide box in the bottom right, with Accept all, Reject all and Manage preferences equally prominent;
  choices kept for 182 days; revisions that ask everyone again; Global Privacy Control recorded and keeping marketing
  off by default.
- Consent's browser script, which fires `contenir:consent` with the stored choice before any gated script runs,
  switches on the scripts each choice allows with their CSP nonce, and fires the event again for every new choice.
- Script gating (`Script\Script`, `Script\ScriptRenderer`), and Google Consent Mode v2 for sites without the Tracking
  add-on (`ConsentMode`).
- A link that opens the preferences (`PreferencesLink`).
