{*
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
*}

{if $status == 'Approved'}
    <p>
      {l s='Transaction successful.' sprintf=[$shop_name] mod='voguepay'}
    </p>
    <p>
      Your payment has been recieved : <b style="color: #20B2AA;">{$status}</b>
    </p>
    <p>
     Payment Reference: <b style="color: #20B2AA; ">{$reference}</b>
    </p>
    <p>
      {l s='If you have questions, comments or concerns, please contact our [1]expert customer support team[/1].' mod='voguepay' tags=["<a href='{$contact_url}'>"]}
    </p>
{else}
    <p class="warning">
    <p>
      {l s='Transaction was not successful.'}
    </p>
    <p>
      Your payment was not recieved : <b style="color: #a94442; ">{$status}</b>
    </p>
    <p>
     Payment Reference: <b style="color: #a94442; ">{$reference}</b>
    </p>
      {l s='We noticed a problem with your order. If you think this is an error, feel free to contact our [1]expert customer support team[/1].' mod='voguepay' tags=["<a href='{$contact_url}'>"]}
    </p>
{/if}
