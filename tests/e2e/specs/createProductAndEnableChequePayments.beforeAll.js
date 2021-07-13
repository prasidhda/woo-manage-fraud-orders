const {
    merchant,
    settingsPageSaveChanges,
    createSimpleProduct,
    setCheckbox,
    verifyCheckboxIsSet
} = require( '@woocommerce/e2e-utils' );

const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );

const createProductAndEnableChequePayments = async ( dispatch ) => {

    await merchant.login();

    // Create a product.
    await createSimpleProduct();

    // Enable a payment method.
    await merchant.openSettings('checkout', 'cheque');
    await setCheckbox('#woocommerce_cheque_enabled');
    await settingsPageSaveChanges();

    // Verify that settings have been saved
    await verifyCheckboxIsSet('#woocommerce_cheque_enabled');

};

module.exports = createProductAndEnableChequePayments;
