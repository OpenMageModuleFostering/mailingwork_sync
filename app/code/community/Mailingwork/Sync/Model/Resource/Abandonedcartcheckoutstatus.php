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

class Mailingwork_Sync_Model_Resource_Abandonedcartcheckoutstatus extends Mage_Core_Model_Resource_Db_Abstract
{

	/**
     * DB read connection
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_read;


	protected function _construct() {
		$this->_init('mailingwork_sync/abandonedcartcheckoutstatus', 'quote_id');
        $this->_isPkAutoIncrement = false;
		$this->_read = $this->_getReadAdapter();
	}

    /**
     * Load by quote_id
     *
     * @param int $quote_id
     * @return array
     */
    public function loadByQuoteId($quote_id)
    {
        $select = $this->_read->select()
            ->from($this->getMainTable())
            ->where('quote_id=:quote_id');

        $result = $this->_read->fetchRow($select, array('quote_id'=>$quote_id));

        if (!$result) {
            return array();
        }

        return $result;
    }
}