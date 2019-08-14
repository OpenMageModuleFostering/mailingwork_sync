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

class Mailingwork_Sync_Model_Resource_Subscriber extends Mage_Newsletter_Model_Resource_Subscriber
{
    /**
     * Initialize resource model
     * Get tablename from config
     *
     */
    protected function _construct()
    {
        parent::_construct();
    }

    /**
     * Load subscriber from DB by email
     *
     * @param integer $storeId
     * @return array
     */
    public function loadByStoreId($storeId = null)
    {
        if (!empty($storeId)) {
            $select = $this->_read->select()
                ->from($this->getMainTable())
                ->where('store_id=:store_id')
                ->where('subscriber_status=:subscriber_status');
            $result = $this->_read->fetchAll($select, array('store_id' => $storeId, 'subscriber_status' => Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED));
        } else {
            $select = $this->_read->select()
                ->from($this->getMainTable())
                ->where('subscriber_status=:subscriber_status');
            $result = $this->_read->fetchAll($select, array('subscriber_status' => Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED));
        }

        if (!$result) {
            return array();
        }

        return $result;
    }
}