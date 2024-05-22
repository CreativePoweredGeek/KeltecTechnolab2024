
<script
        src="https://www.paypal.com/sdk/js?components=buttons,hosted-fields&client-id=<?=sprintf("%s", $client_id);?>"
        <?=sprintf('data-client-token="%s"', $token);?>
></script>
<script>
    let orderId = '<?=$order_id;?>';
    if (paypal.HostedFields.isEligible()) {

        // Renders card fields
        paypal.HostedFields.render({
            createOrder: function () {
                return orderId;
            },
            styles: {
                '.valid': {
                    'color': 'green'
                },
                '.invalid': {
                    'color': 'red'
                }
            },
            fields: {
                number: {
                    selector: "#credit_card_number",
                    placeholder: "4032032049337022"
                },
                cvv: {
                    selector: "#CVV2",
                    placeholder: "123"
                },
                expirationDate: {
                    selector: "#expiration_date",
                    placeholder: "MM/YY",
                }
            }
        }).then((cardFields) => {
            CartthrobTokenizer.onSubmit(function () {
                cardFields.submit({
                    cardholderName: document.getElementById("card_holder_name").value,
                    billingAddress: {
                        streetAddress: document.getElementById('address').value,
                        extendedAddress: document.getElementById('address2').value,
                        region: document.getElementById('state').value,
                        locality: document.getElementById('city').value,
                        postalCode: document.getElementById('zip').value,
                        countryCodeAlpha2: document.getElementById("country_code").value
                    }
                }).then(() => {
                    CartthrobTokenizer.addHidden("paypal-order-id", "<?=$order_id;?>");
                    CartthrobTokenizer.success();

                }).catch(function (err) {
                    console.log(err);
                    return CartthrobTokenizer.error('Payment could not be captured!');
                    //alert('Payment could not be captured! ' + JSON.stringify(err));
                });
            });
        });
    } else {
        // Hides card fields if the merchant isn't eligible
        document.querySelector("#checkout_form").style = 'display: none';
    }
</script>