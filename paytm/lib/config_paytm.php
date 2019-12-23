<?php

/*

  - Use PAYTM_ENVIRONMENT as 'PROD' if you wanted to do transaction in production environment else 'TEST' for doing transaction in testing environment.
  - Change the value of PAYTM_MERCHANT_KEY constant with details received from Paytm.
  - Change the value of PAYTM_MERCHANT_MID constant with details received from Paytm.
  - Change the value of PAYTM_MERCHANT_WEBSITE constant with details received from Paytm.
  - Above details will be different for testing and production environment.

 */
define('PAYTM_ENVIRONMENT', 'DEV'); // PROD

$PAYTM_REFUND_URL = 'https://securegw-stage.paytm.in/refund/apply';
$PAYTM_REFUND_STATUS_URL = 'https://securegw-stage.paytm.in/v2/refund/status';
//$PAYTM_STATUS_QUERY_NEW_URL='https://securegw-stage.paytm.in/order/status';
$PAYTM_STATUS_QUERY_NEW_URL = 'https://securegw-stage.paytm.in/merchant-status/getTxnStatus';
//$PAYTM_TXN_URL='https://securegw-stage.paytm.in/order/process';
$PAYTM_TXN_URL = 'https://securegw-stage.paytm.in/theia/processTransaction';
$PAYTM_MERCHANT_KEY = 'R0EzSi@nDuJrDHwY';
$PAYTM_MERCHANT_MID = 'ofyfFN01777110091943';
$PAYTM_MERCHANT_WEBSITE_APP = 'APPSTAGING';
$CHANNEL_ID_APP = 'WAP';
$PAYTM_MERCHANT_WEBSITE = 'WEBSTAGING';
$CHANNEL_ID = 'WEB';
$INDUSTRY_TYPE_ID = 'Retail';
if (PAYTM_ENVIRONMENT == 'PROD') {
    $PAYTM_REFUND_URL = 'https://securegw.paytm.in/refund/apply';
    $PAYTM_REFUND_STATUS_URL = 'https://securegw.paytm.in/v2/refund/status';
    $PAYTM_STATUS_QUERY_NEW_URL = 'https://securegw.paytm.in/merchant-status/getTxnStatus';
    $PAYTM_TXN_URL = 'https://securegw.paytm.in/theia/processTransaction';
    $PAYTM_MERCHANT_KEY = 'D&Q!OSmmv1nujN9&';
    $PAYTM_MERCHANT_MID = 'DRSPAN35675287627251';
    $PAYTM_MERCHANT_WEBSITE_APP = 'APPSTAGING';
    $CHANNEL_ID_APP = 'WAP';
    $PAYTM_MERCHANT_WEBSITE = 'WEBSTAGING';
    $CHANNEL_ID = 'WEB';
    $INDUSTRY_TYPE_ID = 'Retail';
}
define('PAYTM_MERCHANT_KEY', $PAYTM_MERCHANT_KEY); //Change this constant's value with Merchant key downloaded from portal
define('PAYTM_MERCHANT_MID', $PAYTM_MERCHANT_MID); //Change this constant's value with MID (Merchant ID) received from Paytm
define('PAYTM_MERCHANT_WEBSITE', $PAYTM_MERCHANT_WEBSITE); //Change this constant's value with Website name received from Paytm
define('CHANNEL_ID', $CHANNEL_ID);
define('PAYTM_MERCHANT_WEBSITE_APP', $PAYTM_MERCHANT_WEBSITE_APP); //Change this constant's value with Website name received from Paytm
define('CHANNEL_ID_APP', $CHANNEL_ID_APP);
define('INDUSTRY_TYPE_ID', $INDUSTRY_TYPE_ID);

define('PAYTM_STATUS_QUERY_URL', $PAYTM_STATUS_QUERY_NEW_URL);
define('PAYTM_STATUS_QUERY_NEW_URL', $PAYTM_STATUS_QUERY_NEW_URL);
define('PAYTM_TXN_URL', $PAYTM_TXN_URL);
define('PAYTM_REFUND_URL', $PAYTM_REFUND_URL);
define('PAYTM_REFUND_STATUS_URL', $PAYTM_REFUND_STATUS_URL);
?>
