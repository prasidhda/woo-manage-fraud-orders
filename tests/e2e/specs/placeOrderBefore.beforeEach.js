const {
    shopper,
    uiUnblocked
} = require( '@woocommerce/e2e-utils' );

const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );

const placeOrderBefore = async ( dispatch ) => {

    // Confirm we can place an order as normal before configuring the settings and proceeding with the test.
    await shopper.goToShop();
    await shopper.addToCartFromShopPage(simpleProductName);
    await shopper.goToCheckout();

    await shopper.fillBillingDetails(config.get('addresses.customer.billing'));

    await uiUnblocked();

    await expect(page).toClick('.wc_payment_method label', {text: 'Check'});
    await expect(page).toMatchElement('.payment_method_cheque', {text: 'Please send a check to Store Name, Store Street, Store Town, Store State / County, Store Postcode.'});

    await uiUnblocked();

    await shopper.placeOrder();
};

module.exports = placeOrderBefore;
