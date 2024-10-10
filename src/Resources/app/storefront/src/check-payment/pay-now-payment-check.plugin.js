import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';

export default class PayNowPaymentCheckPlugin extends Plugin {

    static options = {
        transactionId: '',
        successUrl: '',
        failUrl: '',
        checkUrl: '',
        waitingSuccessText: "",
        waitingFailText: "",
    }

    init() {
        this._errors = [];
        this._client = new HttpClient();
        this.checksCount = 0;
        const waitingTime = 0;
        this._getConfig();
        this.totalChecksCount = Math.floor(waitingTime/10);
        this._checkOrderPayment();
        this._checkPaymentStateInterval();
    }

    _getConfig() {
        const optionsString = this.el.dataset.paynowPaymentCheckOptions;
        const optionsObject = JSON.parse(optionsString);
        this.waitingTime = optionsObject.waitingTime
        this.options.checkUrl = optionsObject.checkUrl
        this.options.transactionId = optionsObject.transactionId
        this.options.successUrl = optionsObject.successUrl
        this.options.failUrl = optionsObject.failUrl
    }

    _checkPaymentStateInterval() {
        const intervalTime = 10000;
        this.loopInterval = setInterval(this._loop.bind(this), intervalTime);
    }

    _loop() {
        this._checkOrderPayment();
        if (this.checksCount >= this.totalChecksCount) {
            clearInterval(this.loopInterval);
            this._changeButtonText(this.options.waitingFailText);
            this._changeLocation(this.options.failUrl);
        }
    }

    _checkOrderPayment() {
        this.checksCount++;
        this._client.post(this.options.checkUrl, JSON.stringify({ transactionId: this.options.transactionId }) , this._showPaymentState.bind(this));
    }

    _showPaymentState(response) {
        let status = JSON.parse(response);
        console.log(status)
        if (status.success) {
            this._changeButtonText(this.options.waitingSuccessText);
            this._changeLocation(this.options.successUrl);
        } else if (!status.waiting) {
            this._changeButtonText(this.options.waitingFailText);
            this._changeLocation(this.options.failUrl);
        }
    }

    _changeButtonText(text) {
        this.el.querySelector('[data-info-text]').innerText = text;
    }

    _changeLocation(url) {
        setTimeout(() => {
            window.location.href = url;
        }, 1500)
    }

}
