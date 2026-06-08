import template from './sw-order.html.twig';
import './sw-order.scss';

const { Component, Context } = Shopware;
const { Criteria } = Shopware.Data;

const PAYNOW_HANDLER = 'Crehler\\PayNowPayment\\Service\\PayNowService';

Component.override('sw-order-detail', {
    template,

    data() {
        return {
            isLoadingPayNowTransactions: true,
            payNowTransactions: [],
            hasIncompleteTransaction: false,
        };
    },

    computed: {
        showTabs() {
            return true;
        },
    },

    watch: {
        orderId: {
            deep: true,
            handler() {
                this.loadPayNowTransactions(this.orderId);
            },
            immediate: true,
        },
    },

    methods: {
        loadPayNowTransactions(orderId) {
            if (!orderId) {
                this.isLoadingPayNowTransactions = false;

                return;
            }

            this.isLoadingPayNowTransactions = true;

            const orderRepository = this.repositoryFactory.create('order');
            const orderCriteria = new Criteria(1, 1);
            orderCriteria.addAssociation('transactions.paymentMethod');
            orderCriteria.addFilter(Criteria.equals('id', orderId));

            orderRepository.search(orderCriteria, Context.api).then((searchResult) => {
                const order = searchResult.first();

                if (!order) {
                    return;
                }

                if (!this.identifier) {
                    this.identifier = order.orderNumber;
                }

                this.payNowTransactions = order.transactions.filter(
                    (orderTransaction) => orderTransaction.paymentMethod?.handlerIdentifier === PAYNOW_HANDLER,
                );
            }).finally(() => {
                this.isLoadingPayNowTransactions = false;
            });
        },

        getPayNowDetailsRoute(transactionId) {
            return {
                name: 'pay-now-order.payment.detail',
                params: {
                    id: this.$route.params.id,
                    transactionId: transactionId,
                },
            };
        },
    },
});
