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

class Mailingwork_Sync_Block_Adminhtml_System_Config_Form_Field_Customermap
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{

    protected $_message = array();
    protected $mwFields = null;

    protected $magentoCustomerFields = array('subscriberdata'   => array('email', 'store_id', 'is_customer'),
                                             'customerdata'     => array('prefix', 'firstname', 'lastname', 'last_login_at', 'group'),
                                             'billingaddress'   => array('company', 'prefix', 'firstname', 'lastname',
                                                                         'street', 'postcode', 'city', 'country', 'telephone'),
                                             'shippingaddress'  => array('company', 'prefix', 'firstname', 'lastname',
                                                                         'street', 'postcode', 'city', 'country', 'telephone'),
                                             'orderdata'        => array('count_orders', 'last_order_at', 'last_order_amount',
                                                                         'all_order_amount', 'average_order_amount', 'last_abandoned_cart')
                                        );

    public function __construct()
    {
        $this->setTemplate('mailingwork/customermap.phtml');


        $this->addColumn('magento', array(
            'label' => Mage::helper('adminhtml')->__('Magento'),
            'style' => 'width:200px;',
        ));
        $this->addColumn('mailingwork', array(
            'label' => Mage::helper('adminhtml')->__('Mailingwork'),
            'style' => 'width:200px;',
        ));
        $this->_addAfter = false;

        $helper = new Mailingwork_Sync_Helper_Data();

        if ($helper->isMailingworkEnabled()) {
            if ($helper->getUser()) {
                $mwApi = new Mailingwork_Sync_Model_MwApi();
                $result = $mwApi->getFields();
                if (!empty($result['error'])) {
                    $this->_message = array('type' => 'error',
                                            'message' => __($result['message'])
                    );
                 } else {
                    $this->mwFields = $result['result'];
                 }
            } else {
                $this->_message = array('type' => 'error',
                                        'message' => __('Kein User und Passwort hinterlegt!')
                );
            }
        }

        parent::__construct();

    }

    public function getFieldsForMapping() {
        return $this->magentoCustomerFields;
    }

    /**
     * Render array cell for prototypeJS template
     *
     * @param string $columnName
     * @return string
     * @throws \Exception
     */
    public function renderMwCellTemplate($columnName, $inputName, $inputGroup)
    {
        if (empty($this->_columns[$columnName])) {
            throw new \Exception('Wrong column name specified.');
        }
        $column = $this->_columns[$columnName];
        $inputNameTmp = $this->_getCellInputElementNameByGroup($inputName, $inputGroup);

        if ($column['renderer']) {
            return $column['renderer']->setInputName($inputNameTmp)->setColumnName($columnName)->setColumn($column)->toHtml();
        }

        $field = '';
        $field .= '<select name="' . $inputNameTmp .
            ($column['size'] ? 'size="' . $column['size'] . '"' : '') .
            ' class="' . (isset($column['class'])
                            ? $column['class']
                            : 'input-text') .
            '"' . (isset($column['style']) ? ' style="' . $column['style'] . '"' : '') .
            '>';
        $field .= '<option value=""> </option>';

        if (!empty($this->mwFields)
         && is_array($this->mwFields)
        ) {
            foreach($this->mwFields as $mwField) {
                $field .= '<option value="'.$mwField['id'] .'"';
                if ($mwField['id'] == $this->getValueByInputName($inputName, $inputGroup)) {
                    $field .= ' selected="selected"';
                }
                $field .= '>'.$mwField['name'].'</option>';
            }

        }
        $field .= '</select>';

        return $field;
    }

    /**
     * Get id for cell element
     *
     * @param string $columnName
     * @return string
     */
    protected function _getCellInputElementNameByGroup($inputName, $inputGroup)
    {
        return $this->getElement()->getName() . '[' . $inputGroup . '][' . $inputName . ']';
    }

    protected function getValueByInputName($inputName, $inputGroup) {
        $element = $this->getElement();
        $values = $element->getValue();
        if (!empty($values[$inputGroup][$inputName])) {
            return $values[$inputGroup][$inputName];
        } else {
            return false;
        }
    }

    /**
     * get Messagetype
     * @return string
     */
    public function getMessageType()
    {
        return (!empty($this->_message['type']) ? $this->_message['type'] : false);
    }

    /**
     * get Message
     * @return string
     */
    public function getMessage()
    {
        return (!empty($this->_message['message']) ? $this->_message['message'] : false);
    }

}