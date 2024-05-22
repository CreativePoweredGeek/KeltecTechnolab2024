<?php
$funding_source = '';
if($funding) {
    $funding_source = '&enable-funding='.implode(',', $funding);
}

$disabled_funding = '';
if($disabled_sources) {
    $disabled_funding = '&disable-funding='.implode(',', $disabled_sources);
}
?>
<script src="https://www.paypal.com/sdk/js?client-id=<?=sprintf("%s", $client_id);?>&currency=<?=$currency;?>&components=buttons<?=$funding_source;?><?=$disabled_funding;?>"></script>
<script>
    paypal.Buttons({
        createOrder: (data, actions) => {
            return actions.order.create({
                purchase_units: [{
                    amount: {
                        value: '<?=$amount;?>'
                    }
                }]
            });
        },
        onApprove: (data, actions) => {
            return actions.order.capture().then(function(orderData) {
                // Successful capture! For dev/demo purposes:
                console.log('Capture result', orderData, JSON.stringify(orderData, null, 2));
                const transaction = orderData.purchase_units[0].payments.captures[0];

                CartthrobTokenizer.addHidden("ref-id", transaction.id);
                CartthrobTokenizer.success();
            });
        }
    }).render('#paypal-button-container');

    const form_button = document.getElementById('checkout_complete');
    if(form_button) {
        form_button.style.display = "none";
    }
</script>