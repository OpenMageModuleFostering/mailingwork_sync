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

class Mailingwork_Sync_Model_Abandonedcartcheckoutstatus extends Mage_Core_Model_Abstract
{
    protected $arrCheckoutStepMapping= array('index'           => 'Index',
                                             'billing'         => 'Shipping',
                                             'shipping'        => 'Shipping Method',
                                             'shipping_method' => 'Payment',
                                             'payment'         => 'Review',
                                             'success'         => 'Success',
                                             'failure'         => 'Failure'
                                  );

    protected $arrCheckoutSteps = array('Index'           => 0,
                                        'Shipping'        => 1,
                                        'Shipping Method' => 2,
                                        'Payment'         => 3,
                                        'Review'          => 4,
                                        'Success'         => 5,
                                        'Failure'         => 99
                                  );

    /**
     * Initialize resource model
     */
    protected function _construct()
    {
        $this->_init('mailingwork_sync/abandonedcartcheckoutstatus');
    }

    /**
     * Load by quote id
     *
     * @param int $quote_id
     */
    public function loadByQuoteId($quote_id)
    {
        $this->addData($this->getResource()->loadByQuoteId($quote_id));
        return $this;
    }

    public function updateStatus($quote_id, $status) {

        $bSave = true;
    	$this->loadByQuoteId($quote_id);

    	if (empty($this->getQuoteId())) {
    		$this->setData('quote_id', $quote_id);
        } else {
            if (!empty($this->arrCheckoutStepMapping[$status])
             && $this->arrCheckoutSteps[$this->arrCheckoutStepMapping[$status]] < $this->arrCheckoutSteps[$this->getData('status')]
            ) {
                $bSave = false;
            }
        }

        if (!empty($this->arrCheckoutStepMapping[$status])) {
            $this->setData('status', $this->arrCheckoutStepMapping[$status]);
        } else {
            $this->setData('status', $status);
        }

        if ($bSave) {
            $this->save();
        }

        return $this;
    }

}