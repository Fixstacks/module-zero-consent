<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Consent\Controller\Account;

use Magento\Customer\Model\AuthenticationInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\InvalidEmailOrPasswordException;
use Magento\Framework\Exception\State\UserLockedException;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;
use Zero\Consent\Model\Config;
use Zero\Consent\Model\Privacy\Eraser;

/**
 * "Delete my account": with the customer's password and the confirmation
 * box, they are signed out, their data is erased (Model\Privacy\Eraser) and
 * they land on the home page. A wrong password counts toward the account lock,
 * as on the sign-in form.
 */
class Delete implements HttpPostActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly RedirectFactory $redirectFactory,
        private readonly ManagerInterface $messageManager,
        private readonly CustomerSession $customerSession,
        private readonly AuthenticationInterface $authentication,
        private readonly Eraser $eraser,
        private readonly Config $config,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute()
    {
        $redirect = $this->redirectFactory->create();
        if (!$this->customerSession->isLoggedIn()) {
            return $redirect->setPath('customer/account/login');
        }
        if (!$this->config->allowsDeletion()) {
            return $redirect->setPath('privacy/account/index');
        }
        $customerId = (int) $this->customerSession->getCustomerId();
        if (!$this->request->getParam('confirm')) {
            $this->messageManager->addErrorMessage(__('Tick the box to confirm that you want to delete your account.'));

            return $redirect->setPath('privacy/account/index');
        }
        try {
            $this->authentication->authenticate($customerId, (string) $this->request->getParam('password'));
        } catch (UserLockedException $e) {
            $this->messageManager->addErrorMessage(__('Your account is locked for a while after too many wrong passwords. Try again later.'));

            return $redirect->setPath('privacy/account/index');
        } catch (InvalidEmailOrPasswordException $e) {
            $this->messageManager->addErrorMessage(__('That password is not right. Your account was not deleted.'));

            return $redirect->setPath('privacy/account/index');
        }
        // Signed out first: Magento's logout observers write to the account (customer_log).
        $this->customerSession->logout()->setLastCustomerId(null);
        $this->customerSession->regenerateId();
        try {
            $this->eraser->erase($customerId);
        } catch (\Throwable $e) {
            $this->logger->error('Zero_Consent: deleting customer ' . $customerId . ' failed: ' . $e->getMessage());
            $this->messageManager->addErrorMessage(__('We could not delete your account. Please write to us and we will do it for you.'));

            return $redirect->setPath('customer/account/login');
        }
        $this->messageManager->addSuccessMessage(__('Your account and your personal data have been deleted.'));

        return $redirect->setPath('');
    }
}
