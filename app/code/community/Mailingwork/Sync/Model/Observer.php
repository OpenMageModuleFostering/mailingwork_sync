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

class Mailingwork_Sync_Model_Observer
{

	protected $fieldsToCheck = array('customerdata' => array('prefix', 'firstname', 'lastname', 'group_id'),
                                     'addressdata'  => array('company', 'prefix', 'firstname', 'lastname', 'street', 'postcode', 'city', 'country_id', 'telephone')
	);

	public function customer_save_after($observer) {
		//prüfen ob Magento Plugin aktiviert ist.
		$helper =  new Mailingwork_Sync_Helper_Data();
        if (!$helper->isMailingworkEnabled()) {
            return;
        }

		$bChanged = false;
		$customer = $observer->getCustomer();
		$bChanged = $this->checkCustomerForChange($customer);

		if ($bChanged) {
			$subscriber = Mage::getModel('newsletter/subscriber')->loadByCustomer($customer);
			if (!empty($subscriber)) {
				try {
					$mwApi = new Mailingwork_Sync_Model_MwApi();
					$mwApi->updateRecipient($subscriber);
				} catch (\Exception $e) {
					//update error in mailingwork, catch error to nothing to have no problems in magento
					$logger = new Mailingwork_Sync_Model_Logger();
					$logger->log_error(__METHOD__ . 'StoreId: '.$helper->_getConfigScopeStoreId().' ' . $e->getMessage());
				}
			}
		}
	}

	public function customer_address_save_after($observer) {
		//prüfen ob Magento Plugin aktiviert ist.
		$helper =  new Mailingwork_Sync_Helper_Data();
        if (!$helper->isMailingworkEnabled()) {
            return;
        }

		$bChanged = false;
		$address = $observer->getCustomerAddress();
		$customer = $address->getCustomer();

		$addressIds = $customer->getPrimaryAddressIds();
		if (!empty($addressIds) && in_array($address->getId(), $addressIds)) {
			$bChanged = $this->checkAddressForChange($address);
		}

		if ($bChanged) {
			$subscriber = Mage::getModel('newsletter/subscriber')->loadByCustomer($customer);
			if (!empty($subscriber)) {
				try {
					$mwApi = new Mailingwork_Sync_Model_MwApi();
					$mwApi->updateRecipient($subscriber);
				} catch (\Exception $e) {
					//update error in mailingwork, catch error to nothing to have no problems in magento
					$logger = new Mailingwork_Sync_Model_Logger();
					$logger->log_error(__METHOD__ . 'StoreId: '.$helper->_getConfigScopeStoreId().' ' . $e->getMessage());
				}

			}
		}
	}

	public function sales_order_save_after($observer) {
		//prüfen ob Magento Plugin aktiviert ist.
		$helper =  new Mailingwork_Sync_Helper_Data();
        if (!$helper->isMailingworkEnabled()) {
            return;
        }

		$order = $observer->getOrder();
		$customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
		$subscriber = Mage::getModel('newsletter/subscriber')->loadByCustomer($customer);
		if (!empty($subscriber)) {
			try {
				$mwApi = new Mailingwork_Sync_Model_MwApi();
				$mwApi->updateRecipient($subscriber);
			} catch (\Exception $e) {
				//update error in mailingwork, catch error to nothing to have no problems in magento
				$logger = new Mailingwork_Sync_Model_Logger();
				$logger->log_error(__METHOD__ . 'StoreId: '.$helper->_getConfigScopeStoreId().' ' . $e->getMessage());
			}
		}
	}


	protected function checkCustomerForChange($customer) {
		$bChanged = false;
		foreach ($this->fieldsToCheck['customerdata'] as $value) {
			$bChanged = $customer->dataHasChangedFor($value);
			if ($bChanged) {
				return $bChanged;
			}
		}

		return $bChanged;
	}

	protected function checkAddressForChange($address) {
		$bChanged = false;
		foreach ($this->fieldsToCheck['addressdata'] as $value) {
			$bChanged = $address->dataHasChangedFor($value);
			if ($bChanged) {
				return $bChanged;
			}
		}

		return $bChanged;
	}

}