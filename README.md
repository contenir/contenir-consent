# contenir/contenir-consent

[![Continuous Integration](https://github.com/contenir/contenir-consent/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/contenir/contenir-consent/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/contenir/contenir-consent/graph/badge.svg)](https://codecov.io/gh/contenir/contenir-consent)

Framework-free cookie consent for [Contenir](https://github.com/contenir) sites:

- a banner and preferences on [vanilla-cookieconsent](https://cookieconsent.orestbida.com) v3 (MIT), bundled here,
  with Accept all, Reject all and Manage preferences equally prominent;
- four categories (necessary, analytics, marketing, preferences), each listing its services, with the Tracking
  add-on's tags listed from the codes a site has set;
- regional modes: ask everyone first, ask only visitors from the EEA, the UK and Switzerland, or tell visitors without
  asking;
- script gating: anything that isn't necessary is written inert until the visitor allows its category;
- Google Consent Mode v2, and the `contenir:consent` browser event the Tracking add-on's tags listen for;
- Global Privacy Control, revisions that ask everyone again, and choices kept for 182 days.

It has no framework dependency. Sites use it through an adapter, and the CMS through an add-on:

- **Laminas MVC sites:** [contenir/contenir-consent-laminas-mvc](https://github.com/contenir/contenir-consent-laminas-mvc)
  adds the `consent()`, `consentScript()` and `consentPreferencesLink()` view helpers and serves the banner's files.
- **The CMS:** the [Consent add-on](https://github.com/contenir-extension/consent) (`contenir-extension/consent`) holds
  the settings section and the "ask everyone again" operation.

## Install

```bash
composer require contenir/contenir-consent
```

The package is named after its repository, as every Contenir library is. Before 0.1.0 it was `contenir/consent`.

## Requirements

- PHP 8.3, 8.4 or 8.5

## Usage

```php
use Contenir\Consent\Assets;
use Contenir\Consent\Banner;
use Contenir\Consent\ConsentConfig;
use Contenir\Consent\ConsentMode;
use Contenir\Consent\Country;
use Contenir\Consent\PreferencesLink;
use Contenir\Consent\Script\ScriptRenderer;

$config   = ConsentConfig::fromSettings($settings, $services); // the site's settings, as nested arrays
$renderer = new ScriptRenderer();                              // gates every script that isn't necessary
$country  = Country::fromHeader($request->getHeaderLine($config->countryHeader));

// In <head>, before any tag, on a site without the Tracking add-on (which writes its own):
echo $renderer->render([ConsentMode::script()], $nonce);

// Straight before </body>: cookieconsent and Consent's script, then the call that starts it.
echo $renderer->render((new Banner($config))->scripts('/_consent/' . Assets::scriptName(), $country), $nonce);

// Anywhere, such as the footer:
echo (new PreferencesLink($config->texts->preferencesLink))->html();
```

Serve `Assets::script()` as `Assets::scriptName()` and `Assets::stylesheet()` as `Assets::stylesheetName()`, and link
the stylesheet from `<head>`. Both names carry a hash of the files, so they can be cached for a year.

## Settings

`ConsentConfig::fromSettings()` reads these keys (`SettingKeys`). A value that is missing or can't be used falls back
to its default.

| Key | Holds | Default |
|---|---|---|
| `extensions.consent.regional_mode` | who is asked first: `opt-in`, `by-country` or `notice` | `opt-in` |
| `extensions.consent.country_header` | the request header naming the visitor's country | `CF-IPCountry` |
| `extensions.consent.google_consent_mode` | `basic` or `advanced` | `basic` |
| `extensions.consent.revision` | raised to ask everyone again | 0 |
| `extensions.consent.title` | the banner's heading | "We use cookies" |
| `extensions.consent.description` | the banner's text, as plain text | a short explanation |
| `extensions.consent.privacy_url` | the privacy policy, linked from the banner and preferences | none |

The other texts (button labels and the preferences' wording) are `Texts`' defaults, which an adapter may replace.

### Regional modes

| Mode | Who is asked first | Everyone else |
|---|---|---|
| `opt-in` | everyone | |
| `by-country` | visitors from the EU, Iceland, Liechtenstein, Norway, the UK and Switzerland, and anyone whose country isn't known | told: scripts run, and the banner offers the same choices |
| `notice` | nobody | told |

The country comes from a header the site's proxy sets, Cloudflare's `CF-IPCountry` unless the site names another.
A visitor with no header, or one Cloudflare can't place (`XX`, `T1`), is asked first. A site whose pages are cached
by a proxy must vary the cache on the header in `by-country` mode.

## Categories and services

| Category | Holds | Before a choice |
|---|---|---|
| necessary | the consent cookie, and whatever the site needs to work | always on |
| analytics | Google Analytics and Tag Manager, from the Tracking add-on's codes | off when asked first |
| marketing | Google Ads, the Meta pixel and LinkedIn Insight | off when asked first |
| preferences | embeds such as videos and maps | off when asked first |

`Services::fromSettings()` lists the consent cookie, then a service for each Tracking code the settings hold, then the
site's own, declared as `Service::fromArray()` reads them:

```php
'youtube' => [
    'name'        => 'YouTube',
    'provider'    => 'Google',
    'category'    => 'preferences',
    'cookies'     => ['YSC', 'VISITOR_INFO1_LIVE'],
    'privacy_url' => 'https://policies.google.com/privacy',
],
```

The preferences show each category with a table of its services. When a visitor refuses a category, cookieconsent
clears the cookies its services declare (`*` matches any run of characters, as in `_ga_*`).

## Gating scripts

`ScriptRenderer` writes a `Script` that isn't necessary as
`<script type="text/plain" data-category="analytics|marketing|preferences">`, which the browser doesn't run. When the
visitor allows a category, Consent's script replaces each of its scripts with a live copy, keeping the CSP nonce and
the page order, and waits for each script loaded from a URL before the next. The categories match
contenir/extension-api's `Script\ConsentCategory`, so the add-ons' scripts for public pages (P14) are gated the same
way. `new ScriptRenderer(gate: false)` writes every script to run, for a site with no banner.

## Google Consent Mode and `contenir:consent`

The banner fires `contenir:consent` on `window` with `{analytics, ads}`: `analytics` for the analytics category and
`ads` for marketing. It fires it on every page from the stored choice, before switching on any gated script, and
again whenever the visitor chooses.

On a site with the Tracking add-on, Tracking writes the Consent Mode defaults first and maps each choice to Google,
Meta and LinkedIn. On a site without it, `ConsentMode::script()` does the Google part: every signal denied before any
tag, and an update from each choice. The last choice is kept in `window.contenirConsent` either way.

In basic mode (the default) Google's tags are gated like any other script. In advanced mode the site adapter leaves
them running, so they send Google cookieless pings until the visitor allows them.

## Revisions, expiry and Global Privacy Control

- A choice, accepting or refusing, is kept in the `cc_cookie` cookie for 182 days.
- Raising the revision asks everyone again: a stored choice from another revision doesn't count, and the banner adds
  "We have changed how we use cookies since you last chose."
- A browser sending Global Privacy Control starts with marketing off, even for visitors who are only told, and the
  signal is recorded in the cookie's data with the choice (`{"gpc": true}`). A visitor can still allow marketing in
  the preferences.

## Content Security Policy

Every script carries the nonce passed to the renderer, and the scripts the banner switches on keep it, so a policy
with `'nonce-…' 'strict-dynamic'` lets them run. Inline scripts can't contain `</script`, attribute values and texts
are escaped, and event-handler attributes are dropped. The stylesheet is a file, so it needs no nonce.

## Licence

BSD-3-Clause. See [LICENSE.md](LICENSE.md). vanilla-cookieconsent is MIT-licensed, by Orest Bida: see
[assets/cookieconsent.LICENSE](assets/cookieconsent.LICENSE).
