Version 0.3.0

©2014 GoCoin Holdings Limited and GoCoin International Group of companies hereby grants you permission to utilize a copy of this software and documentation in connection with your use of the GoCoin.com service subject the the published Terms of Use and Privacy Policy published on the site and subject to change from time to time at the discretion of GoCoin.<br><br>

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE DEVELOPERS OR AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.<br><br>

## Using the Official GoCoin WooCommerce Plugin
When a shopper chooses the GoCoin payment method and places their order, they will be redirected to https://gateway.gocoin.com to pay.  
GoCoin will send a notification to your server which this plugin handles. Then the customer will be redirected to an order summary page.  

** Version 0.3.0 of this plugin recommends at least Wordpress Version 4.0 and WooCommerce Version 2.2. Earlier versions may work, but have not been fully tested. **

### Using the plugin
All that is required for this plugin to function is your GoCoin Merchant ID and an API Key. You can obtain these from the [GoCoin Dashboard](https://dashboard.gocoin.com) in the "Developers" section. Copy and paste these values into the plugin settings and click "Save."

###Version 0.3.0

##### CryptoCurrency Support
This plugin supports all currencies that GoCoin supports

##### Fiat Currency Support
Your store can be used in with of the available currencies on WooCommerce. 

##### Order Status
When an order is created, its status will be 'pending.' (This is a change from previous versions, which immediately placed the order into 'on-hold.')

When a payment is detected, whether the invoice is paid in full or underpaid, the order status will be updated to on-hold.

When all payments are paid in full and confirmed on the blockchain (Bitcoin Network), the order status will be 'processing.'

Any invalid payment will result in a status of 'failed'







  
  
