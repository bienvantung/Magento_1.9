<?php
/**
 * Created by PhpStorm.
 * User: Tungbv
 * Date: 29/07/2014
 * Time: 15:21
 */
require_once 'Mage/Customer/controllers/AccountController.php';
class Smartosc_ProductWishlist_AccountController extends Mage_Customer_AccountController
{


    /**
     * Change customer information action
     */

    const XML_PATH_FORGOT_EMAIL_TEMPLATE = 'customer/password/forgot_email_template';

    public function editInformAction()
    {

        if (!$this->_validateFormKey()) {
            return $this->_redirect('*/*/edit');
        }

        if ($this->getRequest()->isPost()) {
            /** @var $customer Mage_Customer_Model_Customer */
            $customer = $this->_getSession()->getCustomer();

            /** @var $customerForm Mage_Customer_Model_Form */
            $customerForm = $this->_getModel('customer/form');
            $customerForm->setFormCode('customer_account_edit')
                ->setEntity($customer);

            $customerData = $customerForm->extractData($this->getRequest());

            $errors = array();
            $customerErrors = $customerForm->validateData($customerData);
            if ($customerErrors !== true) {
                $errors = array_merge($customerErrors, $errors);
            } else {
                $customerForm->compactData($customerData);
                $errors = array();


                // Validate account and compose list of errors if any
                $customerErrors = $customer->validate();
                if (is_array($customerErrors)) {
                    $errors = array_merge($errors, $customerErrors);
                }
            }

            if (!empty($errors)) {
                $this->_getSession()->setCustomerFormData($this->getRequest()->getPost());
                foreach ($errors as $message) {
                    $this->_getSession()->addError($message);
                }
                $this->_redirect('*/*/edit');
                return $this;
            }

            try {
                $customer->setConfirmation(null);
                $customer->save();
                // Set session in updated
                Mage::getSingleton('customer/session')->setUpdate('info');
                $this->_getSession()->setCustomer($customer)
                    ->addSuccess($this->__('Basic Info Updated!'));

                $this->_redirect('customer/account');
                return;
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->setCustomerFormData($this->getRequest()->getPost())
                    ->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->setCustomerFormData($this->getRequest()->getPost())
                    ->addException($e, $this->__('Cannot save the customer.'));
            }
        }

        $this->_redirect('*/*/edit');
    }


    /**
     * Change customer password action
     */
    public function editPasswordAction()
    {
        Mage::getSingleton('customer/session')->setUpdate('password');
        if ($this->getRequest()->isPost()) {
            /** @var $customer Mage_Customer_Model_Customer */
            $customer = $this->_getSession()->getCustomer();

            /** @var $customerForm Mage_Customer_Model_Form */
            $customerForm = $this->_getModel('customer/form');
            $customerForm->setFormCode('customer_account_edit')
                ->setEntity($customer);

            $customerData = $customerForm->extractData($this->getRequest());

            $errors = array();
            $customerErrors = $customerForm->validateData($customerData);
            if ($customerErrors !== true) {
                $errors = array_merge($customerErrors, $errors);
            } else {
                $customerForm->compactData($customerData);
                $errors = array();

                // If password change was requested then add it to common validation scheme
//                var_dump($this->getRequest()); die("dfdf");
//                if ($this->getRequest()->getParam('change_password')) {
                $currPass   = $this->getRequest()->getPost('current_password');
                $newPass    = $this->getRequest()->getPost('password');
                $confPass   = $this->getRequest()->getPost('password');
                $oldPass = $this->_getSession()->getCustomer()->getPasswordHash();
                if ( $this->_getHelper('core/string')->strpos($oldPass, ':')) {
                    list($_salt, $salt) = explode(':', $oldPass);
                } else {
                    $salt = false;
                }

                if ($customer->hashPassword($currPass, $salt) == $oldPass) {
                    if (strlen($newPass)) {
                        /**
                         * Set entered password and its confirmation - they
                         * will be validated later to match each other and be of right length
                         */
                        $customer->setPassword($newPass);
                        $customer->setConfirmation($confPass);
                    } else {
                        $errors[] = $this->__('New password field cannot be empty.');
                    }
                } else {
                    $errors[] = $this->__('Invalid current password');
                }
//                }
                // Validate account and compose list of errors if any
                $customerErrors = $customer->validate();
                if (is_array($customerErrors)) {
                    $errors = array_merge($errors, $customerErrors);
                }
            }

            if (!empty($errors)) {
                $this->_getSession()->setCustomerFormData($this->getRequest()->getPost());
                foreach ($errors as $message) {
                    $this->_getSession()->addError($message);
                }
                $this->_redirect('*/*/edit');
                return $this;
            }
            try {
                $customer->setConfirmation(null);
                $customer->save();
                // Set session in updated
                Mage::getSingleton('customer/session')->setUpdate('password');
                $this->_getSession()->setCustomer($customer)
                    ->addSuccess($this->__('Password Updated!'));

                $this->_redirect('customer/account');
                return;
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->setCustomerFormData($this->getRequest()->getPost())
                    ->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->setCustomerFormData($this->getRequest()->getPost())
                    ->addException($e, $this->__('Cannot save the customer.'));
            }
        }

        $this->_redirect('*/*/edit');
    }


    /**
     * Create customer account action
     */
    public function createaccountAction()
    {

        $_request = $this->getRequest()->getPost();
        $_response = array();

        if($_request['ajax'] != 'register'){
            return $_response['error'] = $this->__('Invalid action!');
        }

        $customer = $this->_getCustomer();


        $customer->setWebsiteId(Mage::app()->getWebsite()->getId());

        $customer->setEmail($_request['email']);
        $customer->setPassword($_request['password']);
        $customer->setFirstname($_request['firstname']);
        $customer->setLastname($_request['lastname']);

        try
        {
            //turn off send email when register
            $customer->save();
            $storeId = $customer->getSendemailStoreId();
//            $customer->sendNewAccountEmail('registered', '', $storeId);
            $session = $this->_getSession();
            if ($customer->isConfirmationRequired()) {
                /** @var $app Mage_Core_Model_App */
                $app = $this->_getApp();
                /** @var $store  Mage_Core_Model_Store*/
                $store = $app->getStore();
//                $customer->sendNewAccountEmail(
//                    'confirmation',
//                    $session->getBeforeAuthUrl(),
//                    $store->getId()
//                );
                $customerHelper = $this->_getHelper('customer');
                $_successMess = $this->__('Account confirmation is required. Please, check your email for the confirmation link. To resend the confirmation email please <a href="%s">click here</a>.'.
                    $customerHelper->getEmailConfirmationUrl($customer->getEmail()));
                $_response['success'] = $_successMess;
            }else{
                $session->setCustomerAsLoggedIn($customer);
                $session->renewSession();
                $_successMess = '<p>You can now save address, create a list of the thing you love, and more!</p>
                            <p>You\'ll receive a confirmation email at <span class="popup-email"><strong>'.$customer->getEmail().'</strong></span> soon!</p>';

                $_response['success'] = $_successMess;
            }

        }catch (Exception $ex)
        {
            //Zend_Debug::dump($ex->getMessage());
            //throw new Exception($ex->getMessage());
            $_response['error'] = $this->__('Unable to create account! '.$ex->getMessage());

        }
        $this->getResponse()->setHeader('Content-Type', 'text/html; charset=UTF-8', true)->setBody(Mage::helper('core')->jsonEncode($_response));
        return;

    }


    /**
     * Add welcome message and send new account email.
     * Returns success URL
     *
     * @param Mage_Customer_Model_Customer $customer
     * @param bool $isJustConfirmed
     * @return string
     */
    protected function _welcomeCustomer(Mage_Customer_Model_Customer $customer, $isJustConfirmed = false)
    {
        $this->_getSession()->addSuccess(
            $this->__('Thank you for registering with %s.', Mage::app()->getStore()->getFrontendName())
        );
        if ($this->_isVatValidationEnabled()) {
            // Show corresponding VAT message to customer
            $configAddressType =  $this->_getHelper('customer/address')->getTaxCalculationAddressType();
            $userPrompt = '';
            switch ($configAddressType) {
                case Mage_Customer_Model_Address_Abstract::TYPE_SHIPPING:
                    $userPrompt = $this->__('If you are a registered VAT customer, please click <a href="%s">here</a> to enter you shipping address for proper VAT calculation',
                        $this->_getUrl('customer/address/edit'));
                    break;
                default:
                    $userPrompt = $this->__('If you are a registered VAT customer, please click <a href="%s">here</a> to enter you billing address for proper VAT calculation',
                        $this->_getUrl('customer/address/edit'));
            }
            $this->_getSession()->addSuccess($userPrompt);
        }

//        $customer->sendNewAccountEmail(
//            $isJustConfirmed ? 'confirmed' : 'registered',
//            '',
//            Mage::app()->getStore()->getId()
//        );

        $successUrl = $this->_getUrl('customer/account/login', array('_secure' => true));
//        if ($this->_getSession()->getBeforeAuthUrl()) {
//            $successUrl = $this->_getSession()->getBeforeAuthUrl(true);
//        }

        return $successUrl;
    }


    public function resetPasswordPostAction()
    {
        $resetPasswordLinkToken = (string) $this->getRequest()->getQuery('token');
        $customerId = (int) $this->getRequest()->getQuery('id');
        $password = (string) $this->getRequest()->getPost('password');
        $passwordConfirmation = (string) $this->getRequest()->getPost('confirmation');

        try {
            $this->_validateResetPasswordLinkToken($customerId, $resetPasswordLinkToken);
        } catch (Exception $exception) {
            $this->_getSession()->addError( $this->_getHelper('customer')->__('Your password reset link has expired.'));
            $this->_redirect('*/*/');
            return;
        }

        $errorMessages = array();
        if (iconv_strlen($password) <= 0) {
            array_push($errorMessages, $this->_getHelper('customer')->__('New password field cannot be empty.'));
        }
        /** @var $customer Mage_Customer_Model_Customer */
        $customer = $this->_getModel('customer/customer')->load($customerId);

        $customer->setPassword($password);
        $customer->setConfirmation($passwordConfirmation);
        $validationErrorMessages = $customer->validate();
        if (is_array($validationErrorMessages)) {
            $errorMessages = array_merge($errorMessages, $validationErrorMessages);
        }

        if (!empty($errorMessages)) {
            $this->_getSession()->setCustomerFormData($this->getRequest()->getPost());
            foreach ($errorMessages as $errorMessage) {
                $this->_getSession()->addError($errorMessage);
            }
            $this->_redirect('*/*/resetpassword', array(
                'id' => $customerId,
                'token' => $resetPasswordLinkToken
            ));
            return;
        }

        try {
            // Empty current reset password token i.e. invalidate it
            $customer->setRpToken(null);
            $customer->setRpTokenCreatedAt(null);
            $customer->setConfirmation(null);
            $customer->save();


            $templateId = 3;

            // Set sender information
            $senderName = Mage::getStoreConfig('trans_email/ident_support/name');
            $senderEmail = Mage::getStoreConfig('trans_email/ident_support/email');
            $sender = array('name' => $senderName,
                'email' => $senderEmail);

            // Set recepient information
            $recepientEmail = $customer->getEmail();
            $recepientName = $customer->getName();

            // Get Store ID
            $store = Mage::app()->getStore()->getId();

            // Set variables that can be used in email template
            $vars = array('customerName' => $customer->getName(),
                'customerPassword' => $passwordConfirmation);

            $translate  = Mage::getSingleton('core/translate');

            // Send Transactional Email
            Mage::getModel('core/email_template')
                ->sendTransactional($templateId, $sender, $recepientEmail, $recepientName, $vars, $storeId);

            $translate->setTranslateInline(true);



            $this->_getSession()->addSuccess( $this->_getHelper('customer')->__('Your password has been updated.'));
            $this->_redirect('*/*/login');
        } catch (Exception $exception) {
            $this->_getSession()->addException($exception, $this->__('Cannot save a new password.'));
            $this->_redirect('*/*/resetpassword', array(
                'id' => $customerId,
                'token' => $resetPasswordLinkToken
            ));
            return;
        }
    }


    public function forgotPasswordPostAction()
    {
        $email = (string) $this->getRequest()->getPost('email');
        if ($email) {
            if (!Zend_Validate::is($email, 'EmailAddress')) {
                $this->_getSession()->setForgottenEmail($email);
                $this->_getSession()->addError($this->__('Invalid email address.'));
                $this->_redirect('*/*/');
                return;
            }

            /** @var $customer Mage_Customer_Model_Customer */
            $customer = $this->_getModel('customer/customer')
                ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                ->loadByEmail($email);


            if ($customer->getId()) {
                try {
                    $newPassword = $customer->generatePassword();
                    $customer->changePassword($newPassword, false);

                    // Set sender information
                    $storeId =  Mage::app()->getStore()->getStoreId();
                    $emailTemplate = Mage::getStoreConfig(self::XML_PATH_FORGOT_EMAIL_TEMPLATE);
                    $senderName = Mage::getStoreConfig('trans_email/ident_support/name');
                    $senderEmail = Mage::getStoreConfig('trans_email/ident_support/email');
                    $sender = array('name' => $senderName,
                        'email' => $senderEmail);

                    // Set recepient information
                    $recepientEmail = $customer->getEmail();
                    $recepientName = $customer->getName();

                    // Get Store ID
                    $store = Mage::app()->getStore()->getId();

                    // Set variables that can be used in email template
                    $vars = array('customerName' => $customer->getName(),
                        'customerPassword' => $newPassword);

                    $translate  = Mage::getSingleton('core/translate');

                    // Send Transactional Email

                    Mage::getModel('core/email_template')
                        ->sendTransactional($emailTemplate, $sender, $recepientEmail, $recepientName, $vars, $storeId);

                    $translate->setTranslateInline(true);

                    //$customer->sendPasswordReminderEmail();

                    $result = array('success'=>true);

                }
                catch (Exception $e){
                    $result = array('success'=>false, 'error'=>$e->getMessage());
                }
            }
            else{
                $this->_getSession()->addError($this->__('Please enter a registered email address.'));
                $this->_redirect('*/*/');
                return;
            }


            $this->_getSession()
                ->addSuccess( $this->_getHelper('customer')
                    ->__('We have now sent you a new password to your email address.',
                        $this->_getHelper('customer')->escapeHtml($email)));
            $this->_redirect('*/*/');
            return;
        } else {
            $this->_getSession()->addError($this->__('Please enter your email.'));
            $this->_redirect('*/*/');
            return;
        }
    }

    /**
     * Login post action
     */
    public function loginPostAction()
    {
        $wishlistUrl = '';
        $currentUrl = Mage::helper('core/url')->getCurrentUrl();
        if(strpos($currentUrl, 'wishlist') > 0){
            $wishlistUrl = Mage::getSingleton('core/session')->getUrlWishlist();
        }

        if ($this->_getSession()->isLoggedIn()) {
            $this->_redirect('*/*/');
            return;
        }
        $session = $this->_getSession();

        if ($this->getRequest()->isPost()) {
            $login = $this->getRequest()->getPost('login');
            if (!empty($login['username']) && !empty($login['password'])) {
                try {
                    $session->login($login['username'], $login['password']);
                    if ($session->getCustomer()->getIsJustConfirmed()) {
                        $this->_welcomeCustomer($session->getCustomer(), true);
                    }
                    $this->addToWishList();
                } catch (Mage_Core_Exception $e) {
                    switch ($e->getCode()) {
                        case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED:
                            $value = $this->_getHelper('customer')->getEmailConfirmationUrl($login['username']);
                            $message = $this->_getHelper('customer')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.', $value);
                            break;
                        case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
                            $message = $e->getMessage();
                            break;
                        default:
                            $message = $e->getMessage();
                    }
                    $session->addError($message);
                    $session->setUsername($login['username']);
                } catch (Exception $e) {
                    // Mage::logException($e); // PA DSS violation: this exception log can disclose customer password
                }
            } else {
                $session->addError($this->__('Login and password are required.'));
            }
        }
        if($wishlistUrl != ''){
            $this->_redirectUrl($wishlistUrl);
        } else {
            $this->_loginPostRedirect();
        }

    }

    public function addToWishList(){
        if(Mage::getSingleton('core/session')->getWishListGuest())
        {
            $productIds =  explode(',',Mage::getSingleton('core/session')->getWishListGuest());

            foreach($productIds as $productId)
            {
                $product = Mage::getModel('catalog/product')->load($productId);
                $wishlist = Mage::helper('wishlist')->getWishlist();
                $wishlist->addNewItem($product);
            }
            Mage::getSingleton('core/session')->unsWishListGuest();
        }
    }

    /**
     * Define target URL and redirect customer after logging in
     */
    protected function _loginPostRedirect()
    {
        $session = $this->_getSession();

        if (!$session->getBeforeAuthUrl() || $session->getBeforeAuthUrl() == Mage::getBaseUrl()) {
            // Set default URL to redirect customer to
            $session->setBeforeAuthUrl($this->_getHelper('customer')->getAccountUrl());
            // Redirect customer to the last page visited after logging in
            if ($session->isLoggedIn()) {
                if (!Mage::getStoreConfigFlag(
                    Mage_Customer_Helper_Data::XML_PATH_CUSTOMER_STARTUP_REDIRECT_TO_DASHBOARD
                )) {
                    $referer = $this->getRequest()->getParam(Mage_Customer_Helper_Data::REFERER_QUERY_PARAM_NAME);
                    if ($referer) {
                        // Rebuild referer URL to handle the case when SID was changed
                        $referer = $this->_getModel('core/url')
                            ->getRebuiltUrl( $this->_getHelper('core')->urlDecode($referer));
                        if ($this->_isUrlInternal($referer)) {
                            $session->setBeforeAuthUrl($referer);
                        }
                    }
                } else if ($session->getAfterAuthUrl()) {
                    $session->setBeforeAuthUrl($session->getAfterAuthUrl(true));
                }
            } else {
                $session->setBeforeAuthUrl( $this->_getHelper('customer')->getLoginUrl());
            }
        } else if ($session->getBeforeAuthUrl() ==  $this->_getHelper('customer')->getLogoutUrl()) {
            $session->setBeforeAuthUrl( $this->_getHelper('customer')->getDashboardUrl());
        } else {
            if (!$session->getAfterAuthUrl()) {
                if(!strpos($session->getBeforeAuthUrl(), 'xproductalert')){
                    $session->setAfterAuthUrl($session->getBeforeAuthUrl());
                }
                //$session->setAfterAuthUrl($session->getBeforeAuthUrl());
            }
            if ($session->isLoggedIn()) {
                $session->setBeforeAuthUrl($session->getAfterAuthUrl(true));
            }
        }

        // remmember me
        if ($this->getRequest()->isPost()) {
            $login = $this->getRequest()->getPost('login');
            $rememberme = $this->getRequest()->getPost('rememberme');
            try {
                $cookie = Mage::getModel('core/cookie');
                if (!empty($login['username']) && !empty($login['password']) && !empty($rememberme)) {
                    $cookie->set('user_name', $login['username']);
                    $cookie->set('pass_user_name', $login['password']);
                    $cookie->set('rememberme', 1);

                } else if (!empty($login['username']) && !empty($login['password']) && empty($rememberme)) {
                    $cookie->delete('user_name');
                    $cookie->delete('pass_user_name');
                    $cookie->delete('rememberme');
                }
            } catch (Exception $e) {

            }
        }
        $this->_redirectUrl($session->getBeforeAuthUrl(true));
        //$this->_redirectUrl($session->getBeforeAuthUrl(true));
    }


}