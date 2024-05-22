<script src="https://js.stripe.com/v3/"></script>
<?=sprintf('<script>let stripe = Stripe("%s");</script>', $publishable_key); ?>
<script type="text/javascript">
    let elements = stripe.elements();
    let cardElement = elements.create("card", {hidePostalCode: <?=$hide_postal_code;?>, iconStyle: "<?=$icon_style;?>", hideIcon: <?=$hide_icon;?>, style: { <?=$styles;?> }});
    let cardButton = document.getElementById("checkout_complete");

    cardElement.mount("#card-element");

    CartthrobTokenizer.onSubmit(function () {
        let cardholderName = [CartthrobTokenizer.getElementById("first_name"), CartthrobTokenizer.getElementById("last_name")].join(' ');
        stripe.createPaymentMethod("card", cardElement, {
            billing_details: {
                name: cardholderName,
                email: CartthrobTokenizer.getElementById("email_address"),
                phone: CartthrobTokenizer.getElementById("phone"),
                address: {
                    "city": CartthrobTokenizer.getElementById("city"),
                    "line1": CartthrobTokenizer.getElementById("address"),
                    "line2": CartthrobTokenizer.getElementById("address2"),
                    "state": CartthrobTokenizer.getElementById("state"),
                    "postal_code": CartthrobTokenizer.getElementById("zip")
                }
            }
        }).then(function(result) {
            if (result.error) {
                CartthrobTokenizer.error(result.error.message)
            } else {
                CartthrobTokenizer.addHidden("payment-method-id", result.paymentMethod.id)
                CartthrobTokenizer.addHidden("credit_card_number", result.paymentMethod.card.last4)
                CartthrobTokenizer.addHidden("card_type", result.paymentMethod.card.brand)
                CartthrobTokenizer.success();
            }
        });
    });
    // CartthrobTokenizer.onError(function () {});
    // CartthrobTokenizer.onSuccess(function () {});
</script>