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

class Mailingwork_Sync_Model_InitialImport
{
    /**
     * Mailingwork Api
     *
     * @var \Mailingwork\Sync\Model\MwApi
     */
    protected $mwApi;

    /**
     * Helper
     * @var \Mailingwork\Sync\Helper\Data
     */
    protected $helper;

    public function __construct() {
        $this->mwApi = new Mailingwork_Sync_Model_MwApi();
        $this->helper = new Mailingwork_Sync_Helper_Data();
    }

    public function mwInitialImport($listId) {
        $storeId = $this->helper->_getConfigScopeStoreId();
        $resourceSubscriber = new Mailingwork_Sync_Model_Resource_Subscriber();
        $arrSubscriber = $resourceSubscriber->loadByStoreId($storeId);

        if (!empty($arrSubscriber)) {
            $result = $this->mwApi->initialImport($arrSubscriber, $listId);
        } else {
            $result = array('error' => true,
                            'message' => 'no subscriber for import found'
                      );
        }

        return $result;
    }

}