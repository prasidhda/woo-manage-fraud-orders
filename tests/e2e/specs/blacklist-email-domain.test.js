const {
    shopper,
    merchant,
    settingsPageSaveChanges,
    uiUnblocked
} = require( '@woocommerce/e2e-utils' );

const createProductAndEnableChequePayments = require( './createProductAndEnableChequePayments.beforeAll.js' );
const clearBlacklistSettings = require( './clearBlacklistSettings.beforeEach.js' );
const placeOrderBefore = require( './placeOrderBefore.beforeEach.js' );

// import config from 'config';
const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );

let blacklistNoticeMessage;

describe('BlacklistsTests by email domain', () => {
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

    it('should block all email address from example.com', async () => {

        await merchant.login();

        // admin.php?page=wc-settings&tab=settings_tab_blacklists
        await merchant.openSettings('settings_tab_blacklists');

        // 172.22.0.1
        await expect(page).toFill('#wmfo_black_list_email_domains', 'example.com');

        // Save the changes
        await settingsPageSaveChanges();

        await merchant.logout();

        await shopper.goToShop();
        await shopper.addToCartFromShopPage(simpleProductName);
        await shopper.goToCheckout();

        await shopper.fillBillingDetails(config.get('addresses.customer.billing'));

        await uiUnblocked();

        await expect(page).toClick('.wc_payment_method label', {text: 'Check'});
        await expect(page).toMatchElement('.payment_method_cheque', {text: 'Please send a check to Store Name, Store Street, Store Town, Store State / County, Store Postcode.'});

        expect(shopper.placeOrder()).rejects.toThrow();

        await expect(page).toMatchElement('.woocommerce-error', {text: blacklistNoticeMessage});

    });

});
