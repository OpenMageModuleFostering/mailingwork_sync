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

/**
 * Used in creating options for Yes|No config value selection
 *
 */
class Mailingwork_Sync_Model_System_Config_Source_Optinsetups
{
    protected $_message = array();
    protected $mwOptinsetups = array();

    public function __construct() {
        $helper = new Mailingwork_Sync_Helper_Data();

        if ($helper->isMailingworkEnabled()) {
            if ($helper->getUser()) {
                $mwApi = new Mailingwork_Sync_Model_MwApi();
                $result = $mwApi->getOptinsetups();
                if (empty($result['error'])) {
                    $this->mwOptinsetups = $result['result'];
                }
            }
        }
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = array();
        $options[] = array('value' => '', 'label' => '');
        if (!empty($this->mwOptinsetups)) {
            foreach ($this->mwOptinsetups as $mwOptinsetup) {
                $options[] = array('value' => $mwOptinsetup['id'], 'label' => $mwOptinsetup['name'].' ('.$mwOptinsetup['id'].')');
            }
        }

        return $options;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $options = array();
        $options[''] = '';
        if (!empty($this->mwOptinsetups)) {
            foreach ($this->mwOptinsetups as $mwOptinsetup) {
                $options[$mwOptinsetup['id']] = $mwOptinsetup['name'].' ('.$mwOptinsetup['id'].')';
            }
        }

        return $options;
    }

}
