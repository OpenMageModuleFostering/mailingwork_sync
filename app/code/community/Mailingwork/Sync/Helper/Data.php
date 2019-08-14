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

class Mailingwork_Sync_Helper_Data extends Mage_Core_Helper_Abstract
{
	const PATH_ACTIVE           = 'newsletter/mailingwork_sync/active';
    const PATH_USER             = 'newsletter/mailingwork_sync/username';
    const PATH_PASSWORD         = 'newsletter/mailingwork_sync/password';
    const PATH_MAPPING          = 'newsletter/mailingwork_sync/mapping';
    const PATH_LISTID           = 'newsletter/mailingwork_sync/list_id';
    const PATH_OPTINSETUPNEWCUSTOMER = 'newsletter/mailingwork_sync/optin_setup_new';
    const PATH_OPTINSETUPCUSTOMER = 'newsletter/mailingwork_sync/optin_setup';
    const PATH_OPTOUTSETUPWITHOUTCUSTOMER = 'newsletter/mailingwork_sync/optout_setup_without_customer';
    const PATH_OPTOUTSETUPCUSTOMER = 'newsletter/mailingwork_sync/optout_setup';

    public function isMailingworkEnabled()
    {
        return Mage::getStoreConfig(self::PATH_ACTIVE, $this->_getConfigScopeStoreId());
    }

    public function getUser()
    {
        return Mage::getStoreConfig(self::PATH_USER, $this->_getConfigScopeStoreId());
    }

    public function getPassword()
    {
        return Mage::helper('core')->decrypt(Mage::getStoreConfig(self::PATH_PASSWORD, $this->_getConfigScopeStoreId()));
    }

    public function getListId()
    {
        return Mage::getStoreConfig(self::PATH_LISTID, $this->_getConfigScopeStoreId());
    }

    public function getOptinSetupNewCustomer()
    {
        return Mage::getStoreConfig(self::PATH_OPTINSETUPNEWCUSTOMER, $this->_getConfigScopeStoreId());
    }

    public function getOptinSetupCustomer()
    {
        return Mage::getStoreConfig(self::PATH_OPTINSETUPCUSTOMER, $this->_getConfigScopeStoreId());
    }

    public function getOptoutSetupWithoutCustomer()
    {
        return Mage::getStoreConfig(self::PATH_OPTOUTSETUPWITHOUTCUSTOMER, $this->_getConfigScopeStoreId());
    }

    public function getOptoutSetupCustomer()
    {
        return Mage::getStoreConfig(self::PATH_OPTOUTSETUPCUSTOMER, $this->_getConfigScopeStoreId());
    }

    public function getMapping()
    {
        return Mage::getStoreConfig(self::PATH_MAPPING, $this->_getConfigScopeStoreId());
    }

    public function _getConfigScopeStoreId()
    {
        $storeId = Mage_Core_Model_App::ADMIN_STORE_ID;
        $storeCode = (string)Mage::getSingleton('adminhtml/config_data')->getStore();
        $websiteCode = (string)Mage::getSingleton('adminhtml/config_data')->getWebsite();
        if ('' !== $storeCode) { // store level
            try {
                $storeId = Mage::getModel('core/store')->load( $storeCode )->getId();
            } catch (Exception $ex) {  }
        } elseif ('' !== $websiteCode) { // website level
            try {
                $storeId = Mage::getModel('core/website')->load( $websiteCode )->getDefaultStore()->getId();
            } catch (Exception $ex) {  }
        }

        if (empty($storeId) && !empty(Mage::app()->getStore()->getId())) {
            $storeId = Mage::app()->getStore()->getId();
        }

        return $storeId;
    }

}