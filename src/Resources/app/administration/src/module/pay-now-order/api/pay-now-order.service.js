const { Application } = Shopware;
const ApiService = Shopware.Classes.ApiService;

class PayNowOrderService extends ApiService {
    constructor(httpClient, loginService) {
        super(httpClient, loginService);
    }

    testApi(){
        const apiRoute = `/paynowpayment/test-api`;

        return this.httpClient
            .post(
                apiRoute,
                {},
                {
                    headers: this.getBasicHeaders(),
                }
            )

    }

    refundPayment(orderId, amountToRefund, descriptionOfRefund, productsToRefund) {

        const apiRoute = `/paynowpayment/refund-payment`;

        return this.httpClient
            .post(
                apiRoute,
                {
                    orderId: orderId,
                    amountToRefund: amountToRefund,
                    descriptionOfRefund: descriptionOfRefund,
                    productsToRefund: productsToRefund,
                },
                {
                    headers: this.getBasicHeaders(),
                }
            )
    }

    fetchStatus(refundId){
        const apiRoute = `/paynowpayment/fetch-refund-payment`;

        return this.httpClient
            .post(apiRoute, {refundId: refundId}, {headers: this.getBasicHeaders()})
    }
}


Application.addServiceProvider('PayNowOrderService', (container) => {
    const initContainer = Application.getContainer('init');

    return new PayNowOrderService(initContainer.httpClient, container.loginService);
});