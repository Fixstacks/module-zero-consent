<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Consent\Controller\Account;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Zero\Consent\Model\Config;
use Zero\Consent\Model\Privacy\Exporter;

/**
 * "Download my data": the signed-in customer's data as a JSON file. POST
 * with the form key (Magento's CSRF validator checks it), never cached.
 */
class Export implements HttpPostActionInterface
{
    public function __construct(
        private readonly RawFactory $rawFactory,
        private readonly RedirectFactory $redirectFactory,
        private readonly CustomerSession $customerSession,
        private readonly Exporter $exporter,
        private readonly Config $config
    ) {
    }

    public function execute()
    {
        if (!$this->config->privacyEnabled() || !$this->customerSession->isLoggedIn()) {
            return $this->redirectFactory->create()->setPath('customer/account/login');
        }
        $result = $this->rawFactory->create();
        $result->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        $result->setHeader('Content-Disposition', 'attachment; filename="' . $this->exporter->fileName() . '"', true);
        $result->setHeader('Cache-Control', 'no-store, private', true);
        $result->setContents($this->exporter->json((int) $this->customerSession->getCustomerId()));

        return $result;
    }
}
