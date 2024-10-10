import template from './sw-order-list.html.twig'

const { Component } = Shopware;

Component.override('sw-order-list', {
    template,

    methods: {
         getOrderColumns() {
             let columns = this.$super('getOrderColumns');
             columns.splice(5,0,{
                 property: 'orderRefund',
                 label: this.$tc('pay-now-order.general.orderListLabel'),
                 align: 'right',
                 allowResize: true,
             })

             return columns;
         },
    }

});