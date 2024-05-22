window.CartthrobTokenizer = {};

(function (tokenizer) {
    tokenizer.form = null;
    tokenizer.submissionState = false;

    tokenizer._submitCb = null;
    tokenizer._successCb = null;
    tokenizer._errorCb = null;

    tokenizer.submit = function () {
        this.form.dispatchEvent(_createEvent('ct.payment-form.submit'));

        if (this._submitCb) {
            this._submitCb();
        } else {
            this.success();
        }
    };

    tokenizer.error = function (msg) {
        this.form.dispatchEvent(_createEvent('ct.payment-form.error'));
        this.submissionState = false;

        if (this._errorCb) {
            this._errorCb(msg);
        } else {
            alert(msg);
        }
    };

    tokenizer.success = function (msg) {
        this.form.dispatchEvent(_createEvent('ct.payment-form.success'));
        this.submissionState = false;

        if (this._successCb) {
            this._successCb(msg);
        } else {
            this.form.submit();
        }
    };

    /**
     * Method to call when the checkout form is submitted
     *
     * @param {function} cb
     * @returns {CartthrobTokenizer}
     */
    tokenizer.onSubmit = function (cb) {
        this._submitCb = cb;
        return this;
    };

    /**
     * Method to call when the checkout errors
     *
     * @param {function} cb
     * @returns {CartthrobTokenizer}
     */
    tokenizer.onError = function (cb) {
        this._errorCb = cb;
        return this;
    };

    /**
     * Method to call when the checkout succeeds
     *
     * @param {function} cb
     * @returns {CartthrobTokenizer}
     */
    tokenizer.onSuccess = function (cb) {
        this._successCb = cb;
        return this;
    }

    /**
     * @param name
     * @returns {Event}
     * @private
     */
    function _createEvent(name) {
        var event = document.createEvent('Event');
        event.initEvent(name, true, true);
        return event;
    }

    /**
     * Form submission handler
     *
     * @param e
     * @returns {boolean}
     * @private
     */
    function _submit(e) {
        e.preventDefault();

        if (CartthrobTokenizer.submissionState === true) {
            return false;
        }

        CartthrobTokenizer.submissionState = true;

        CartthrobTokenizer.submit();

        return false;
    }

    /**
     * Add a hidden value to the form
     *
     * @param name
     * @param value
     * @returns {CartthrobTokenizer}
     */
    tokenizer.addHidden = function (name, value) {
        var input = document.createElement("input");
        input.setAttribute('type', 'hidden');
        input.setAttribute('name', name);
        input.value = value;

        this.form.appendChild(input);

        return this;
    };

    /**
     * Safe way to grab a document element without conditional soup
     * @param name
     * @param value
     * @returns {*}
     */
    tokenizer.getElementById = function(name, value) {
        let input = document.getElementById(name);

        if (input) {
            return input.value;
        }

        return null;
    };

    /**
     * Get an input value from the form
     * Shortcut to getElementById
     *
     * @param selector
     * @returns {*|null}
     */
    tokenizer.val = function (selector) {
        return  document.getElementById(selector).value;
    };

    /**
     * @param formSelector
     * @returns {CartthrobTokenizer}
     */
    tokenizer.init = function (formSelector) {
        formSelector = formSelector || "checkout_form";

        this.form = document.getElementById(formSelector);

        if (this.form === null) {
            console.error('There was no form with a selector of "' + formSelector + '" found on this page.');
            return this;
        }

        this.form.addEventListener('submit', _submit);

        return this;
    };

})(CartthrobTokenizer);
