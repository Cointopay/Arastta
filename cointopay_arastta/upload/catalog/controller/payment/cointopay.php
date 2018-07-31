<?php


class ControllerPaymentCointopay extends Controller {

	public function index() 
	{

		$data['button_confirm'] = $this->language->get('button_confirm');

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$data['action'] = $this->url->link('payment/cointopay/sendCointopay', '', '');

		$data['sid'] = $this->config->get('cointopay_account');
		$data['currency_code'] = $order_info['currency_code'];
		$data['total'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		$data['cart_order_id'] = $this->session->data['order_id'];
		$data['card_holder_name'] = $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'];
		$data['street_address'] = $order_info['payment_address_1'];
		$data['city'] = $order_info['payment_city'];

		if ($order_info['payment_iso_code_2'] == 'US' || $order_info['payment_iso_code_2'] == 'CA') {
			$data['state'] = $order_info['payment_zone'];
		} else {
			$data['state'] = 'XX';
		}

		$data['zip'] = $order_info['payment_postcode'];
		$data['country'] = $order_info['payment_country'];
		$data['email'] = $order_info['email'];
		$data['phone'] = $order_info['telephone'];

		if ($this->cart->hasShipping()) {
			$data['ship_street_address'] = $order_info['shipping_address_1'];
			$data['ship_city'] = $order_info['shipping_city'];
			$data['ship_state'] = $order_info['shipping_zone'];
			$data['ship_zip'] = $order_info['shipping_postcode'];
			$data['ship_country'] = $order_info['shipping_country'];
		} else {
			$data['ship_street_address'] = $order_info['payment_address_1'];
			$data['ship_city'] = $order_info['payment_city'];
			$data['ship_state'] = $order_info['payment_zone'];
			$data['ship_zip'] = $order_info['payment_postcode'];
			$data['ship_country'] = $order_info['payment_country'];
		}

		$data['products'] = array();

		$products = $this->cart->getProducts();

		foreach ($products as $product) {
			$data['products'][] = array(
				'product_id'  => $product['product_id'],
				'name'        => $product['name'],
				'description' => $product['name'],
				'quantity'    => $product['quantity'],
				'price'       => $this->currency->format($product['price'], $order_info['currency_code'], $order_info['currency_value'], false)
			);
		}

		if ($this->config->get('cointopay_test')) {
			$data['demo'] = 'Y';
		} else {
			$data['demo'] = '';
		}

		if ($this->config->get('cointopay_display')) {
			$data['display'] = 'Y';
		} else {
			$data['display'] = '';
		}

		$data['lang'] = $this->session->data['language'];

		$data['return_url'] = $this->url->link('payment/cointopay/callback', '', 'SSL');

		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/cointopay.tpl')) {
			return $this->load->view($this->config->get('config_template') . '/template/payment/cointopay.tpl', $data);
		} else {
			return $this->load->view('default/template/payment/cointopay.tpl', $data);
		}
	}
    public function sendCointopay()
    {
    	$this->load->model('checkout/order');
    	$callbackUrl = $this->url->link('payment/cointopay/callback', '', 'SSL');
    	$merchantID = $this->config->get('cointopay_account');
    	$securityCode = $this->config->get('cointopay_secret');
    	// order data
    	$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
    	
    	$params = array( 
        "authentication:1",
        'cache-control: no-cache',
        );

		$ch = curl_init();
		curl_setopt_array($ch, array(
		CURLOPT_URL => 'https://app.cointopay.com/MerchantAPI?Checkout=true',
		//CURLOPT_USERPWD => $this->apikey,
		CURLOPT_POSTFIELDS => 'SecurityCode='.$securityCode.'&MerchantID='.$merchantID.'&Amount=' . number_format($order_info['total'], 2, '.', '').'&AltCoinID=1&output=json&inputCurrency=USD&CustomerReferenceNr='.$order_info['order_id'].'&transactionconfirmurl='.$callbackUrl.'&transactionfailurl='.$callbackUrl,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_HTTPHEADER => $params,
		CURLOPT_USERAGENT => 1,
		CURLOPT_HTTPAUTH => CURLAUTH_BASIC
		)
		);
		$redirect = curl_exec($ch);
		$results = json_decode($redirect);
		if($results->RedirectURL)
		{
		   //fn_create_payment_form($results->RedirectURL, '', 'Cointopay', false);
		    header("Location: ".$results->RedirectURL."");
		}
		echo $redirect;
		exit;
    }
	public function callback() {
		//http://localhost/arastta/index.php?route=payment/cointopay/callback&CustomerReferenceNr=2&TransactionID=230828&status=paid&notenough=1&ConfirmCode=IKTCELAMS0JRX8CMEGPNBGKRG6ZA-JKRMBVZ-UBP9SU&AltCoinID=1&MerchantID=13659&CoinAddressUsed=3C9fxEdTLmhYZZkCDhy5xuumWzc3v24JCB&SecurityCode=-1132003738&inputCurrency=USD
		$paymentStatus = isset($_GET['status']) ? $_GET['status'] : 'failed'; 
        $notEngough = isset($_GET['notenough']) ? $_GET['notenough'] : '2';
        $transactionID = isset($_GET['TransactionID']) ? $_GET['TransactionID'] : '';
        $orderID = isset($_GET['CustomerReferenceNr']) ? $_GET['CustomerReferenceNr'] : '';
        // load order model
		$this->load->model('checkout/order');
       	$order_info = $this->model_checkout_order->getOrder($orderID);

        if(isset($_GET['ConfirmCode']))
        {
           	$data = [ 
		       			'mid' => $this->config->get('cointopay_account') , 
		       			'TransactionID' => $_GET['TransactionID'] ,
		       			'ConfirmCode' => $_GET['ConfirmCode'] 
           			];
           	$response = $this->validateOrder($data);
       
           	if($response->Status !== $_GET['status'])
           	{
           		echo "We have detected different order status. Your order has been halted.";
           		exit;
           	}
           	if($response->CustomerReferenceNr == $_GET['CustomerReferenceNr'])
           	{
           		//if paid 
		        if($paymentStatus == 'paid' && $notEngough == '0')
		        {
		            $order_status = 5;
		            $comment = "Successfully paid!!";
		            $this->model_checkout_order->addOrderHistory($orderID, $order_status, $comment);
		        }
		   
		        else if ($paymentStatus == 'paid' || $notEngough == '1')
		        {
		            $order_status = 15;
		            $comment = "Low balance!!";
		            $this->model_checkout_order->addOrderHistory($orderID, $order_status, $comment);
		        }
		        elseif ($paymentStatus == 'failed')
		        {
		            $order_status = 10;
		            $comment = "Transaction failed";
		            $this->model_checkout_order->addOrderHistory($orderID, $order_status, $comment);
		        }
		        else
		        {
		            $order_status = 10;
		            $comment = "Transaction failed";
		            $this->model_checkout_order->addOrderHistory($orderID, $order_status, $comment);
		        }
				
					// We can't use $this->response->redirect() here, because of 2CO behavior. It fetches this page
					// on behalf of the user and thus user (and his browser) see this as located at cointopay.com
					// domain. So user's cookies are not here and he will see empty basket and probably other
					// weird things.

					echo '<html>' . "\n";
					echo '<head>' . "\n";
					echo '  <meta http-equiv="Refresh" content="0; url=' . $this->url->link('checkout/success') . '">' . "\n";
					echo '</head>' . "\n";
					echo '<body>' . "\n";
					echo '  <p>Please follow <a href="' . $this->url->link('checkout/success') . '">link</a>!</p>' . "\n";
					echo '</body>' . "\n";
					echo '</html>' . "\n";
					exit();
           	} 
           	else
           	{
           		echo "We have detected changes in order info. Your order has been halted.";
           		exit;
           	}
        }
	}

	public function calculateRFC2104HMAC($data, $key) 
	{
    	// compute the hmac on input data bytes, make sure to set returning raw hmac to be true
    	$rawHmac = hash_hmac("sha1", $data, $key, true);
    	// base64-encode the raw hmac
    	return base64_encode($rawHmac);
    }

    function  validateOrder($data)
    {
    	//$this->pp($data);
    	//https://cointopay.com/v2REAPI?MerchantID=14351&Call=QA&APIKey=_&output=json&TransactionID=230196&ConfirmCode=YGBMWCNW0QSJVSPQBCHWEMV7BGBOUIDQCXGUAXK6PUA
    	$params = array( 
        "authentication:1",
        'cache-control: no-cache',
        );

		$ch = curl_init();
		curl_setopt_array($ch, array(
		CURLOPT_URL => 'https://app.cointopay.com/v2REAPI?',
		//CURLOPT_USERPWD => $this->apikey,
		CURLOPT_POSTFIELDS => 'MerchantID='.$data['mid'].'&Call=QA&APIKey=_&output=json&TransactionID='.$data['TransactionID'].'&ConfirmCode='.$data['ConfirmCode'],
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_HTTPHEADER => $params,
		CURLOPT_USERAGENT => 1,
		CURLOPT_HTTPAUTH => CURLAUTH_BASIC
		)
		);
		$response = curl_exec($ch);
		$results = json_decode($response);
		if($results->CustomerReferenceNr)
		{
			return $results;
		}
		echo $response;
    }

    function pp($data)
    {
         echo "<pre>";
         print_r($data);
         die('--------------------------');
    }
}