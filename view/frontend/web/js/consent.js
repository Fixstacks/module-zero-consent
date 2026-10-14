/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 *
 * Zero Consent: the visitor's analytics/marketing cookie choice. Settings from
 * #zero-consent-settings. window.zeroConsent.get() → { analytics, marketing,
 * decided, gpc }: the banner's choice (localStorage); without one, off while
 * the store asks for consent; GPC turns marketing off unless the visitor
 * turned it on. A choice dispatches `zero:consent`. Loaded before trackers.
 */
(function () {
    'use strict';

    var KEY = 'zero-consent-v1';
    var settingsEl = document.getElementById('zero-consent-settings');
    var settings = settingsEl ? JSON.parse(settingsEl.textContent) : { ask: true, gpc: true };
    var gpc = !!settings.gpc && navigator.globalPrivacyControl === true;

    function stored(value) {
        try {
            if (value === undefined) {
                return JSON.parse(localStorage.getItem(KEY) || 'null');
            }
            localStorage.setItem(KEY, JSON.stringify(value));
        } catch (e) {
            // No storage (private mode): the choice lasts for this page only.
        }
        return null;
    }

    var choice = stored();

    function get() {
        var on = choice ? choice : { analytics: !settings.ask, marketing: !settings.ask };
        var marketing = !!on.marketing;
        if (gpc && !(choice && choice.gpc && choice.marketing)) {
            marketing = false;
        }
        return { analytics: !!on.analytics, marketing: marketing, decided: !!choice || !settings.ask, gpc: gpc };
    }

    var banner = document.querySelector('[data-zero-consent]');

    function show(customise) {
        if (!banner) {
            return;
        }
        var current = get();
        banner.querySelectorAll('[data-zero-consent-option]').forEach(function (box) {
            box.checked = !!current[box.getAttribute('data-zero-consent-option')];
        });
        banner.querySelector('[data-zero-consent-choices]').hidden = !customise;
        banner.querySelector('[data-zero-consent-action="save"]').hidden = !customise;
        banner.querySelector('[data-zero-consent-action="choose"]').hidden = !!customise;
        banner.querySelector('[data-zero-consent-gpc]').hidden = !gpc;
        banner.hidden = false;
    }

    function decide(next) {
        choice = { analytics: !!next.analytics, marketing: !!next.marketing, gpc: gpc, at: Date.now() };
        stored(choice);
        if (banner) {
            banner.hidden = true;
        }
        document.dispatchEvent(new CustomEvent('zero:consent', { detail: get() }));
    }

    window.zeroConsent = { get: get, open: function () { show(true); } };

    if (banner) {
        banner.addEventListener('click', function (event) {
            var button = event.target.closest('[data-zero-consent-action]');
            if (!button) {
                return;
            }
            var action = button.getAttribute('data-zero-consent-action');
            if (action === 'accept') {
                decide({ analytics: true, marketing: true });
            } else if (action === 'reject') {
                decide({ analytics: false, marketing: false });
            } else if (action === 'choose') {
                show(true);
            } else if (action === 'save') {
                var next = {};
                banner.querySelectorAll('[data-zero-consent-option]').forEach(function (box) {
                    next[box.getAttribute('data-zero-consent-option')] = box.checked;
                });
                decide(next);
            }
        });
    }
    document.addEventListener('click', function (event) {
        var opener = event.target.closest ? event.target.closest('[data-zero-consent-open]') : null;
        if (opener) {
            event.preventDefault();
            show(true);
        }
    });

    function start() {
        if (settings.ask && !choice) {
            show(false);
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
