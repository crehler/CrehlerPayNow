window.PluginManager.register(
    "PayNowPaymentMethods",
    () => import("./payment-methods/pay-now-payment-methods.plugin"),
    "[data-pay-now-payment-methods]",
);

window.PluginManager.register(
    "PayNowPaymentCheckPlugin",
    () => import("./check-payment/pay-now-payment-check.plugin"),
    "[data-paynow-payment-check]",
);