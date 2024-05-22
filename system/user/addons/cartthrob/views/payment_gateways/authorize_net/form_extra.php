<?php if ($mode == 'test'): ?>
    <script type="text/javascript" src="https://jstest.authorize.net/v1/Accept.js" charset="utf-8"></script>
<?php else: ?>
    <script type="text/javascript" src="https://js.authorize.net/v1/Accept.js" charset="utf-8"></script>
<?php endif; ?>

<script type="text/javascript">
    CartthrobTokenizer.onSubmit(function () {
        var data = {
            authData: {
                clientKey: "<?=$client_key;?>",
                apiLoginID: "<?=$api_login_id;?>"
            },
            cardData: {
                cardNumber: CartthrobTokenizer.val('credit_card_number'),
                month: CartthrobTokenizer.val('expiration_month'),
                year: CartthrobTokenizer.val('expiration_year'),
                cardCode: CartthrobTokenizer.val('CVV2')
            }
        };

        Accept.dispatchData(data, function (response) {
            if (response.messages.resultCode === 'Error') {
                return CartthrobTokenizer.error(response.messages.message[0].text);
            }

            CartthrobTokenizer
                .addHidden('opaqueDataDescriptor', response.opaqueData.dataDescriptor)
                .addHidden('opaqueDataValue', response.opaqueData.dataValue);

            CartthrobTokenizer.success(response.messages.message[0].text);
        });
    });
    // CartthrobTokenizer.onError(function () {});
    // CartthrobTokenizer.onSuccess(function () {});
</script>