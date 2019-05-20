{*
* Voguepay
*}
{if isset($gateway_chosen) && $gateway_chosen == 'voguepay'}


<script src="//voguepay.com/js/voguepay.js"></script>
<script>
    closedFunction=function() {
        alert('window closed');
    }

     successFunction=function(transaction_id) {
        alert('Transaction was successful, Ref: '+transaction_id);
        var mainUrl = "{$redirect_url}?w1=" + transaction_id;
        var replaceValue = /&amp;/ig;
        var replacedUrl = mainUrl.replace( replaceValue, "&" );
        window.location.href = replacedUrl;


    }

     failedFunction=function(transaction_id) {
        alert('Transaction was not successful, Ref: '+transaction_id)
        var mainUrl = "{$redirect_url}?w1=" + transaction_id;
        var replaceValue = /&amp;/ig;
        var replacedUrl = mainUrl.replace( replaceValue, "&" );
        window.location.href = replacedUrl;
    }
</script>
<script>
    Voguepay.link({
        url: '{$response}',
        loadText:"Loading payment interface... Please Wait...",
        success:successFunction,
        failed:failedFunction
    });
</script>
{/if}