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

/**
 * @since 1.5.0
 */
class VoguepayVoguepaysuccessModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::postProcess()
     */
    public function initContent()
    {
		$cart = $this->context->cart;

        $transId = isset($_POST['transaction_id']) ? $_POST['transaction_id'] : $_GET['w1'];
        $voguepay = Module::getInstanceByName('voguepay');
        $json = file_get_contents('https://voguepay.com/?v_transaction_id=' . $transId . '&type=json&demo=true');
        $transaction = json_decode($json, true);
        $email = $transaction['email'];
        $total = $transaction['total'];
        $date = $transaction['date'];
        $voguePayOrderId = $transaction['merchant_ref'];
        $order_details = explode('_', $voguePayOrderId);
        $uniqueRef = $order_details[0];
        $cartId = $order_details[1];
        $secure_key = $order_details[2];
        $status = $transaction['status'];
        $transaction_id = $transaction['transaction_id'];

        $currency_order = new Currency($cart->id_currency);
        $customer = new Customer($cart->id_customer);

        if (trim(strtolower($status)) == 'approved') 
        {
          $extra_vars = array(
            'transaction_id' => $transaction_id,
             'id' => 1,
            'payment_method' => 'Voguepay',
            'status' => 'Paid',
            'currency' => $currency_order->iso_code,
            'intent' => '$intent'
            );      
      
          $this->module->validateOrder(
            $cart->id,
            Configuration::get('PS_OS_VOGUEPAY'),
            $total,
            $this->module->displayName,
            'Voguepay Reference: '.$transaction_id,
            $extra_vars,
            (int)$cart->id_currency,
            false,
            $customer->secure_key
          );

             Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key.'&reference='.$transaction_id.'&merchantRef='.$uniqueRef.'&status='.$status);
        }
        elseif (trim(strtolower($status)) == 'pending') 
        {
                  
          $extra_vars = array(
		    'transaction_id' => $transaction_id,
	        'id' => 1,
            'payment_method' => 'Voguepay',
            'status' => 'Pending',
            'currency' => $currency_order->iso_code,
            'intent' => '$intent'
            );		
			
			$this->module->validateOrder(
				$cart->id,
				Configuration::get('PS_OS_VOGUEPAY'),
				$total,
				$this->module->displayName,
				'Voguepay Reference: '.$transaction_id,
				$extra_vars,
				(int)$cart->id_currency,
				false,
				$customer->secure_key
            );
			    Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key.'&reference='.$transaction_id.'&merchantRef='.$uniqueRef.'&status='.$status);
        }
        else
        {
          $extra_vars = array(
            'transaction_id' => $transaction_id,
             'id' => 1,
            'payment_method' => 'Voguepay',
            'status' => 'Failed',
            'currency' => $currency_order->iso_code,
            'intent' => '$intent'
            );    
      
          $this->module->validateOrder(
            $cart->id,
            Configuration::get('PS_OS_VOGUEPAY'),
            $total,
            $this->module->displayName,
            'Voguepay Reference: '.$transaction_id,
            $extra_vars,
            (int)$cart->id_currency,
            false,
            $customer->secure_key
            );

             Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key.'&reference='.$transaction_id.'&merchantRef='.$uniqueRef.'&status='.$status);

        }
    }
}
