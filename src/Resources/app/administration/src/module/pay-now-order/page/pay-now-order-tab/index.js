import template from './pay-now-order-tab.html.twig';
import './pay-now-order-tab.scss';

const {Component, Mixin, Context} = Shopware;
const Criteria = Shopware.Data.Criteria;

Component.register('pay-now-order-tab', {
    template,

    inject: [
        'PayNowOrderService',
        'repositoryFactory',
    ],

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('sw-inline-snippet'),
    ],

    data() {
        return {
            showPayNowTab: false,
            refundAmountTooBig: false,
            refundAmountTooSmall: false,
            refundError: false,
            refundOk: false,
            refundRejected: false,
            succesfulProductsList: [],
            currency: null,
            listofPaymentsIdToFetch: [],
            listOfAmounts: [],
            refundStatus: null,
            refundPending: false,
            order: null,
            orderItems: [],
            orderTransactionIds: [],
            orderTransactionArray: [],
            payNowHistory: [],
            isLoading: true,
            isSuccess: true,
            transaction: null,
            refreshTime: 5000,
            refreshing: false,
            refreshHandler: null,
            amountToRefund: 0,
            descriptionToRefund: 'OTHER',
            checkedProduct: null,
            modalMode: false,
            orderLineItems: [],
            productsToRefund:[],
            refundReasons: [
                { value:"RMA", labelProperty: "reklamacja"},
                { value:"REFUND_BEFORE_14", labelProperty: "zwrot poniżej 14 dni od zakupu"},
                { value:"REFUND_AFTER_14", labelProperty: "zwrot powyżej 14 dni od zakupu"},
                { value:"OTHER", labelProperty: "inny powód"},
            ]
        };
    },

    computed: {
        orderRepository() {
            return this.repositoryFactory.create('order');
        },

        transactionRepository() {
            return this.repositoryFactory.create('order_transaction');
        },

        payNowRefundRepository(){
            return this.repositoryFactory.create('paynow_refund_history');
        },

        columns() {
            return [
            { property: 'input', label: 'Do zwrotu', rawData: true },
            { property: 'quantity', label: 'Ilość', rawData: true },
            { property: 'refundedQty', label: 'Zwrócono', rawData: true },
            { property: 'label', label: 'Nazwa', rawData: true },
            { property: 'unitPrice', label: 'Cena/sztukę', rawData: true },
            { property: 'totalPrice', label: 'Cena/całość', rawData: true },
            ]
        },
        columnsHistory() {
            return [
                { property: 'paynowStatus', label: 'Status', rawData: true },
                { property: 'refundAmount', label: 'Ilość', rawData: true },
                { property: 'refundId', label: 'PayNow ID', rawData: true },
                { property: 'createdAt', label: 'Data utworzenia', rawData: true },
            ]
        },
        columnsHistoryProduct() {
            return [
                { property: 'qty', label: 'Ilość'},
                { property: 'label', label: 'Nazwa'},
            ]
        },
        billingAddress() {
            const billingAddressId = this.order.billingAddressId;

            return this.order.addresses.get(billingAddressId);
        },

        shippingAddress() {
            return this.order.deliveries.last().shippingOrderAddress;
        },
    },

    watch: {
        '$route'() {
            this.createdComponent();
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        modalModeFalse(){
            this.modalMode = false;
        },
        calculateRefund(){
            this.amountToRefund= 0;
            this.productsToRefund = [];
            this.orderItems.forEach(item => {
                if(item.toRefund > item.quantity) {
                    item.toRefund = 0;
                    return this.createNotificationError({
                        title: 'Error',
                        message:  this.$tc('pay-now-order.notification.alert'),
                    });
                }
                this.amountToRefund+= item.toRefund*item.unitPrice;
                this.productsToRefund.push({ 'id': item.id, 'refundedQty': item.toRefund });
            })
        },
        refreshDataSource(){
            this.payNowHistory = [];
            this.listOfAmounts = [];
            this.listOfAmounts.push(this.order.price.totalPrice)
            const promiseArray =[];
            this.orderTransactionIds.forEach(id =>{
                  promiseArray.push(this.loadPayNowHistory(id));
            });
            Promise.all(promiseArray).then(result=>{
                result.forEach(payNowHistoryArray=>{
                    payNowHistoryArray.forEach(payNowHistory=>{
                        const createDate = payNowHistory.createdAt.slice(0,10) + ' ' + payNowHistory.createdAt.slice(11,19);
                        this.payNowHistory.push({
                            id:payNowHistory.id,
                            paynowStatus: payNowHistory.paynowStatus,
                            refundAmount: payNowHistory.refundAmount,
                            refundId: payNowHistory.refundId,
                            productList: payNowHistory.productList,
                            createdAt: createDate
                        })
                        if(payNowHistory.paynowStatus === "zwrot zaakceptowany"){
                            payNowHistory.productList.forEach(product=>{
                                this.succesfulProductsList.push({id:  Object.keys(product)[0], qty:  parseInt(Object.values(product)[0],10)});
                            })
                            this.listOfAmounts.push(payNowHistory.refundAmount);

                        }
                    });
                })
                this.compareLineItems();
            });
        },
        dataSource(){
            this.order.lineItems.forEach(item => this.orderItems.push(
                {
                    id: item.id,
                    quantity: item.quantity,
                    refundedQty: 0,
                    label: item.label,
                    unitPrice: item.price.unitPrice,
                    totalPrice: item.price.totalPrice,
                    toRefund: 0,
                }
            ));
            this.order.deliveries.forEach(delivery=>{
                if(delivery.shippingCosts.totalPrice){
                    this.orderItems.push({
                        id: delivery.id,
                        quantity: 1,
                        refundedQty: 0,
                        label: delivery.shippingMethod.name,
                        unitPrice: delivery.shippingCosts.totalPrice,
                        totalPrice: delivery.shippingCosts.totalPrice,
                        toRefund: 0,
                    });
                }
            })
            this.order.transactions.forEach(transaction => this.orderTransactionIds.push(
                transaction.id
            ));

            const promiseArray =[];
            this.orderTransactionIds.forEach(id =>{
                promiseArray.push(this.loadPayNowHistory(id));
            });
            Promise.all(promiseArray).then(result=>{
                result.forEach(payNowHistoryArray=>{
                    payNowHistoryArray.forEach(payNowHistory=>{
                        const createDate = payNowHistory.createdAt.slice(0,10) + ' ' + payNowHistory.createdAt.slice(11,19);
                        this.payNowHistory.push({
                            id:payNowHistory.id,
                            paynowStatus: payNowHistory.paynowStatus,
                            refundAmount: payNowHistory.refundAmount,
                            refundId: payNowHistory.refundId,
                            productList: payNowHistory.productList,
                            createdAt: createDate
                        })
                        if(payNowHistory.paynowStatus === "zwrot zaakceptowany"){
                            payNowHistory.productList.forEach(product=>{
                                this.succesfulProductsList.push({id:  Object.keys(product)[0], qty:  parseInt(Object.values(product)[0], 10)});
                            })
                            this.listOfAmounts.push(payNowHistory.refundAmount);

                        } else{
                            this.listofPaymentsIdToFetch.push(payNowHistory.refundId);
                        }
                    });

                })
                this.compareLineItems();
                this.fetchStatus();
                this.groupProducts();
                this.prepareRefoundedProducts();
            });

        },
        groupProducts(){
            const res = this.succesfulProductsList.reduce((a, b) =>
                a.set(b.id, (a.get(b.id) || 0) + Number(b.qty)), new Map);
            this.succesfulProductsList = Array.from(res, ([id, qty]) => ({ id, qty }));
        },
        prepareRefoundedProducts(){
            this.orderItems.forEach((item,index)=>{
                this.succesfulProductsList.forEach(refundedProducts=>{
                    if(item.id === refundedProducts.id){
                        item.refundedQty = refundedProducts.qty;
                    }
                })
            })
        },
        compareLineItems(){
            this.payNowHistory.forEach(history=>{
                history.productsToPass = [];
            })

            this.payNowHistory.forEach((history,index)=>{
                history.productList.forEach((value)=>{
                    let productId = Object.keys(value)[0]
                    let qty = Object.values(value)[0]
                    this.orderItems.forEach(item=>{
                        if(item.id === productId){
                            history.productsToPass.push({
                                'qty': qty,
                                'label': item.label,
                            })}
                        })
                })
            })
        },
        loadPayNowHistory(id){
            let payNowCriteria = new Criteria(1, 25);
            payNowCriteria.addFilter(Criteria.equals('transactionId', id));
            return this.payNowRefundRepository.search(payNowCriteria ,Context.api);
        },

        createdComponent() {
            this.loadData();
        },

        doRefund() {
            const that = this;
            if (this.amountToRefund > this.order.amountTotal) {
                this.refundAmountTooBig =true;

            } else if(this.amountToRefund < 1){
                this.refundAmountTooSmall =true;

            } else {
                this.PayNowOrderService.refundPayment(this.order.id, this.amountToRefund.toFixed(2), this.descriptionToRefund, this.productsToRefund)
                    .then(()=>{
                        this.createNotificationSuccess({
                            title: 'Succes',
                            message: this.$tc('pay-now-order.notification.success'),
                        });
                        this.refreshDataSource();
                        that.modalModeFalse();
                })
                    .catch(()=>{
                        this.createNotificationError({
                            title: 'Error',
                            message:  this.$tc('pay-now-order.notification.failure'),
                        });
                    })
            }
        },

        fetchStatus() {
            const promiseArray =[];
            this.listofPaymentsIdToFetch.forEach(id=>{
                promiseArray.push(this.PayNowOrderService.fetchStatus(id));
            })
            Promise.all(promiseArray).then(result=>{
                this.refreshDataSource();
            })
        },

        loadData() {
            const orderId = this.$route.params.id;
            this.loadOrder(orderId).then((order) => {
                this.order = order;
                this.isLoading = false;
                this.listOfAmounts.push(order.price.totalPrice)
                this.currency = order.currency.symbol
                this.dataSource();
                if(order.transactions[0].customFields !== null && order.transactions[0].customFields["paynowPaymentId"] !== null){
                    this.showPayNowTab = true;
                }
                this.isLoading = false;
            });

        },

        loadOrder(orderId) {

            const orderCriteria = new Criteria(1, 1);
            orderCriteria.addAssociation('addresses');
            orderCriteria.addAssociation('currency');
            orderCriteria.addAssociation('deliveries');
            orderCriteria.addAssociation('deliveries.shippingMethod')
            orderCriteria.addAssociation('transactions');
            orderCriteria.addAssociation('lineItems');
            orderCriteria.addAssociation('orderCustomer');

            return this.orderRepository.get(orderId, Context.api, orderCriteria);
        },
    },
});