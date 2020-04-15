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
		if (empty($merchantID) || empty($securityCode)){
            echo 'CredentialsMissing';exit;
		}  
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
		if (is_string($results) && $results != 'testmerchant success'){
				echo 'BadCredentials:'.$results;exit;
		}
		if($results->RedirectURL)
		{
		    header("Location: ".$results->RedirectURL."");
		}
		echo $redirect;
		exit;
    }
	public function callback() {
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
           	$transactionData = $this->getTransactiondetail($data);
			if(200 !== $transactionData['status_code']){
			echo $transactionData['message'];exit;
			}
			else{
					if($transactionData['data']['Security'] != $_GET['ConfirmCode']){
						echo "Data mismatch! ConfirmCode doesn\'t match";
						exit;
					}
					elseif($transactionData['data']['CustomerReferenceNr'] != $_GET['CustomerReferenceNr']){
						echo "Data mismatch! CustomerReferenceNr doesn\'t match";
						exit;
					}
					elseif($transactionData['data']['TransactionID'] != $_GET['TransactionID']){
						echo "Data mismatch! TransactionID doesn\'t match";
						exit;
					}
					elseif($transactionData['data']['AltCoinID'] != $_GET['AltCoinID']){
						echo "Data mismatch! AltCoinID doesn\'t match";
						exit;
					}
					elseif($transactionData['data']['MerchantID'] != $_GET['MerchantID']){
						echo "Data mismatch! MerchantID doesn\'t match";
						exit;
					}
					elseif($transactionData['data']['coinAddress'] != $_GET['CoinAddressUsed']){
						echo "Data mismatch! coinAddress doesn\'t match";
						exit;
					}
					elseif($transactionData['data']['SecurityCode'] != $_GET['SecurityCode']){
						echo "Data mismatch! SecurityCode doesn\'t match";
						exit;
					}
					elseif($transactionData['data']['inputCurrency'] != $_GET['inputCurrency']){
						echo "Data mismatch! inputCurrency doesn\'t match";
						exit;
					}
					elseif($transactionData['data']['Status'] != $_GET['status']){
						echo "Data mismatch! status doesn\'t match. Your order status is ".$transactionData['data']['Status'];
						exit;
					}
					
				}
			/*$response = $this->validateOrder($data);
       
           	if($response->Status !== $_GET['status'])
           	{
           		echo "We have detected different order status. Your order has been halted.";
           		exit;
           	}
           	if($response->CustomerReferenceNr == $_GET['CustomerReferenceNr'])
           	{*/
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
           	/*} 
           	else
           	{
           		echo "We have detected changes in order info. Your order has been halted.";
           		exit;
           	}*/
        }
	}
    public function  validateOrder($data)
    {
    	
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
		if(is_string($results))
		{
			echo $response;
		}
		else{
			return $results;
		}
		
    }
	public function getTransactiondetail($data) {
		$params = array( 
        "authentication:1",
        'cache-control: no-cache',
        );

		$ch = curl_init();
		curl_setopt_array($ch, array(
		CURLOPT_URL => 'https://app.cointopay.com/v2REAPI?',
		//CURLOPT_USERPWD => $this->apikey,
		CURLOPT_POSTFIELDS => 'MerchantID='.$data['mid'].'&Call=Transactiondetail&APIKey=a&output=json&ConfirmCode='.$data['ConfirmCode'],
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_HTTPHEADER => $params,
		CURLOPT_USERAGENT => 1,
		CURLOPT_HTTPAUTH => CURLAUTH_BASIC
		)
		);
		$response = curl_exec($ch);
		$results = json_decode($response, true);
        return $results;
    }

    function pp($data)
    {
         echo "<pre>";
         print_r($data);
         die('--------------------------');
    }
}