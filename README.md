Version 0.1.0

## Using the Official GoCoin WooCommerce Plugin
When a shopper chooses the GoCoin payment method and places their order, they will be redirected to gateway.GoCoin.com to pay.  
GoCoin will send a notification to your server which this plugin handles.  Then the customer will be redirected to n order summary page.  

The order status in the admin panel will be "on-hold" when the order is placed and "processing" if payment has been confirmed. 

#### Important Note: 
Version 0.1.0 of this plugin only supports US Dollars as the Base Currency. Please make sure your WooCommerce Currency is set to US Dollars. Support for additional currencies is coming soon. 

### 1. Installation
[Wordpress](http://www.wordpress.org) and the [WooCommerce extension](http://wordpress.org/plugins/woocommerce/) must be installed before installing this plugin.

Copy this folder and its contents into your plugins directory (wordpress/wp-content/plugins)

### 2. Setting up an application.
1) [Enable the GoCoin Hosted Payment Gateway](http://www.gocoin.com/docs/hosted_gateway)<br>
2) Create an application in the [GoCoin Dashboard](https://dashboard.gocoin.com)

##### Navigate to the Applications menu from the dashboard home<br>
![applications](https://dl.dropboxusercontent.com/spa/pvghiam459l0yh2/rj1pj_-a.png)

##### Create a new application <br>
![applications home](https://dl.dropboxusercontent.com/spa/pvghiam459l0yh2/s61g2gn8.png)<br>
Make sure your redirect_uri is equal to:

```
https://YOUR_DOMAIN/wp-admin/admin.php
```

![new application](https://dl.dropboxusercontent.com/spa/pvghiam459l0yh2/d5tqf3zq.png)<br>
Make sure to use https for a production site - its part of the OAuth standard.

More information on creating GoCoin connected applications can be found [here](http://www.gocoin.com/docs/create_application)

### 3. Configuration

1. In the Admin panel click Plugins, then click Activate under GoCoin WooCommerce. <br><br>
![activate](https://dl.dropboxusercontent.com/spa/pvghiam459l0yh2/eleb5ers.png)<br>
2. In WooCommerce > Settings, make sure Currency is set to US Dollars <br><br>
![usd](https://dl.dropboxusercontent.com/spa/pvghiam459l0yh2/j4a-5r70.png)<br>

3. Disable 'Order Processing' email by unchecking the box under Emails > Processing Order <br>

4. Go to WooCommerce > Settings > Payment Gateways > GoCoin <br>
  a) Enable GoCoin by Checking the box <br>
  b) Obtain a token:<br>
    i) Set client key and client id. <br>
    ii) BEFORE PROCEEDING CLICK "Save Changes" <br>
    <br>
![step 3](https://dl.dropboxusercontent.com/spa/pvghiam459l0yh2/2duixbff.png)
 
4. MAKE SURE YOU SAVED YOUR CLIENT ID AND SECRET BEFORE COMPLETING THIS STEP. Click "Get Access token from GoCoin" button. You will be redirected to dashboard.gocoin.com. Allow permission to access your info then you will be redirected back to this page. The Access Token will have populated the field.  
5. SAVE AGAIN. You are now ready to accept payments with GoCoin!






	
	