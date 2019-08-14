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

class Mailingwork_Sync_Model_MwApi
{
    protected $soapClient = null;

    /**
     * Logger
     * @var \Mailingwork\Sync\Model\Logger
     */
    protected $logger;

    /**
     * Helper
     * @var \Mailingwork\Sync\Helper\Data
     */
    protected $helper;

    public function __construct() {
        $this->logger = new Mailingwork_Sync_Model_Logger();
        $this->helper = new Mailingwork_Sync_Helper_Data();

        $webserviceUrl = Mage::getStoreConfig('mailingwork_sync/mailingwork_sync_configuration/wsdl_url');
        $webserviceTyp = Mage::getStoreConfig('mailingwork_sync/mailingwork_sync_configuration/ws_type');
        $webserviceParam = Mage::getStoreConfig('mailingwork_sync/mailingwork_sync_configuration/ws_param');
        $webserviceLocation = Mage::getStoreConfig('mailingwork_sync/mailingwork_sync_configuration/ws_location');

        $config = array('soap_version' => SOAP_1_1,'cache_wsdl'=>WSDL_CACHE_NONE);
        if (!empty($webserviceLocation)) {
            $config['location'] = $webserviceLocation;
        }

        $this->soapClient = new \SoapClient($webserviceUrl.$webserviceTyp.'?'.$webserviceParam, $config);

    }

    /**
     * Retrieves customer log model
     *
     * @param integer $customerId
     * @return \Magento\Customer\Model\Log
     */
    protected function getCustomerLogByCustomerId($customerId)
    {
        return Mage::getModel('log/customer')->loadByCustomer($customerId);
    }

    /**
     * @param integer $customerId
     * @return array
     */
    protected function getOrdersByCustomerId($customerId) {
        if ($this->isCustomerHasOrders($customerId)) {
            return $this->getCustomerOrderCollection($customerId);
        }
        return [];
    }

    /**
     * @param int $customerId
     * @param null|float $grandTotal
     *
     * @return bool
     */
    public function isCustomerHasOrders($customerId, $grandTotal = null)
    {
        $orderCollection = $this->getCustomerOrderCollection($customerId, $grandTotal);
        return (bool)$orderCollection->getSize();
    }

    /**
     * @param int  $customerId
     * @param null $grandTotal
     *
     * @return Mage_Sales_Model_Resource_Order_Collection
     */
    public function getCustomerOrderCollection($customerId, $grandTotal = null)
    {
        $orderCollection = Mage::getResourceModel('sales/order_collection')
            ->addFieldToSelect('*')
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('state', array('in' => Mage::getSingleton('sales/order_config')->getVisibleOnFrontStates()))
            ->setOrder('created_at', 'desc')
        ;
        return $orderCollection;
    }

    /**
     * @param string $email
     * @throws \Exception
     * @return integer
     */
    protected function getRecipientIdsByEmail($email) {
        $aAdvanced = array('listId' => $this->helper->getPassword());

        $result = $this->soapClient->getRecipientIdsByEmail($this->helper->getUser(),
                                                           $this->helper->getPassword(),
                                                           $email,
                                                           $this->helper->getListId()
        );

        if (empty($result['error']) && !empty($result['result'])) {
            return $result['result'];
        }

        if (!empty($result['error'])) {
                $this->logger->log_error(__METHOD__ . 'StoreId: '.$this->helper->_getConfigScopeStoreId().' ' . $result['message'] . ' (email: ' . $email . ')');
        }

        if (empty($result['result'])) {
            $this->logger->log_error(__METHOD__ . 'StoreId: '.$this->helper->_getConfigScopeStoreId(). ' empty result (email: ' . $email . ')');
        }

        return false;
    }

    /**
     * @param  $subscriber
     * @return array
     */
    protected function getFieldArrayForMw($subscriber) {
        $aFields = array();
        $sMappingFields = $this->helper->getMapping();
        if (!empty($sMappingFields)) {
            $aMappingFields = unserialize($sMappingFields);
            if (!empty($aMappingFields['subscriberdata']['email'])) {
                if (is_array($subscriber)) {
                    $aFields[$aMappingFields['subscriberdata']['email']] = $subscriber['subscriber_email'];
                } else {
                    $aFields[$aMappingFields['subscriberdata']['email']] = $subscriber->getEmail();
                }
            }
            if (!empty($aMappingFields['subscriberdata']['store_id'])) {
                if (is_array($subscriber)) {
                    $aFields[$aMappingFields['subscriberdata']['store_id']] = $subscriber['store_id'];
                } else {
                    $aFields[$aMappingFields['subscriberdata']['store_id']] = $subscriber->getStoreId();
                }
            }
            $customerId = null;
            if (is_array($subscriber)) {
                $customerId = $subscriber['customer_id'];
            } else {
                $customerId = $subscriber->getCustomerId();
            }
            if (!empty($aMappingFields['subscriberdata']['is_customer'])) {
                $aFields[$aMappingFields['subscriberdata']['is_customer']] = 0;
                if ($customerId) {
                    $aFields[$aMappingFields['subscriberdata']['is_customer']] = 1;
                }
            }
            if ($customerId) {
                $customer = Mage::getModel('customer/customer')->load($customerId);
                if (!empty($customer)) {
                    $billingAddress = $customer->getPrimaryBillingAddress();
                    $shippingAddress = $customer->getPrimaryShippingAddress();
                    $customerLog = $this->getCustomerLogByCustomerId($customer);
                    $orders = $this->getOrdersByCustomerId($customer->getId());
                    if (!empty($orders)) {
                        $countOrders = 0;
                        $allOrderAmount = 0;
                        $averageOrderAmount = 0;
                        foreach($orders as $order) {
                            if (empty($lastOrderAt) || $lastOrderAt < $order->getCreatedAt()) {
                                $lastOrderAt = $order->getCreatedAt();
                                $lastOrderAmount = $order->getGrandTotal();
                            }
                            $countOrders++;
                            $allOrderAmount += $order->getGrandTotal();
                        }
                        if ($countOrders && $allOrderAmount) {
                            $averageOrderAmount = round($allOrderAmount / $countOrders, 2);
                        }

                    }
                }
                foreach($aMappingFields as $mappingFieldGroup => $mappingFields) {
                    foreach($mappingFields as $customerField => $mwFieldId) {
                        if (!empty($mwFieldId)) {
                            switch ($mappingFieldGroup) {
                                case 'customerdata':
                                    if ($customerField == 'last_login_at') {
                                        if (!empty($customerLog)) {
                                            $aFields[$mwFieldId] = $customerLog->getLoginAt();
                                        }
                                        continue;
                                    }

                                    if ($customerField == 'group') {
                                        $aFields[$mwFieldId] = $customer->getGroupId();
                                        continue;
                                    }

                                    $tmpGetFunction = 'get' . ucfirst($customerField);
                                    if ($customer->$tmpGetFunction()) {
                                        $aFields[$mwFieldId] = $customer->$tmpGetFunction();
                                    }
                                    break;

                                case 'billingaddress':
                                    if (!empty($billingAddress)) {
                                        if ($customerField == 'country') {
                                            $aFields[$mwFieldId] = $billingAddress->getCountryId();
                                        } else {
                                            $tmpGetFunction = 'get' . ucfirst($customerField);
                                            if ($billingAddress->$tmpGetFunction()) {
                                                if (is_array($billingAddress->$tmpGetFunction())) {
                                                    $value = '';
                                                    foreach($billingAddress->$tmpGetFunction() as $tmpValue) {
                                                        $value .= (!empty($value) ? ', ' : '') . $tmpValue;
                                                    }
                                                    $aFields[$mwFieldId] = $value;
                                                } else {
                                                    $aFields[$mwFieldId] = $billingAddress->$tmpGetFunction();
                                                }
                                            }
                                        }
                                    }
                                    break;

                                case 'shippingaddress':
                                    if (!empty($shippingAddress)) {
                                        if ($customerField == 'country') {
                                            $aFields[$mwFieldId] = $shippingAddress->getCountryId();
                                        } else {
                                            $tmpGetFunction = 'get' . ucfirst($customerField);
                                            if ($shippingAddress->$tmpGetFunction()) {
                                                if (is_array($shippingAddress->$tmpGetFunction())) {
                                                    $value = '';
                                                    foreach($shippingAddress->$tmpGetFunction() as $tmpValue) {
                                                        $value .= (!empty($value) ? ', ' : '') . $tmpValue;
                                                    }
                                                    $aFields[$mwFieldId] = $value;
                                                } else {
                                                    $aFields[$mwFieldId] = $shippingAddress->$tmpGetFunction();
                                                }
                                            }
                                        }
                                    }
                                    break;

                                case 'orderdata':
                                    if ($customerField == 'count_orders') { $aFields[$mwFieldId] = (!empty($countOrders) ? $countOrders : 0); }
                                    if ($customerField == 'last_order_at') { $aFields[$mwFieldId] = (!empty($lastOrderAt) ? $lastOrderAt : ''); }
                                    if ($customerField == 'last_order_amount') { $aFields[$mwFieldId] = (!empty($lastOrderAmount) ? $lastOrderAmount : 0); }
                                    if ($customerField == 'all_order_amount') { $aFields[$mwFieldId] = (!empty($allOrderAmount) ? $allOrderAmount : 0); }
                                    if ($customerField == 'average_order_amount') { $aFields[$mwFieldId] = (!empty($averageOrderAmount) ? $averageOrderAmount : 0); }
                                    break;
                            }
                        }
                    }
                }
            }
        }
        return $aFields;
    }

    public function getFields() {
        try {
            $result = $this->soapClient->getFields($this->helper->getUser(), $this->helper->getPassword());
            if (!empty($result['error'])) {
                $this->logger->log_error(__METHOD__ .' StoreId: '.$this->helper->_getConfigScopeStoreId().' '.$result['message']);
            }
            return $result;
        } catch (SoapFault $exception) {
            $result = array('error' => 1, 'message' => $exception->getMessage());
            $this->logger->log_error(__METHOD__ .' StoreId: '.$this->helper->_getConfigScopeStoreId().' '.$exception->getMessage());
            return $result;
        }
    }

    public function getLists() {
        try {
            $result = $this->soapClient->getLists($this->helper->getUser(), $this->helper->getPassword());
            if (!empty($result['error'])) {
                $this->logger->log_error(__METHOD__ .' StoreId: '.$this->helper->_getConfigScopeStoreId().' '.$result['message']);
            }
            return $result;
        } catch (SoapFault $exception) {
            $result = array('error' => 1, 'message' => $exception->getMessage());
            $this->logger->log_error(__METHOD__ .' StoreId: '.$this->helper->_getConfigScopeStoreId().' '.$exception->getMessage());
            return $result;
        }
    }

    public function getOptinsetups() {
        try {
            $result = $this->soapClient->getOptinsetups($this->helper->getUser(), $this->helper->getPassword());
            if (!empty($result['error'])) {
                $this->logger->log_error(__METHOD__ .' StoreId: '.$this->helper->_getConfigScopeStoreId().' '.$result['message']);
            }
            return $result;
        } catch (SoapFault $exception) {
            $result = array('error' => 1, 'message' => $exception->getMessage());
            $this->logger->log_error(__METHOD__ .' StoreId: '.$this->helper->_getConfigScopeStoreId().' '.$exception->getMessage());
            return $result;
        }
    }

    public function getOptoutsetups() {
        try {
            $result = $this->soapClient->getOptoutsetups($this->helper->getUser(), $this->helper->getPassword());
            if (!empty($result['error'])) {
                $this->logger->log_error(__METHOD__ .' StoreId: '.$this->helper->_getConfigScopeStoreId().' '.$result['message']);
            }
            return $result;
        } catch (SoapFault $exception) {
            $result = array('error' => 1, 'message' => $exception->getMessage());
            $this->logger->log_error(__METHOD__ .' StoreId: '.$this->helper->_getConfigScopeStoreId().' '.$exception->getMessage());
            return $result;
        }
    }

    /**
     * @param $subscriber
     * @param boolean $hasCustomer
     * @throws \Exception
     * @return array
     */
    public function optinRecipient($subscriber, $hasCustomer = false) {

        if ($hasCustomer) {
            $optinSetupId = $this->helper->getOptinSetupCustomer();
        } else {
            $optinSetupId = $this->helper->getOptinSetupNewCustomer();
        }
        if (empty($optinSetupId)) {
            $this->logger->log_info(__METHOD__ .' StoreId: '.$this->helper->_getConfigScopeStoreId() .' No OptinSetup Id configured (hasCustomer: '.$hasCustomer.')');
            return false;
        }

        $aFields = $this->getFieldArrayForMw($subscriber);

        $aAdvanced = array('ip' => Mage::helper('core/http')->getRemoteAddr(),
                           'referer' => Mage::helper('core/http')->getHttpReferer()
        );
        $this->logger->log_debug(__METHOD__ .' OptinSetupId '.$optinSetupId.' values:'.json_encode($aFields).' advanced:'.json_encode($aAdvanced));
        $result = $this->soapClient->optinRecipient($this->helper->getUser(),
                                                    $this->helper->getPassword(),
                                                    $optinSetupId,
                                                    $aFields,
                                                    $aAdvanced
        );

        if (!empty($result['error'])) {
            $this->logger->log_error(__METHOD__ .' StoreId: '.$this->helper->_getConfigScopeStoreId().' '.$result['message']);
        }

        return $result;
    }

    /**
     * @param $subscriber
     * @param boolean $hasCustomer
     * @throws \Exception
     * @return array
     */
    public function updateRecipient($subscriber) {
        $aFields = $this->getFieldArrayForMw($subscriber);

        $recipientIds = $this->getRecipientIdsByEmail($subscriber->getEmail());

        if (!empty($recipientIds)) {
            $blError = false;
            foreach ($recipientIds as $recipientId) {
                $this->logger->log_debug(__METHOD__ .' StoreId: '.$this->helper->_getConfigScopeStoreId().' Recipient Id '.$recipientId.' values: '.json_encode($aFields));
                $result = $this->soapClient->updateRecipientById($this->helper->getUser(),
                                                                 $this->helper->getPassword(),
                                                                 $recipientId,
                                                                 $aFields
                );

                if (!empty($result['error'])) {
                    $this->logger->log_error(__METHOD__ .' StoreId: '.$this->helper->_getConfigScopeStoreId().' '.$result['message']);
                    $blError = true;
                }
            }


            return $blError;
        } else {
            $this->logger->log_info(__METHOD__ .' StoreId: '.$this->helper->_getConfigScopeStoreId().' No Recipient Id for '.$subscriber->getEmail());
            return false;
        }
    }

    /**
     * @param $subscriber
     * @param boolean $hasCustomer
     * @throws \Exception
     * @return array
     */
    public function optoutRecipient($subscriber, $hasCustomer = false) {
        if ($hasCustomer) {
            $optoutSetupId = $this->helper->getOptoutSetupCustomer();
        } else {
            //$optoutSetupId = $this->_helper->getOptoutSetupWithoutCustomer();
            $optoutSetupId = $this->helper->getOptoutSetupCustomer();
        }
        if (empty($optoutSetupId)) {
            $this->logger->log_info(__METHOD__ .' StoreId: '.$this->helper->_getConfigScopeStoreId().' No Optoutsetup Id configured (hasCustomer: '.$hasCustomer.')');
            return false;
        }
        $recipientIds = $this->getRecipientIdsByEmail($subscriber->getEmail());
        $blError = false;
        if (!empty($recipientIds)) {
            foreach ($recipientIds as $recipientId) {
                $this->logger->log_info(__METHOD__ .' StoreId: '.$this->helper->_getConfigScopeStoreId().' Optout Recipient-Id '.$recipientId);
                $result = $this->soapClient->optoutRecipientById($this->helper->getUser(),
                                                                 $this->helper->getPassword(),
                                                                 $optoutSetupId,
                                                                 $recipientId
                );
                if (!empty($result['error'])) {
                    $blError = true;
                    $this->logger->log_error(__METHOD__ .' StoreId: '.$this->helper->_getConfigScopeStoreId().' '.$result['message']);
                }
            }
        } else {
            $this->logger->log_info(__METHOD__ .' StoreId: '.$this->helper->_getConfigScopeStoreId().' No Recipient Id for '.$subscriber->getEmail());
            return false;
        }

        return $blError;
    }

    /**
     * @param array $arrSubscriber
     * @param integer $listId
     * @return array
     */
    public function initialImport($arrSubscriber, $listId) {
        if (!empty($arrSubscriber)) {
            $arrSubscriberForImport = array();
            $arrResultImport = array();
            foreach ($arrSubscriber as $subscriber) {
                $arrSubscriberForImport[] = $this->getFieldArrayForMw($subscriber);
                if (count($arrSubscriberForImport) == 2500) {
                    $arrResultImport[] = $this->importRecipients($arrSubscriberForImport, $listId);
                    $arrSubscriberForImport = array();
                }
            }
            if (!empty($arrSubscriberForImport)) {
                $arrResultImport[] = $this->importRecipients($arrSubscriberForImport, $listId);
            }
        }
        $sMessage = '';
        $bError = false;
        if (!empty($arrResultImport)) {
            foreach($arrResultImport as $arrResultTmp) {
                if (!empty($arrResultTmp['error'])) {
                    $bError = true;
                    $sMessage .= (!empty($sMessage) ? ' | ' : '') . $arrResultTmp['message'];
                }
            }
        }

        $arrResult = array('error' => $bError,
                           'message' => ($bError ? $sMessage : 'import successful ' . count($arrSubscriber) . ' subscriber imported')
                     );


        return $arrResult;
    }

    /**
     * @param array $arrSubscriber
     * @param integer $listId
     * @return array
     */
    protected function importRecipients($arrSubscriber, $listId) {
        $result = $this->soapClient->importRecipients($this->helper->getUser(),
                                                      $this->helper->getPassword(),
                                                      $listId,
                                                      $arrSubscriber,
                                                      'add'
        );

        return $result;
    }

}
