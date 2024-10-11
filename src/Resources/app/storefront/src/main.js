import PayNowPaymentMethods from './payment-methods/pay-now-payment-methods.plugin';
import PayNowPaymentCheckPlugin from "./check-payment/pay-now-payment-check.plugin";

const PluginManager = window.PluginManager;

PluginManager.register('PayNowPaymentMethods', PayNowPaymentMethods, '[data-pay-now-payment-methods]');
PluginManager.register('PayNowPaymentCheckPlugin', PayNowPaymentCheckPlugin, '[data-paynow-payment-check]');

