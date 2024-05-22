<link
        rel="stylesheet"
        type="text/css"
        href="https://www.paypalobjects.com/webstatic/en_US/developer/docs/css/cardfields.css"
/>

<fieldset class="credit_card_info" id="credit_card_info">
    <legend>Credit Card Info</legend>
    <div class="control-group" style="margin:auto;width:80%">
        <label for="credit_card_number">Card Number</label>
        <div id="credit_card_number" class="card_field"></div>
        <div style="display: flex; flex-direction: row;">
            <div>
                <label for="expiration_date">Expiration Date</label>
                <div id="expiration_date" class="card_field"></div>
            </div>
            <div style="margin-left: 10px;">
                <label for="CVV2">CVV</label>
                <div id="CVV2" class="card_field"></div>
            </div>
        </div>
        <label for="card-holder-name">Name on Card</label>
        <input
                type="text"
                id="card_holder_name"
                name="card_holder_name"
                autocomplete="off"
                placeholder="card holder name"
        />
    </div>
</fieldset>