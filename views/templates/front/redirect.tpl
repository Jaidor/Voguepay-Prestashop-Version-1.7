{*
* Voguepay
*}
{if isset($gateway_chosen) && $gateway_chosen == 'voguepay'}
<form name="custompaymentmethod" id="payform" method="POST" action="{$response}"></form>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script type="text/javascript">
  $(document).ready(function(){
    $("#payform").submit();
  })        
</script>
{/if}