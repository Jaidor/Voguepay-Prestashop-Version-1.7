<?php
/*
* 2007-2019 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.

*

*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2019 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_'))

    exit;

class VoguePay extends PaymentModule
{    

    private $_html = '';
    private $_postErrors = array();
    public $details;
    public $owner;
    public $address;
    public $extra_mail_vars;

    public function __construct()
    {

        $this->name = 'voguepay';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.2';
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->author = 'Jaidor';
        $this->controllers = array('payment', 'validation');
        $this->is_eu_compatible = 0;
        $this->module_key = '7bd648045911885fe8a9a3c6f550d76e';
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $config = Configuration::getMultiple(array('VOGUEPAY_MERCHANT_ID', 'VOGUEPAY_STORE_ID', 'VOGUEPAY_PAYMENT_METHOD','VOGUEPAY_DEMO_MODE','VOGUEPAY_CURRENCY'));     

        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->trans('Voguepay', array(), 'Modules.Voguepay.Admin');
        $this->description = $this->trans('Accept payments by credit card  both local and international buyers, quickly and securely with VoguePay.', array(), 'Modules.VoguePay.Admin');
        $this->confirmUninstall = $this->trans('Are you sure about removing these details?', array(), 'Modules.Voguepay.Admin');

        if (!isset($this->owner) || !isset($this->details) || !isset($this->address)) {
            $this->warning = $this->trans('Account owner and account details must be configured before using this module.', array(), 'Modules.Voguepay.Admin');
        }

        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->trans('No currency has been set for this module.', array(), 'Modules.Voguepay.Admin');
        }

        $this->extra_mail_vars = array(
            '{voguepay_owner}' => Configuration::get('VOGUEPAY_USERNAME'),
            '{voguepay_details}' => nl2br(Configuration::get('VOGUEPAY_DETAILS')),
        );
    }

    public function install()
    {
        if (!parent::install() || !$this->registerHook('paymentReturn') || !$this->registerHook('paymentOptions') || !$this->registerHook('header')) {
            return false;
        }

        // TODO : Cek insert new state, Custom CSS
        $newState = new OrderState();
        $newState->send_email = true;
        $newState->module_name = $this->name;
        $newState->invoice = true;
        $newState->color = "#04b404";
        $newState->unremovable = false;
        $newState->logable = true;
        $newState->delivery = false;
        $newState->hidden = false;
        $newState->shipped = false;
        $newState->paid = true;
        $newState->delete = false;

        $languages = Language::getLanguages(true);
        foreach ($languages as $lang) {
            if ($lang['iso_code'] == 'id') {
                $newState->name[(int)$lang['id_lang']] = 'Menunggu pembayaran via Voguepay';
            } else {
                $newState->name[(int)$lang['id_lang']] = 'Payment successful';
            }
            $newState->template = "voguepay";
        }

        if ($newState->add()) {
            Configuration::updateValue('PS_OS_VOGUEPAY', $newState->id);
            copy(dirname(__FILE__).'/logo.png', _PS_IMG_DIR_.'tmp/order_state_mini_'.(int)$newState->id.'_1.png');
        } else {
            return false;
        }
        return true;
    }

    public function uninstall()
    {

        if (!Configuration::deleteByName('VOGUEPAY_MERCHANT_ID')
            || !parent::uninstall()
        ) {
            return false;
        }
        return true;
    }


    protected function _postProcess()
    {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue('VOGUEPAY_MERCHANT_ID', Tools::getValue('VOGUEPAY_MERCHANT_ID'));
            Configuration::updateValue('VOGUEPAY_STORE_ID', Tools::getValue('VOGUEPAY_STORE_ID'));
            Configuration::updateValue('VOGUEPAY_PAYMENT_METHOD', Tools::getValue('VOGUEPAY_PAYMENT_METHOD'));
            Configuration::updateValue('VOGUEPAY_DEMO_MODE', Tools::getValue('VOGUEPAY_DEMO_MODE'));
            Configuration::updateValue('VOGUEPAY_CURRENCY', Tools::getValue('VOGUEPAY_CURRENCY'));
        }
        $this->_html .= $this->displayConfirmation($this->trans('Settings updated', array(), 'Admin.Global'));
    }

    protected function _postValidation()

    {

        if (Tools::isSubmit('btnSubmit')) {
            if (!Tools::getValue('VOGUEPAY_MERCHANT_ID') AND Tools::getValue('VOGUEPAY_DEMO_MODE') == false) {
                $this->_postErrors[] = $this->trans('Demo mode should be activated for test or Merchant ID should be entered for Live payment.', array(), 'Modules.Voguepay.Admin');
            }
            if (Tools::getValue('VOGUEPAY_MERCHANT_ID') AND Tools::getValue('VOGUEPAY_DEMO_MODE') == true) {
                $this->_postErrors[] = $this->trans('Demo mode and merchant ID can not be used at the same time, kindly make use of one.', array(), 'Modules.Voguepay.Admin');
            }
            if (!Tools::getValue('VOGUEPAY_CURRENCY')) {
                $this->_postErrors[] = $this->trans('Currency is required.', array(), 'Modules.Voguepay.Admin');
            }
        }
    }

    private function _displayVoguepay()
    {
        return $this->display(__FILE__, 'infos.tpl');
    }

    public function getContent()
    {

        if (Tools::isSubmit('btnSubmit')) {
            $this->_postValidation();
            if (!count($this->_postErrors)) {
                $this->_postProcess();
            } else {
                foreach ($this->_postErrors as $err) {
                    $this->_html .= $this->displayError($err);
                }
            }
        } else {
            $this->_html .= '<br />';
        }

        $this->_html .= $this->_displayVoguepay();
        $this->_html .= $this->renderForm();
        return $this->_html;
    }


    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        if (!$this->checkCurrencyNGN($params['cart'])) {
            return;
        }

        $config = $this->getConfigFieldsValues();
        if ($config['VOGUEPAY_DEMO_MODE'] == true) {
            $merchant_id = 'demo';
        } else {
            $merchant_id = $config['VOGUEPAY_MERCHANT_ID'];
        }

        if ($merchant_id == '') {
            return;
        }



        $gateway_chosen = 'none';
        if (Tools::getValue('gateway') == 'voguepay') {
            $cart = $this->context->cart;
            $gateway_chosen = 'voguepay';
            $customer = new Customer((int)($cart->id_customer));
            $amount = $cart->getOrderTotal(true, Cart::BOTH);
            $currency_order = new Currency($cart->id_currency);

            $this->data = array();
            $this->data['merchant_id'] = $merchant_id;
            $this->data['store_id'] = Configuration::get('VOGUEPAY_STORE_ID');
            $this->data['cur'] = Configuration::get('VOGUEPAY_CURRENCY');
            $this->data['developer_code'] = '5c06818ac2d89';
            $this->data['return_url'] = $this->context->link->getPageLink('order-confirmation', null, null, 'key=' . $cart->secure_key . '&id_cart=' . (int) ($cart->id) . '&id_module=' . (int) ($this->id));
            $this->data['notify_url'] = $this->context->link->getModuleLink('voguepay', 'voguepaysuccess', array(), true);
            $this->responseurl = $this->context->link->getModuleLink('voguepay', 'voguepaysuccess', array(), true);
            $this->data['email_address'] = $customer->email;
            $this->data['amount'] = number_format(sprintf("%01.2f", $amount), 2, '.', '');
            $this->data['item_name'] = Configuration::get('PS_SHOP_NAME') . ' Payment, Cart Item ID #' . $cart->id;
            $uniqueRef = uniqid();
            $voguePayOrderId = $uniqueRef.'_'.$cart->id.'_'.$cart->secure_key;
            $this->data['merchant_ref'] = $voguePayOrderId;

            $this->url = "https://voguepay.com/?p=linkToken&v_merchant_id=".urlencode($this->data['merchant_id'])."&store_id=".urlencode($this->data['store_id'])."&memo=".urlencode($this->data['item_name'])."&total=".urlencode($this->data['amount'])."&merchant_ref=".urlencode($this->data['merchant_ref'])."&%20notify_url=".urlencode($this->data['notify_url'])."&success_url=".urlencode($this->responseurl)."&fail_url=".urlencode($this->responseurl)."&developer_code=".urlencode($this->data['developer_code'])."&cur=".urlencode($this->data['cur'])." ";        

            $ch = curl_init($this->url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $this->response = curl_exec($ch);

            $params = array(
              "response"    => $this->response,
            );

            $this->context->smarty->assign(
                array(
                'gateway_chosen' => 'voguepay',
                'redirect_url'       => $this->context->link->getModuleLink($this->name, 'voguepaysuccess', array(), true),
                )
            );

            $this->context->smarty->assign(
                $params
            );
        }
            

        $newOption = new PaymentOption();
        $newOption->setCallToActionText($this->trans('Voguepay (Debit/credit cards)', array(), 'Modules.Voguepay.Shop'))
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
            ->setAdditionalInformation($this->context->smarty->fetch('module:voguepay/views/templates/hook/intro.tpl'))
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/voguepay-logo.jpg'));
        if ($gateway_chosen == 'voguepay' AND Configuration::get('VOGUEPAY_PAYMENT_METHOD') == false) {
                $newOption->setAdditionalInformation(
                    $this->context->smarty->fetch('module:voguepay/views/templates/front/embedded.tpl')
                );
        }
        if(Configuration::get('VOGUEPAY_PAYMENT_METHOD') == true AND $gateway_chosen == 'voguepay'){
            $newOption->setAdditionalInformation(
            $this->context->smarty->fetch('module:voguepay/views/templates/front/redirect.tpl')
            );
        }
        $payment_options = [
            $newOption,
        ];
        return $payment_options;
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }


        $status = $_GET['status'];
        $tx_id = $_GET['reference'];
        $reference = Tools::getValue('reference');
        if ($status == "Approved") {
            $reference = $params['order']->reference;        

            $this->smarty->assign(
                array(
                'shop_name' => 'Voguepay',
                'total' => Tools::displayPrice(
                    $params['order']->getOrdersTotalPaid(),
                    new Currency($params['order']->id_currency),
                    false
                ),
                'status' => 'Approved',
                'reference' => $tx_id,
                'contact_url' => $this->context->link->getPageLink('contact', true)
                )
            );

        } else {
            $this->smarty->assign(
                array(
                    'status' => 'Failed',
                    'reference' => $tx_id,
                    'contact_url' => $this->context->link->getPageLink('contact', true),
                )
            );
        }

        return $this->fetch('module:voguepay/views/templates/hook/payment_return.tpl');
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function checkCurrencyNGN($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        if ($currency_order->iso_code == 'NGN' || $currency_order->iso_code == 'GHS' || $currency_order->iso_code == 'USD' || $currency_order->iso_code == 'GBP' || $currency_order->iso_code == 'EUR' || $currency_order->iso_code == 'ZAR') {
            return true;
        }
        return false;
    }

    public function renderForm()
    {

        $fields_form = array(

            'form' => array(

                'legend' => array(
                    'title' => $this->trans('Settings', array(), 'Modules.Voguepay.Admin'),
                    'icon' => 'icon-cogs'
                ),

                'input' => array(

                    array(
                        'type' => 'switch',
                        'label' => $this->trans('Demo Mode', array(), 'Modules.Voguepay.Admin'),
                        'name' => 'VOGUEPAY_DEMO_MODE',
                        'is_bool' => true,
                        'desc' => $this->trans('The Merchant ID field is not required if Demo Mode is set to Yes.', array(), 'Modules.Voguepay.Admin'),

                        'values' => array(

                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->trans('Enable', array(), 'Admin.Global'),
                            ),

                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->trans('Disable', array(), 'Admin.Global'),
                            )

                        ),

                    ),

                    array(
                        'type' => 'text',
                        'label' => $this->trans('Merchant ID', array(), 'Modules.Voguepay.Admin'),
                        'hint' => $this->trans('Voguepay Merchant ID', array(), 'Modules.Voguepay.Admin'),
                        'desc' => $this->trans('Your Merchant ID from your voguepay account', array(), 'Modules.Voguepay.Admin'),
                        'name' => 'VOGUEPAY_MERCHANT_ID',
                        'required' => true,

                    ),

                    array(

                        'type' => 'text',
                        'label' => $this->trans('Store ID', array(), 'Modules.Voguepay.Admin'),
                        'name' => 'VOGUEPAY_STORE_ID',
                        'desc' => $this->trans('Your Store ID from your Voguepay account (Optional.)', array(), 'Modules.Voguepay.Admin'),
                    ),

                    array(
                        'type'      => 'radio',
                        'label' => $this->trans('Check Currency', array(), 'Modules.Voguepay.Admin'),
                        'hint' => $this->trans('Please check atleast one currency', array(), 'Modules.Voguepay.Admin'),
                        'name' => 'VOGUEPAY_CURRENCY',
                        'required' => true,
                        'values'    => array(                                 

                            array(
                                'id'    => 'active_on',
                                'value' => 'NGN',
                                'label' => $this->l('NGN')
                            ),

                            array(
                                'id'    => 'active_off',
                                'value' => 'USD',
                                'label' => $this->l('USD')
                            ),

                            array(
                                'id'    => 'active_on',
                                'value' => 'GBP',
                                'label' => $this->l('GBP')
                            ),

                            array(
                                'id'    => 'active_off',
                                'value' => 'EUR',
                                'label' => $this->l('EUR')
                            ),

                            array(
                                'id'    => 'active_on',
                                'value' => 'GHS',
                                'label' => $this->l('GHS')
                            ),

                            array(
                                'id'    => 'active_off',
                                'value' => 'ZAR',
                                'label' => $this->l('ZAR')
                            )
                        )
                    ),

                    array(
                        'type' => 'switch',
                        'label' => $this->trans('Payment Method', array(), 'Modules.Voguepay.Admin'),
                        'name' => 'VOGUEPAY_PAYMENT_METHOD',
                        'is_bool' => true,
                        'desc' => $this->trans('Select Yes, for users to be redirected to VoguePay Checkout page or No, for users to complete payment on your Checkout page.', array(), 'Modules.Voguepay.Admin'),

                        'values' => array(

                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->trans('redirect', array(), 'Admin.Global'),
                            ),

                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->trans('inline', array(), 'Admin.Global'),
                            )
                        ),

                    ),

                ),

                'submit' => array(
                    'title' => $this->trans('Save Settings', array(), 'Admin.Actions'),
                    'name' => 'btnSubmit',
                )
            ),
        );

        $fields_form_customization = array();

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? : 0;
        $this->fields_form = array();
        $helper->id = (int)Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='
            .$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );
        return $helper->generateForm(array($fields_form, $fields_form_customization));
    }

    public function getConfigFieldsValues()
    {
        return array(
            'VOGUEPAY_MERCHANT_ID' => Tools::getValue('VOGUEPAY_MERCHANT_ID', Configuration::get('VOGUEPAY_MERCHANT_ID')),
            'VOGUEPAY_STORE_ID' => Tools::getValue('VOGUEPAY_STORE_ID', Configuration::get('VOGUEPAY_STORE_ID')),
            'VOGUEPAY_PAYMENT_METHOD' => Tools::getValue('VOGUEPAY_PAYMENT_METHOD', Configuration::get('VOGUEPAY_PAYMENT_METHOD')),
            'VOGUEPAY_DEMO_MODE' => Tools::getValue('VOGUEPAY_DEMO_MODE', Configuration::get('VOGUEPAY_DEMO_MODE')),
            'VOGUEPAY_CURRENCY' => Tools::getValue('VOGUEPAY_CURRENCY', Configuration::get('VOGUEPAY_CURRENCY'))
        );
    }

}

