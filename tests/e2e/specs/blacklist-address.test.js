/* eslint-disable jest/no-export, jest/no-disabled-tests, jest/expect-expect, jest/no-standalone-expect */

const {
    shopper,
    merchant,
    settingsPageSaveChanges,
    uiUnblocked
} = require( '@woocommerce/e2e-utils' );

const createProductAndEnableChequePayments = require( './createProductAndEnableChequePayments.beforeAll.js' );
const clearBlacklistSettings = require( './clearBlacklistSettings.beforeEach.js' );
const placeOrderBefore = require( './placeOrderBefore.beforeEach.js' );

const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );

let blacklistNoticeMessage;

describe('Blacklist Billing/Shipping Address Tests', () => {

    // Enable the cheque payment method and store the blacklistNoticeMessage for verifying blacklisting has succeeded.
    beforeAll(async () => {

        await createProductAndEnableChequePayments();

        // admin.php?page=wc-settings&tab=settings_tab_blacklists
        await merchant.openSettings('settings_tab_blacklists');

        let blacklistNoticeMessageHtmlElement = await page.$('#wmfo_black_list_message');
        blacklistNoticeMessage = await page.evaluate(element => element.textContent, blacklistNoticeMessageHtmlElement);

        await merchant.logout();
    });

    // Clear the blacklist settings, and confirm we can place an order without being blocked.
    beforeEach( async () => {

        await clearBlacklistSettings();
        await placeOrderBefore();
    });


    it('should block any with full matching address', async () => {

        await merchant.login();

        // admin.php?page=wc-settings&tab=settings_tab_blacklists
        await merchant.openSettings('settings_tab_blacklists');

        await expect(page).toFill('#wmfo_black_list_addresses', ' ');

        // The default billing address.
        await page.$eval('#wmfo_black_list_addresses', el => el.value = 'addr 1, addr 2,San Francisco, CA, 94107, US');

        // Save the changes
        await settingsPageSaveChanges();

        // Verify that settings have been saved
        await expect(page).toMatchElement('#wmfo_black_list_addresses', {text: 'addr 1, addr 2,San Francisco, CA, 94107, US'});

        await merchant.logout();

        await shopper.goToShop();

        await shopper.addToCartFromShopPage(simpleProductName);

        await shopper.goToCheckout();

        await shopper.fillBillingDetails(config.get('addresses.customer.billing'));

        await uiUnblocked();

        await expect(page).toClick('.wc_payment_method label', {text: 'Check'});

        await expect(page).toMatchElement('.payment_method_cheque', {text: 'Please send a check to Store Name, Store Street, Store Town, Store State / County, Store Postcode.'});

        await page.focus( 'button#place_order' );
        await page.click( 'button#place_order' );

        await expect(page).toMatchElement('.woocommerce-error', {text: blacklistNoticeMessage});

    });

    it('should block partial address – i.e. any with zip code 94107', async () => {

        await merchant.login();

        // admin.php?page=wc-settings&tab=settings_tab_blacklists
        await merchant.openSettings('settings_tab_blacklists');

        // 94107 is the zip code used in the default billing address (San Francisco).
        await page.$eval('#wmfo_black_list_addresses', el => el.value = '94107');

        // Save the changes
        await settingsPageSaveChanges();

        // Verify that settings have been saved
        await expect(page).toMatchElement('#wmfo_black_list_addresses', {text: /^94107$/});

        await merchant.logout();

        await shopper.goToShop();
        await shopper.addToCartFromShopPage(simpleProductName);
        await shopper.goToCheckout();

        await shopper.fillBillingDetails(config.get('addresses.customer.billing'));

        await uiUnblocked();
        await expect(page).toClick('.wc_payment_method label', {text: 'Check'});
        await expect(page).toMatchElement('.payment_method_cheque', {text: 'Please send a check to Store Name, Store Street, Store Town, Store State / County, Store Postcode.'});

        await page.focus( 'button#place_order' );
        await page.click( 'button#place_order' );

        await expect(page).toMatchElement('.woocommerce-error', {text: blacklistNoticeMessage});

    });

});
