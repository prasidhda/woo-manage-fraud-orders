/* eslint-disable jest/no-export, jest/no-disabled-tests, jest/expect-expect, jest/no-standalone-expect */

const {
    shopper,
    merchant,
    uiUnblocked
} = require( '@woocommerce/e2e-utils' );

const createProductAndEnableChequePayments = require( './createProductAndEnableChequePayments.beforeAll.js' );
const clearBlacklistSettings = require( './clearBlacklistSettings.beforeEach.js' );

const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );

var baseUrl = config.get('url');

describe('Should add order details to blacklists when order action is run', () => {

    // Enable the cheque payment method and store the blacklistNoticeMessage for verifying blacklisting has succeeded.
    beforeAll(async () => {
        await createProductAndEnableChequePayments();
    });

    // Clear the blacklist settings, and confirm we can place an order without being blocked.
    beforeEach( async () => {
        await clearBlacklistSettings();
    });

    it('should add order details to blacklists when order action is run', async () => {

        await shopper.goToShop();

        await shopper.addToCartFromShopPage(simpleProductName);

        await shopper.goToCheckout();

        await shopper.fillBillingDetails(config.get('addresses.customer.billing'));

        await uiUnblocked();

        await expect(page).toClick('.wc_payment_method label', {text: 'Check'});

        await expect(page).toMatchElement('.payment_method_cheque', {text: 'Please send a check to Store Name, Store Street, Store Town, Store State / County, Store Postcode.'});

        await shopper.placeOrder();

        // Get order ID from the order received html element on the page
        let orderReceivedHtmlElement = await page.$('.woocommerce-order-overview__order.order');
        let orderReceivedText = await page.evaluate(element => element.textContent, orderReceivedHtmlElement);
        let orderId = orderReceivedText.split(/(\s+)/)[6].toString();

        await merchant.login();

        // http://localhost:8084/wp-admin/post.php?post=309&action=edit
        let newOrderPage = baseUrl + 'wp-admin/post.php?post='+orderId+'&action=edit';

        console.log( newOrderPage );

        await page.goto(newOrderPage, {
            waitUntil: 'networkidle0'
        });

        // Customer IP: 172.22.0.1
        let orderIpElement = await page.$('.woocommerce-Order-customerIP');
        let orderIp = await page.evaluate(element => element.textContent, orderIpElement);

        await page.select('#actions > select', 'black_list_order');

        await page.focus( 'button.save_order' );
        await page.click( 'button.save_order' );

        jest.setTimeout(500);

        // admin.php?page=wc-settings&tab=settings_tab_blacklists
        await merchant.openSettings('settings_tab_blacklists');

        // Verify that entries have been added to the blacklists
        await expect(page).toMatchElement('#wmfo_black_list_names', {text: 'John Doe'});
        await expect(page).toMatchElement('#wmfo_black_list_phones', {text: '123456789'});
        await expect(page).toMatchElement('#wmfo_black_list_emails', {text: 'john.doe@example.com'});
        await expect(page).toMatchElement('#wmfo_black_list_ips', {text: orderIp});
        await expect(page).toMatchElement('#wmfo_black_list_addresses', {text: 'addr 1,addr 2,San Francisco,CA,94107,US'});

    });

});
