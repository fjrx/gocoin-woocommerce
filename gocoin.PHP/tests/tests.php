<?php
/**
 * GoCoin Api Unit TestCase
 * A PHP Unit Test case to test GoCoin Api used by PHPUnit
 *
 * @author Roman A <future.roman3@gmail.com>
 * @version 0.1.1
 *
 */
 
require_once('../src/api.php');
require_once('../src/auth.php');
require_once('../src/client.php');
 
class PHPGocoinTestCase extends PHPUnit_Framework_TestCase {
    const CLIENT_ID = "6f8d9bb9a577fa25e8187637c50d3c3df162599d7442e958b435d82b50c54c45";
    const CLIENT_SECRET = "f44ac8d2dbd8ab0337a0f490e4ac2a97dd192bc35496a5616eac8a807185d30d";
    private $access_token = "063514757f57e3454bb28437e7f716d75182c82aa29817e06c63203b0aac89e8";
    private $expired_token = "063514757f57e3454bb28437e7f716d75182c82aa29817e06c63203b0aac89e8";
    
    public function testConstructor() {
        $headers = array(
            "'Content-Type' => 'application/json'",
            "'Content-Type' => 'application/text'"
        );

        $client = new Client( array(        
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'scope' => "user_read_write+invoice_read_write",
        )); 
           
        $this->assertEquals($client->getClientId(), self::CLIENT_ID,
                            'Expect the Client ID to be set.');
        $this->assertEquals($client->getClientSecret(), self::CLIENT_SECRET,
                            'Expect the Client secret to be set.');
    }

    public function testSetClientId() {
        $client = new Client( array(        
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
        ));
        $client->setClientId('dummy');
        $this->assertEquals($client->getClientId(), 'dummy',
                            'Expect the Client ID to be dummy.');
    }

    public function testSetClientSecret() {
        $client = new Client( array(        
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
        ));
        $client->setClientSecret('dummy');
        $this->assertEquals($client->getClientSecret(), 'dummy',
                            'Expect the Client secret to be dummy.');
    }

    public function testSetToken() {
        $client = new Client( array(        
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
        ));

        $client->setToken('saltydog');
        $this->assertEquals($client->getToken(), 'saltydog',
                            'Expect installed access token to remain \'saltydog\'');
    }

    public function testGetCurrentURL() {
        $client = new Client( array(        
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
        ));

        $_SERVER['HTTP_HOST'] = 'www.test.com';
        $_SERVER['REQUEST_URI'] = '/unit-tests.php?one=one&two=two&three=three';
        $current_url = $client->get_current_url();
        $this->assertEquals(
          'http://www.test.com/unit-tests.php?one=one&two=two&three=three',
          $current_url,
          'getCurrentUrl function is changing the current URL');
      
        $_SERVER['HTTP_HOST'] = 'www.test.com';
        $_SERVER['REQUEST_URI'] = '/unit-tests.php?one=&two=&three=';
        $current_url = $client->get_current_url();
        $this->assertEquals(
          'http://www.test.com/unit-tests.php?one=&two=&three=',
          $current_url,
          'getCurrentUrl function is changing the current URL');

        $_SERVER['HTTP_HOST'] = 'www.test.com';
        $_SERVER['REQUEST_URI'] = '/unit-tests.php?one&two&three';
        $current_url = $client->get_current_url();
        $this->assertEquals(
          'http://www.test.com/unit-tests.php?one&two&three',
          $current_url,
          'getCurrentUrl function is changing the current URL');
    }

    public function testGetAuthURL() {
        $client = new Client( array(        
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'scope' => "user_read_write",
        ));

        $_SERVER['HTTP_HOST'] = 'www.test.com';
        $_SERVER['REQUEST_URI'] = '/login.php';
        $login_url = parse_url($client->get_auth_url());
        $this->assertEquals($login_url['host'], $client->options['dashboard_host']);
        $this->assertEquals($login_url['path'], '/auth');
        $expected_login_params = array('response_type' => "code",
                'client_id' => self::CLIENT_ID,
                'redirect_uri' => "http://www.test.com/login.php",
                'scope' => "user_read_write"
                );
        $query_map = array();
        parse_str($login_url['query'], $query_map);
        $this->assertEquals($expected_login_params, $query_map);
    }

    public function testGetUser() {
        $client = new Client( array(        
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'scope' => "user_read_write",
        ));
        
        $client->setToken($this->access_token);
        $class_name = get_class($client->api->user);
        $this->assertEquals($class_name, 'User',
                            'Expect class.');
    }
    
    public function testGetInvoices() {
        $client = new Client( array(        
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'scope' => "invoice_read_write",
        ));
        
        $client->setToken($this->access_token);
        $class_name = get_class($client->api->invoices);
        $this->assertEquals($class_name, 'Invoices',
                            'Expect class.');
    }
    
    public function testGetMerchant() {
        $client = new Client( array(        
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'scope' => "merchant_read_write",
        ));
        
        $client->setToken($this->access_token);
        $class_name = get_class($client->api->merchant);
        $this->assertEquals($class_name, 'Merchant',
                            'Expect class.');
    }
    
    public function testGetAccounts() {
        $client = new Client( array(        
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'scope' => "user_read_write",
        ));
        
        $client->setToken($this->access_token);
        $class_name = get_class($client->api->accounts);
        $this->assertEquals($class_name, 'Accounts',
                            'Expect class.');
    }
    
    public function testGetApiUrl() {
       $client = new Client( array(        
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
        ));
       $headers = array( "Content-Type"=> "application/json",
                         "Authorization"=> "Bearer ". $this->access_token);
       $options = array();
       $url = $client->get_api_url($options);
       $test_url = "https://api.gocoin.com/api/v1";
       $this->assertEquals($url, $test_url,
                            'Expect url'); 
    }
    
    public function testCurl() {
        $client = new Client( array(        
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'scope' => "user_read_write",
        ));
        
        $client->setToken($this->expired_token);
        $options = array('secure' => false);
        $url = $client->get_api_url($options)."/user";
        print_r($url);
        $headers = array( "Content-Type"=> "application/json",
                         "Authorization"=> "Bearer ". $this->expired_token);
        
        $curl_result = $client->do_request($url, false, $headers, "POST");
        $this->assertEquals($curl_result==false , true,
                            'Expect Curl result');
    }
    
    public function testSetEror() {
       $client = new Client( array(        
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'scope' => "user_read_write",
        ));
       $err_msg = "Test Error Message";
       $client->setError($err_msg);
       $this->assertEquals($client->getError(), $err_msg,
                            'Expect Error Message');
    }
}
?>