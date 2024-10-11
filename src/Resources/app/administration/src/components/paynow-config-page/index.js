import template from "./paynow-config-page.html.twig";
import './style.scss';

const {Component, Mixin, Context} = Shopware;
const {Criteria} = Shopware.Data;

Component.register('paynow-settings', {
    template,
    inject: [
        'repositoryFactory',
        'PayNowOrderService',
        'systemConfigApiService'
    ],
    mixins: [
        Mixin.getByName('notification'),
    ],
    data() {
        return {
            isLoading: false,
            notificationUrl: 'notification',
            returnUrl: 'return',
        }
    },
    created() {
        if (document.documentURI.split('/').pop() === "CrehlerPayNowPayment") {
            let title;
            if (Shopware.Context.app.fallbackLocale === "pl-PL") {
                title = "paynow konfiguracja"
            } else {
                title = "paynow configuration"
            }
            document.getElementsByClassName("sw-meteor-page__smart-bar-title")[0].innerText = title
        }
        this.getSalesChannel();

    },
    computed: {
        salesChannelDomainRepository() {
            return this.repositoryFactory.create('sales_channel_domain');
        }
    },
    methods: {
        async onTestCredentials() {
            try {
                let res = await this.PayNowOrderService.testApi();
                console.log(res)
                this.createNotificationSuccess({
                    title: 'Success',
                    message: this.$tc('pay-now-order.notification.testSucces'),
                });
            } catch (e) {
                this.createNotificationError({
                    title: 'Error',
                    message: this.$tc('pay-now-order.notification.testError'),
                });
            }
        },
        async getSalesChannel() {
            let criteria = new Criteria();
            criteria.addAssociation('salesChannel');
            let result = await this.salesChannelDomainRepository.search(criteria, Context.api)
            result.forEach(e => {
                if (e.url.split(':')[0] === 'https' && e.salesChannel.active) {

                    let salesChannelName = document.getElementById('salesChannelSelect');
                    salesChannelName = salesChannelName.getElementsByClassName('sw-entity-single-select__selection-text')[0].innerText
                    if (salesChannelName !== 'All Sales Channels' && salesChannelName !== 'Wszystkie kanały sprzedaży' && e.salesChannel.name !== salesChannelName) return

                    this.notificationUrl = e.url+ '/paynowpayment/notification'
                    this.returnUrl = e.url
                }
            })
        }
    }
});
