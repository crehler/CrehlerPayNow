import './extension/sw-order';
import './extension/sw-order-list';
import './page/pay-now-order-tab';


const { Module } = Shopware;

Module.register('pay-now-order', {
    type: 'plugin',
    name: 'PayNowOrder',
    title: 'pay-now-order.general.title',
    description: 'pay-now-order.general.description',
    version: '1.0.0',
    targetVersion: '1.0.0',

    routeMiddleware(next, currentRoute) {
        if (currentRoute.name === 'sw.order.detail') {
            currentRoute.children.push({
                component: 'pay-now-order-tab',
                name: 'pay-now-order.payment.detail',
                isChildren: true,
                path: '/sw/order/pay-now-order/detail/:id/:transactionId',
            });
        }

        next(currentRoute);
    },
});
