'use strict';

((settings, MP, $) => {

    let oldBin;
    const MP_Form = '.wc-mp-gateway-form';

    MP.setPublishableKey(settings.public_key);

    $(document.body).on('updated_checkout', () => new FormHandler(MP_Form));

    $('form.checkout.woocommerce-checkout').on('checkout_place_order_wc_mp_gateway', () => {
        let form = document.querySelector(MP_Form);
        MP_Helper.createToken(form);
        let previousToken = form.querySelector('input[name="CcToken"]');
        if (!previousToken) return false;
    });

    let MP_Helper = {
        getBin: (number) => number.substring(0, 7),
        createToken: (form) => {
            MP.clearSession();
            MP.createToken(form, (status, response) => {
                if (status === 200 || status === 201) {
                    let previousToken = form.querySelector('input[name="CcToken"]');
                    if (previousToken) {
                        previousToken.value = response.id;
                    } else {
                        let cardToken = document.createElement('input');
                        cardToken.setAttribute('name', 'CcToken');
                        cardToken.setAttribute('type', 'hidden');
                        cardToken.setAttribute('value', response.id);
                        form.appendChild(cardToken);
                    }
                }
            });
        }
    }

    class FormHandler {
        constructor(formSelector) {
            // Define our base form
            let form = document.querySelector(formSelector);

            // Define our elements in our form
            this.elems = {
                form: form,
                ccName: form.querySelector('input[name="ccName"]'),
                ccExpiry: form.querySelector('input[name="ccExpiry"]'),
                ccNumber: form.querySelector('input[name="ccNumber"]'),
                ccCvc: form.querySelector('input[name="ccCvc"]'),
                ccDocumentNumber: form.querySelector('input[name="docNumber"]'),
                hiddenPaymentMethodId: form.querySelector('input[name="hiddenPaymentMethodId"]'),
                installments: form.querySelector('select[name="installments"]'),
                installmentsRateLabel: form.querySelector('.installments_rate'),
                hiddenInstallmentsType: form.querySelector('input[name="hiddenInstallmentsType"]'),
                ccHiddenNumber: form.querySelector('input[name="hiddenCcNumber"]'),
                ccHiddenMonth: form.querySelector('input[name="hiddenExpiryMonth"]'),
                ccHiddenYear: form.querySelector('input[name="hiddenExpiryYear"]')
            };
            this.ccTokenValues = {
                number: null,
                name: null,
                month: null,
                year: null,
                cvc: null,
                document: null
            };

            // Instantiate the virtual card in the Checkout
            new Card({
                form: this.elems.form,
                container: '.wc-mp-gateway-form-card',
                formSelectors: {
                    numberInput: 'input[name="ccNumber"]',
                    expiryInput: 'input[name="ccExpiry"]',
                    cvcInput: 'input[name="ccCvc"]',
                    nameInput: 'input[name="ccName"]'
                },
                width: settings.card_size
            });

            // Add listeners to the "reactive" fields
            this.elems.ccNumber.addEventListener('keyup', this.handleNewccNumber.bind(this));
            this.elems.ccExpiry.addEventListener('keyup', this.handleExpiryDateChange.bind(this));
            this.elems.installments.addEventListener('change', this.showInstallmentLabel.bind(this));
            this.elems.ccNumber.addEventListener('keyup', this.syncCcNumber.bind(this));
            this.elems.form.addEventListener('keyup', this.checkCardToken.bind(this));
        }
        showInstallmentLabel = () => {
            let selected = this.elems.installments.selectedIndex;
            let label = this.elems.installments.options[selected].getAttribute('data-label');
            this.elems.installmentsRateLabel.innerHTML = label;
        }
        createCardToken = () => {

            this.ccTokenValues.number = this.elems.ccHiddenNumber.value;
            this.ccTokenValues.name = this.elems.ccName.value;
            this.ccTokenValues.month = this.elems.ccHiddenMonth.value;
            this.ccTokenValues.year = this.elems.ccHiddenYear.value;
            this.ccTokenValues.cvc = this.elems.ccCvc.value;
            this.ccTokenValues.document = this.elems.ccDocumentNumber.value;

            MP_Helper.createToken(this.elems.form);
        }
        checkCardToken = (e) => {
            /* We could avoid this archaic method of checking for a new card token just by listening the
            checkout_place_order_wc_mp_gateway event, but the MercadoPago SDK doesn't allow us to ask for 
            a card token in a synchronously way, making that event "useless" */
            let number = this.elems.ccHiddenNumber.value;
            if (number.length < 16) return false;
            let name = this.elems.ccName.value;
            if (name.length < 3) return false;
            let month = this.elems.ccHiddenMonth.value;
            if (!month) return false;
            let year = this.elems.ccHiddenYear.value;
            if (!year) return false;
            let cvc = this.elems.ccCvc.value;
            if (cvc.length < 3) return false;
            let document = this.elems.ccDocumentNumber.value;
            if (document.length < 5) return false;

            if (this.ccTokenValues.number !== number ||
                this.ccTokenValues.month !== month ||
                this.ccTokenValues.year !== year ||
                this.ccTokenValues.cvc !== cvc ||
                this.ccTokenValues.document !== document
            ) {
                this.createCardToken();
            }
        }
        syncCcNumber = (e) => {
            let elem = e.currentTarget;
            let number = elem.value.replace(/\D/g, '');
            this.elems.ccHiddenNumber.value = number;
        }

        handleNewccNumber = (e) => {
            let newBin = MP_Helper.getBin(this.elems.ccNumber.value);
            if (newBin.length < 7 || oldBin === newBin) return;
            oldBin = newBin;
            newBin = newBin.replace(/\D/g, '');
            MP.getPaymentMethod({
                'bin': newBin
            }, this.setPaymentMethodInfo.bind(this));
            MP.getInstallments({
                'bin': newBin,
                'amount': settings.cart_amount
            }, this.setInstallmentsInfo.bind(this));
        }
        handleExpiryDateChange(e) {
            let elem = e.currentTarget;
            if (elem.classList.contains('jp-card-valid')) {
                this.setExpirationDates(elem.value);
            } else {
                this.clearExpirationDate()
            }
        }
        clearExpirationDate = () => {
            this.elems.ccHiddenMonth.value = '';
            this.elems.ccHiddenYear.value = '';
        }
        setExpirationDates = (expDate) => {
            let dates = expDate.match(/[0-9]{2}/g);
            if (dates.length !== 2) return false;
            this.setMonthExpirationDate(dates[0]);
            this.setYearExpirationDate(dates[1]);
        }
        setPaymentMethodInfo = (status, response) => {
            if (status === 200) {
                this.elems.hiddenPaymentMethodId.value = response[0].id;
                MP.getIdentificationTypes();
            }
        }
        setInstallmentsInfo = (status, response) => {
            if (status === 200) {
                let installments = null;
                let installmentsBackup = null;
                for (let res in response) {
                    let element = response[res];
                    if (element.processing_mode === 'gateway') {
                        installments = element;
                        break;
                    } else if (element.processing_mode === 'aggregator') {
                        installmentsBackup = element;
                    }
                }
                if (installments) {
                    this.elems.hiddenInstallmentsType.value = 'gateway';
                } else if (installmentsBackup) {
                    this.elems.hiddenInstallmentsType.value = 'aggregator';
                    installments = installmentsBackup;
                }
                this.setInstallments(installments.payer_costs);
            }
        }
        setInstallments = (installments) => {
            while (this.elems.installments.options.length)
                this.elems.installments.options.remove(0)

            installments.forEach(installment => {
                let option = document.createElement('option');
                let labels_length = installment.labels.length;
                let index = 0;
                for (index = 0; index < labels_length; index++) {
                    const element = installment.labels[index];
                    if (element.indexOf('CFT_') !== -1) {
                        break;
                    }
                }
                option.text = installment.recommended_message;
                option.setAttribute('data-label', installment.labels[index]);
                option.setAttribute('value', installment.installments);
                this.elems.installments.appendChild(option);
            });
        };
        setMonthExpirationDate = (month) => {
            this.elems.ccHiddenMonth.value = month
        };
        setYearExpirationDate = (year) => {
            this.elems.ccHiddenYear.value = year
        };
    }
})(wc_mp_gateway_settings, Mercadopago, jQuery);