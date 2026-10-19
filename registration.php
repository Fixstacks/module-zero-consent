<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 *
 * Zero_Consent: the visitor's cookie choice for every tracker: a banner and
 * "Cookie Settings" (GDPR), Global Privacy Control and "Do Not Sell or Share"
 * (CCPA/CPRA). Trackers register in Model\Trackers (di.xml) and read
 * window.zeroConsent.get() / the `zero:consent` event. My Account > Privacy
 * downloads the customer's personal data and deletes the account (GDPR).
 */
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Zero_Consent', __DIR__);
