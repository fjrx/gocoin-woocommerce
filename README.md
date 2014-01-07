2013 GoCoin

Installation
------------
Copy this folder and its contents into your plugins directory.

Configuration
-------------
1. In the Admin panel click Plugins, then click Activate under GoCoin Woocommerce.
2. In Admin panel click Woocommerce > Settings > Payment Gateways > GoCoin.
	1). Set client key and client id.
	2). input access token or click "Get Access token from GoCoin" button. ( You will be redirected to dashboard.GoCoin.com. Allow permission to access your info then you will be redirected back to this page). Note: Before you click "Get Access token from GoCoin" button, please save client id and secret key first.

Usage
-----
When a shopper chooses the GoCoin payment method and places their order, they will be redirected to gateway.GoCoin.com to pay.  
GoCoin will send a notification to your server which this plugin handles.  Then the customer will be redirected to n order summary page.  

The order status in the admin panel will be "on-hold" when the order is placed and "processing" if payment has been confirmed. 

	
	