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
 * Synchronize button renderer
 */
class Mailingwork_Sync_Block_Adminhtml_System_Config_Initialimport
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * @var string
     */
    protected $_importList = 'newsletter_mailingwork_syncimport_initialimport_list';

    /**
     * Initialimport Button Label
     *
     * @var string
     */
    protected $_initButtonLabel = "Initiale Datenübertragung (Kundendaten) zu Mailingwork starten";

    /*
     * Set template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('mailingwork/initialimport.phtml');
    }


    public function getStoreId() {
        $helper = new Mailingwork_Sync_Helper_Data();
        return $helper->_getConfigScopeStoreId();
    }

    /**
     * Get Importlist Field Name
     *
     * @return string
     */
    public function getImportListField() {
        return $this->_importList;
    }

    /**
     * Remove scope label
     *
     * @param  $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        //$element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Set Validate VAT Button Label
     *
     * @param string $initButtonLabel
     * @return Mailingwork\Sync\Block\Adminhtml\System\Config\InitialImport
     */
    public function setInitButtonLabel($initButtonLabel)
    {
        $this->_initButtonLabel = $initButtonLabel;
        return $this;
    }

    /**
     * Get the button and scripts contents
     *
     * @param $element
     * @return string
     */
    protected function _getElementHtml($element)
    {
        $originalData = $element->getOriginalData();
        $buttonLabel = !empty($originalData['button_label']) ? $originalData['button_label'] : $this->_initButtonLabel;
        $this->addData(
            [
                'button_label' => __($buttonLabel),
                'html_id' => $element->getHtmlId(),
                'ajax_url' => Mage::helper('adminhtml')->getUrl('adminhtml/initialimport/index'),
            ]
        );

        return $this->_toHtml();
    }

    /**
     * Generate synchronize button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        //$originalData = $element->getOriginalData();
        $buttonLabel = !empty($originalData['button_label']) ? $originalData['button_label'] : $this->_initButtonLabel;
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(array(
                'id'        => 'mailingworkinitialimport',
                'label'     => $this->helper('adminhtml')->__($buttonLabel),
                'onclick'   => 'javascript:startInitialImport(); return false;'
            ));

        return $button->toHtml();
    }
}
