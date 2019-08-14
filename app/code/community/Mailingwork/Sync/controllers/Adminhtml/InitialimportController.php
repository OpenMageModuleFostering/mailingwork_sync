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

class Mailingwork_Sync_Adminhtml_InitialimportController extends Mage_Adminhtml_Controller_Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    protected function _construct()
    {
        $this->setUsedModuleName('Mailingwork_Sync');
    }

    public function indexAction()
    {
        $listId = $this->getRequest()->getParam('importlist');

        if (!empty($listId)) {
            $initialImportModel = new Mailingwork_Sync_Model_InitialImport();
            $result = $initialImportModel->mwInitialImport($listId);
        } else {
            $result = array('error' => true,
                            'message' => 'Bitte Abonnentenliste für Import angeben!'
            );
        }

        $arrRes = array('valid' => (!empty($result['error']) ? 0 : 1),
                        'message' => $result['message']
        );

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($arrRes));
    }
}
