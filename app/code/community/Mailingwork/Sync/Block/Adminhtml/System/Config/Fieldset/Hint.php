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

class Mailingwork_Sync_Block_Adminhtml_System_Config_Fieldset_Hint extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    public function __construct()
    {
        $this->setTemplate('mailingwork/hint.phtml');
        parent::__construct();
    }

    /**
     * Render fieldset html
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)

    {
        return $this->toHtml();
    }
    /**
     * @return bool
     */
    public function mailingworkEnabled()
    {
        return false;
    }

    public function getVersion()
    {
        $modules = $modules = Mage::getConfig()->getNode()->modules;

        $v = "";
        if(isset($modules->Mailingwork_Sync))
        {
            $v =$modules->Mailingwork_Sync->version;
        }
        return $v;
    }
}