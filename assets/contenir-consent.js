/*!
 * contenir/contenir-consent: starts vanilla-cookieconsent with a site's settings,
 * fires contenir:consent with the visitor's choice before any gated script
 * runs, and switches on the scripts each choice allows. BSD-3-Clause.
 */
(function (window, document) {
    'use strict';

    var EVENT = 'contenir:consent';
    var COOKIE = /(?:^|;\s*)cc_cookie=([^;]*)/;

    function announce(categories) {
        window.dispatchEvent(new CustomEvent(EVENT, {
            detail: {
                analytics: categories.indexOf('analytics') !== -1,
                ads: categories.indexOf('marketing') !== -1
            }
        }));
    }

    function activate(categories) {
        var inert = [].slice.call(document.querySelectorAll('script[type="text/plain"][data-category]'));

        (function next(index) {
            for (; index < inert.length; index++) {
                var old = inert[index];
                if (!old.parentNode || categories.indexOf(old.getAttribute('data-category')) === -1) {
                    continue;
                }

                var script = document.createElement('script');
                for (var i = 0; i < old.attributes.length; i++) {
                    var name = old.attributes[i].name;
                    if (name !== 'type' && name !== 'data-category' && name !== 'nonce') {
                        script.setAttribute(name, old.attributes[i].value);
                    }
                }

                if (old.nonce) {
                    script.nonce = old.nonce;
                }

                var waits = script.hasAttribute('src');
                if (waits) {
                    script.onload = script.onerror = function () {
                        next(index + 1);
                    };
                } else {
                    script.text = old.text;
                }

                old.parentNode.replaceChild(script, old);
                if (waits) {
                    return;
                }
            }
        })(0);
    }

    function stored(revision) {
        var match = document.cookie.match(COOKIE);
        if (!match) {
            return null;
        }

        var value = null;
        try {
            value = JSON.parse(decodeURIComponent(match[1]));
        } catch (error) {
            return null;
        }

        return value && value.revision === revision && Array.isArray(value.categories) ? value.categories : null;
    }

    function pattern(name) {
        if (name.indexOf('*') === -1) {
            return name;
        }

        return new RegExp('^' + name.split('*').map(function (part) {
            return part.replace(/[.+?^${}()|[\]\\]/g, '\\$&');
        }).join('.*') + '$');
    }

    function enabled(categories) {
        return Object.keys(categories).filter(function (name) {
            return categories[name].enabled === true;
        });
    }

    window.ContenirConsent = {
        start: function (options) {
            var config = options.config;
            var cookieConsent = window.CookieConsent;
            var gpc = window.navigator.globalPrivacyControl === true;

            Object.keys(config.categories).forEach(function (name) {
                var autoClear = config.categories[name].autoClear;
                if (autoClear) {
                    autoClear.cookies = autoClear.cookies.map(function (cookie) {
                        return {name: pattern(cookie.name)};
                    });
                }
            });

            if (gpc && config.categories.marketing) {
                config.categories.marketing.enabled = false;
            }

            var choice = stored(config.revision);
            if (choice === null && config.mode === 'opt-out') {
                choice = enabled(config.categories);
            }

            if (choice !== null) {
                announce(choice);
                activate(choice);
            }

            if (!cookieConsent) {
                return;
            }

            config.onFirstConsent = config.onChange = function (event) {
                cookieConsent.setCookieData({value: {gpc: gpc}, mode: 'update'});
                announce(event.cookie.categories);
                activate(event.cookie.categories);
            };

            cookieConsent.run(config);
        }
    };
})(window, document);
