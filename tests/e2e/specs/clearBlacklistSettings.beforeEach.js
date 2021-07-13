const {
    merchant,
    settingsPageSaveChanges
} = require( '@woocommerce/e2e-utils' );

const config = require( 'config' );

const clearBlacklistSettings = async ( dispatch ) => {

    await merchant.login();

    // admin.php?page=wc-settings&tab=settings_tab_blacklists
    await merchant.openSettings('settings_tab_blacklists');

    // Clear existing settings.
    await page.$eval('#wmfo_black_list_names', el => el.value = '');
    await page.$eval('#wmfo_black_list_phones', el => el.value = '');
    await page.$eval('#wmfo_black_list_emails', el => el.value = '');
    await page.$eval('#wmfo_black_list_email_domains', el => el.value = '');
    await page.$eval('#wmfo_black_list_ips', el => el.value = '');
    await page.$eval('#wmfo_black_list_addresses', el => el.value = '');

    // Save the changes
    await settingsPageSaveChanges();

    // Verify that settings have been saved
    await expect(page).toMatchElement('#wmfo_black_list_names', {text: /^$/});
    await expect(page).toMatchElement('#wmfo_black_list_phones', {text: /^$/});
    await expect(page).toMatchElement('#wmfo_black_list_emails', {text: /^$/});
    await expect(page).toMatchElement('#wmfo_black_list_email_domains', {text: /^$/});
    await expect(page).toMatchElement('#wmfo_black_list_ips', {text: /^$/});
    await expect(page).toMatchElement('#wmfo_black_list_addresses', {text: /^$/});

    await merchant.logout();

};

module.exports = clearBlacklistSettings;
