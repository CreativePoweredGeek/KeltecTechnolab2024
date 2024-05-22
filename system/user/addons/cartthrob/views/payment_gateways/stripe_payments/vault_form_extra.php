<script src="https://js.stripe.com/v3/"></script>
<?=sprintf('<script>const stripe = Stripe("%s");</script>', $publishable_key); ?>
<script type="text/javascript">

    const options = {
        clientSecret: '<?=$client_secret; ?>',
        appearance: {
            theme: '<?=$theme;?>',
            <?php if($rules): ?>
            rules: {
                <?=$rules;?>
            },
            <?php endif;?>
            <?php if($variables): ?>
            variables: {
                <?=$variables;?>
            },
            <?php endif;?>
            labels: '<?=$labels;?>'
        }
    };

    const elements = stripe.elements(options);
    const paymentElement = elements.create('payment');

    //https://stripe.com/docs/js/setup_intents/confirm_setup
    paymentElement.mount('#payment-element');
    CartthrobTokenizer.onSubmit(function () {
        let cardholderName = [CartthrobTokenizer.getElementById("first_name"), CartthrobTokenizer.getElementById("last_name")].join(' ');
        //elements.submit();
        stripe.confirmSetup({
            elements,
            //clientSecret: '<?=$client_secret; ?>',
            redirect: 'if_required',
            confirmParams: {
                return_url: '<?=$return_url; ?>',
                payment_method_data: {
                    //card: paymentElement,
                    billing_details: {
                        email: CartthrobTokenizer.getElementById("email_address"),
                        phone: CartthrobTokenizer.getElementById("phone"),
                        name: cardholderName,
                        address: {
                            "city": CartthrobTokenizer.getElementById("city"),
                            "line1": CartthrobTokenizer.getElementById("address"),
                            "line2": CartthrobTokenizer.getElementById("address2"),
                            "state": CartthrobTokenizer.getElementById("state"),
                            "postal_code": CartthrobTokenizer.getElementById("zip")
                        }
                    },
                }
            },
        })
        .then(function(result) {
            if (result.error) {
                CartthrobTokenizer.error(result.error.message)
            } else {
                //alert(JSON.stringify(result));
                CartthrobTokenizer.addHidden("payment-method-id", result.setupIntent.payment_method)
                CartthrobTokenizer.addHidden("payment-intent-id", result.setupIntent.id)
                CartthrobTokenizer.addHidden("client-secret", result.setupIntent.client_secret)
                CartthrobTokenizer.success();
            }
        });
    });
    // CartthrobTokenizer.onError(function () {});
    // CartthrobTokenizer.onSuccess(function () {});
</script>