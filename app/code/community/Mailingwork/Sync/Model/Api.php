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

class Mailingwork_Sync_Model_Api extends Mage_Api_Model_Resource_Abstract
{
        public function getAbandonedCarts($secondsUntilLastUpdate)
        {

			$aReturn = array();
			$quotes = Mage::getModel('sales/quote')->getCollection();
        	/* @var $quotes Mage_Sales_Model_Mysql4_Quote_Collection */

        	//aktualisiert immer die letzten 24h
        	$quotes->addFieldToFilter('updated_at', array('to'=>date("Y-m-d H:i:s", time()-$secondsUntilLastUpdate)));
        	$quotes->addFieldToFilter('updated_at', array('from'=>date("Y-m-d H:i:s", time()-$secondsUntilLastUpdate-(24*3600))));
        	$quotes->addFieldToFilter('is_active', 1);

        	foreach($quotes as $quote) {
        		$order = null;
        		//zur Sicherheit prüfen ob es zum quote eine order gibt.
        		$order = Mage::getModel('sales/order')->loadByAttribute('quote_id');

        		if ($order->isObjectNew()) {
        			$customer = $quote->getCustomer();
        			$subscriber = Mage::getModel('newsletter/subscriber')->loadByCustomer($customer);

        			if (!empty($subscriber)
        			 && $subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED
        			) {
                        $abandonedCartCheckoutStatus = Mage::getModel('mailingwork_sync/abandonedcartcheckoutstatus')->loadByQuoteId($quote->getId());
                        $checkoutStatus = null;
                        if (!empty($abandonedCartCheckoutStatus->getQuoteId())) {
                            $checkoutStatus = $abandonedCartCheckoutStatus->getData('status');
                        }

        				$aReturn[] = array('email' => $subscriber->getEmail(),
        					 			   'quote_id' => $quote->getId(),
        					 			   'updated_at' => $quote->getUpdatedAt(),
        					 			   'store_id' => $quote->getStoreId(),
                                           'checkout_status' => $checkoutStatus
                        );
        			}
        		}
        	}

            return $aReturn;
        }

        public function unsubscribeSubscriber($email) {
            $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($email);

            if ($subscriber->getId()) {
                $subscriber->unsubscribeMagentoOnly();
                return true;
            } else {
                return false;
            }

        }

        public function getMailingworkLog() {
            $logger = new Mailingwork_Sync_Model_Logger();
            return $logger->getLast50Lines();
        }
}