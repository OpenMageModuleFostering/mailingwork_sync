<?php
/**
 * mailignwork GmbH
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com and you will be sent a copy immediately.
 *
 * @category   mailingwork
 * @package    Mailingwork_Sync
 * @copyright  Copyright (c) 2016 mailingwork GmbH (http://mailingwork.de)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mailingwork_Sync_Model_Subscriber extends Mage_Newsletter_Model_Subscriber
{

    protected $helper;

    /**
     * Initialize resource model
     */
    protected function _construct()
    {
        $this->helper = new Mailingwork_Sync_Helper_Data();
        parent::_construct();
    }

    /**
     * Subscribes by email
     *
     * @param string $email
     * @throws Exception
     * @return int
     */
    public function subscribe($email)
    {
        //prüfen ob Magento Plugin aktiviert ist.
        if (!$this->helper->isMailingworkEnabled()) {
            return parent::subscribe($email);
        }

        $this->loadByEmail($email);
        $customerSession = Mage::getSingleton('customer/session');

        if(!$this->getId()) {
            $this->setSubscriberConfirmCode($this->randomSequence());
        }

        $isConfirmNeed   = (Mage::getStoreConfig(self::XML_PATH_CONFIRMATION_FLAG) == 1) ? true : false;
        $isOwnSubscribes = false;
        $ownerId = Mage::getModel('customer/customer')
            ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
            ->loadByEmail($email)
            ->getId();
        $isSubscribeOwnEmail = $customerSession->isLoggedIn() && $ownerId == $customerSession->getId();

        if (!$this->getId() || $this->getStatus() == self::STATUS_UNSUBSCRIBED
            || $this->getStatus() == self::STATUS_NOT_ACTIVE
        ) {
            if ($isConfirmNeed === true) {
                // if user subscribes own login email - confirmation is not needed
                $isOwnSubscribes = $isSubscribeOwnEmail;
                if ($isOwnSubscribes == true){
                    $this->setStatus(self::STATUS_SUBSCRIBED);
                } else {
                    $this->setStatus(self::STATUS_NOT_ACTIVE);
                }
            } else {
                $this->setStatus(self::STATUS_SUBSCRIBED);
            }
            $this->setSubscriberEmail($email);
        }

        if ($isSubscribeOwnEmail) {
            $this->setStoreId($customerSession->getCustomer()->getStoreId());
            $this->setCustomerId($customerSession->getCustomerId());
        } else {
            $this->setStoreId(Mage::app()->getStore()->getId());
            $this->setCustomerId(0);
        }

        $this->setIsStatusChanged(true);

        try {
            $this->mwOptinRecipient(false);

            $this->save();
            return $this->getStatus();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Unsubscribes loaded subscription
     *
     */
    public function unsubscribe()
    {
        //prüfen ob Magento Plugin aktiviert ist.
        if (!$this->helper->isMailingworkEnabled()) {
            return parent::unsubscribe($email);
        }

        if ($this->hasCheckCode() && $this->getCode() != $this->getCheckCode()) {
            Mage::throwException(Mage::helper('newsletter')->__('Invalid subscription confirmation code.'));
        }

        if ($this->getSubscriberStatus() != self::STATUS_UNSUBSCRIBED) {
            $result = $this->mwOptoutRecipient(true);
            if (!empty($result) && empty($result['error'])) {
                $this->setSubscriberStatus(self::STATUS_UNSUBSCRIBED)->save();
            }
        }

        return $this;
    }

    /**
     * Saving customer subscription status
     *
     * @param   Mage_Customer_Model_Customer $customer
     * @return  Mage_Newsletter_Model_Subscriber
     */
    public function subscribeCustomer($customer)
    {
        //prüfen ob Magento Plugin aktiviert ist.
        if (!$this->helper->isMailingworkEnabled()) {
            return parent::subscribeCustomer($customer);
        }
        $this->loadByCustomer($customer);

        if ($customer->getImportMode()) {
            $this->setImportMode(true);
        }

        if (!$customer->getIsSubscribed() && !$this->getId()) {
            // If subscription flag not set or customer is not a subscriber
            // and no subscribe below
            return $this;
        }

        if(!$this->getId()) {
            $this->setSubscriberConfirmCode($this->randomSequence());
        }

       /*
        * Logical mismatch between customer registration confirmation code and customer password confirmation
        */
       $confirmation = null;
       if ($customer->isConfirmationRequired() && ($customer->getConfirmation() != $customer->getPassword())) {
           $confirmation = $customer->getConfirmation();
       }

        $sendInformationEmail = false;
        if ($customer->hasIsSubscribed()) {
            $status = $customer->getIsSubscribed()
                ? (!is_null($confirmation) ? self::STATUS_UNCONFIRMED : self::STATUS_SUBSCRIBED)
                : self::STATUS_UNSUBSCRIBED;
            /**
             * If subscription status has been changed then send email to the customer
             */
            if ($status != self::STATUS_UNCONFIRMED && $status != $this->getStatus()) {
                $sendInformationEmail = true;
            }
        } elseif (($this->getStatus() == self::STATUS_UNCONFIRMED) && (is_null($confirmation))) {
            $status = self::STATUS_SUBSCRIBED;
            $sendInformationEmail = true;
        } else {
            $status = ($this->getStatus() == self::STATUS_NOT_ACTIVE ? self::STATUS_UNSUBSCRIBED : $this->getStatus());
        }

        if($status != $this->getStatus()) {
            $this->setIsStatusChanged(true);
        }

        $this->setStatus($status);

        if(!$this->getId()) {
            $storeId = $customer->getStoreId();
            if ($customer->getStoreId() == 0) {
                $storeId = Mage::app()->getWebsite($customer->getWebsiteId())->getDefaultStore()->getId();
            }
            $this->setStoreId($storeId)
                ->setCustomerId($customer->getId())
                ->setEmail($customer->getEmail());
        } else {
            $this->setStoreId($customer->getStoreId())
                ->setEmail($customer->getEmail());
        }

        $sendSubscription = $customer->getData('sendSubscription') || $sendInformationEmail;
        if (is_null($sendSubscription) xor $sendSubscription) {
            if ($this->getIsStatusChanged() && $status == self::STATUS_UNSUBSCRIBED) {
                $result = $this->mwOptoutRecipient(true);
            } elseif ($this->getIsStatusChanged() && $status == self::STATUS_SUBSCRIBED) {
                $result = $this->mwOptinRecipient(true);
            }
        }

        $this->save();
        return $this;
    }

    /**
     * @return $this
     */
    public function unsubscribeMagentoOnly()
    {
        if ($this->getSubscriberStatus() != self::STATUS_UNSUBSCRIBED) {
            $this->setSubscriberStatus(self::STATUS_UNSUBSCRIBED)->save();
        }
        return $this;
    }

    protected function mwOptinRecipient($hasCustomer = false) {
        $mwApi = new Mailingwork_Sync_Model_MwApi();
        $result = $mwApi->optinRecipient($this, $hasCustomer);
        return $result;
    }

    protected function mwOptoutRecipient($hasCustomer = false) {
        $mwApi = new Mailingwork_Sync_Model_MwApi();
        $result = $mwApi->optoutRecipient($this, $hasCustomer);
        return $result;
    }

    protected function mwUpdateSubscriber() {
        $mwApi = new Mailingwork_Sync_Model_MwApi();
        $result = $mwApi->updateRecipient($this);
        return $result;
    }
}
