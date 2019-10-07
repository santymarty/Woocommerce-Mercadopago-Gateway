'use strict';

(function (settings, MP, $) {

    let oldBin;
    const MP_Form = '.wcmp-gateway-form';

    MP.setPublishableKey(settings.public_key);

    $(document.body).on('updated_checkout', function () {
        new FormHandler(MP_Form);
    });

    $('form.checkout.woocommerce-checkout').on('checkout_place_order_wc_mp_gateway', function () {
        let form = document.querySelector(MP_Form);
        MP.createToken(form, function (status, response) {
            if (status === 200 || status === 201) {
                let previousToken = form.querySelector('input[name="token"]');
                if (previousToken) previousToken.remove();
                let cardToken = document.createElement('input');
                cardToken.setAttribute('name', 'token');
                cardToken.setAttribute('type', 'hidden');
                cardToken.setAttribute('value', response.id);
                form.appendChild(cardToken);
            }
        });
    });

    let MP_Helper = {
        getBin: (number) => number.substring(0, 7)
    }

    class FormHandler {
        constructor(formSelector) {
            let form = document.querySelector(formSelector);
            this.elems = {
                form: form,
                ccName: form.querySelector('input[name="ccName"]'),
                ccExpiry: form.querySelector('input[name="ccExpiry"]'),
                ccNumber: form.querySelector('input[name="ccNumber"]'),
                hiddenPaymentMethodId: form.querySelector('input[name="hiddenPaymentMethodId"]'),
                installments: form.querySelector('select[name="installments"]'),
                hiddenInstallmentsType: form.querySelector('input[name="hiddenInstallmentsType"]'),
                ccHiddenNumber: form.querySelector('input[name="hiddenCcNumber"]'),
                //ccHiddenName: form.querySelector('input[name="hiddenCcName"]'),
                ccHiddenMonth: form.querySelector('input[name="hiddenExpiryMonth"]'),
                ccHiddenYear: form.querySelector('input[name="hiddenExpiryYear"]')
            };
            new Card({
                form: this.elems.form,
                container: '.wcmp-gateway-form-card',
                formSelectors: {
                    numberInput: 'input[name="ccNumber"]',
                    expiryInput: 'input[name="ccExpiry"]',
                    cvcInput: 'input[name="ccCvc"]',
                    nameInput: 'input[name="ccName"]'
                },
            });
            this.elems.ccNumber.addEventListener('keyup', this.handleNewccNumber.bind(this));
            this.elems.ccExpiry.addEventListener('keyup', this.handleExpiryDateChange.bind(this));

            this.elems.ccNumber.addEventListener('keyup', this.syncCcNumber.bind(this));
            //this.elems.ccName.addEventListener('keyup', this.syncCcName.bind(this));
        }
        syncCcNumber = function (e) {
            let elem = e.currentTarget;
            let number = elem.value.replace(/\D/g, '');
            this.elems.ccHiddenNumber.value = number;
        }
        //syncCcName = (e) => this.elems.ccHiddenName.value = e.currentTarget.value;

        handleNewccNumber = function (e) {
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
        handleCardToken = function (status, response) {
            console.log(status, response);
            if (status === 200 || status === 201) {
                let cardToken = document.createElement('input');
                cardToken.setAttribute('name', 'token');
                cardToken.setAttribute('type', 'hidden');
                cardToken.setAttribute('value', response.id);
                this.elems.form.appendChild(cardToken);
                console.log(cardToken);
            }
        }
        handleExpiryDateChange(e) {
            let elem = e.currentTarget;
            if (elem.classList.contains('jp-card-valid')) {
                this.setExpirationDates(elem.value);
            } else {
                this.clearExpirationDate()
            }
        }
        clearExpirationDate = function () {
            this.elems.ccHiddenMonth.value = '';
            this.elems.ccHiddenYear.value = '';
        }
        setExpirationDates = function (expDate) {
            let dates = expDate.match(/[0-9]{2}/g);
            if (dates.length !== 2) return false;
            this.setMonthExpirationDate(dates[0]);
            this.setYearExpirationDate(dates[1]);
        }
        setPaymentMethodInfo = function (status, response) {
            if (status === 200) {
                this.elems.hiddenPaymentMethodId.value = response[0].id;
                MP.getIdentificationTypes();
            }
        }
        setInstallmentsInfo = function (status, response) {
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
            installments.forEach(installment => {
                let option = document.createElement('option');
                option.text = installment.recommended_message;
                option.value = installment.installments;
                this.elems.installments.appendChild(option);
            });
        };
        setMonthExpirationDate = function (month) {
            this.elems.ccHiddenMonth.value = month
        };
        setYearExpirationDate = function (year) {
            this.elems.ccHiddenYear.value = year
        };
    }
})(wc_mp_gateway_settings, Mercadopago, jQuery);