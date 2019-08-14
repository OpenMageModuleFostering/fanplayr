<?php
	class Fanplayr_Socialcoupons_Model_OrderObserver
	{
		// normal flow for single address orders
		public function checkSuccessSingle($observer)
		{
			$this->afterSuccessfulOrder($observer->getData('order_ids'));
		}

		// normal flow for multi address orders
		public function checkSuccessMulti($observer)
		{
			$this->afterSuccessfulOrder($observer->getData('order_ids'));
		}

		// normal flow fro multi or single address orders
		// this is not the flow for external paypal payments and such
		private function afterSuccessfulOrder($lastOrderId)
		{
			// only do embed (or even get data for it) if we have an account key - ie we are linked
			$accountKey = Mage::getStoreConfig('fanplayrsocialcoupons/config/acc_key');
			if (empty($accountKey))
				return;

			$errors = '';

			$secret = Mage::getStoreConfig('fanplayrsocialcoupons/config/secret');

			if (is_array($lastOrderId))
				$lastOrderId = $lastOrderId[0];

			// get the main order data, offloaded here as we also use this in the server-server route
			$data = $this->getLastOrderInformation($lastOrderId);

			// get session data if we can
			$session = null;
			$sessionKey = '';
			$userKey = '';
			try {
				$session = Mage::getModel('core/session');
			}catch(Exception $e){}

			if ($session) {
				$sessionKey = $session->getData('fanplayr_session_session_key');
				$userKey = $session->getData('fanplayr_session_user_key');
			}

			$data = array_merge($data, array(
				'accountKey' => $accountKey,
				'shopType' => 'magento',
				'version' => 3,
				'sessionKey' => $sessionKey,
				'userKey' => $userKey,
				'errors' => $errors
			));

			// add the embed type
			// this variable has changed from simply doing "wait for onload" to being for differnt types
			$data['embedType'] = intval(Mage::getStoreConfig('fanplayrsocialcoupons/config/wait_for_onload'));

			// ------------------------------------------------------------------
			// do the embed
			// normaly in the content block, but may be changed

			$block = Mage::getSingleton('core/layout')->createBlock(
				'Mage_Core_Block_Template',
				'fanplayr_embed_block',
				array('template' => 'fanplayr/success.phtml')
			)->assign('data', $data);

			$currentLayoutHook = Mage::getStoreConfig('fanplayrsocialcoupons/config/layout_hook_order');
			if (!$currentLayoutHook)
				$currentLayoutHook = 'content';

			$contentBlock = Mage::getSingleton('core/layout')->getBlock($currentLayoutHook);
			if ($contentBlock) $contentBlock->insert($block);
		}

		// sales_order_place_after
		public function serverToServerTrackingBeforePayment($ob)
		{
			$this->serverToServerTracking($ob->order);
		}

		// called on "sales_order_invoice_save_commit_after", after payment
		public function serverToServerTrackingAfterPayment($ob)
		{
			$this->serverToServerTracking($ob->getEvent()->getInvoice()->getOrder());
		}

		// used for tracking payments that don't go through the normal flow
		private function serverToServerTracking($order)
		{
			try {

				$accountKey = Mage::getStoreConfig('fanplayrsocialcoupons/config/acc_key');
				if (!$accountKey) return;

				//$order = $ob->order;

				$state = $order->getState();
				$method = $order->getPayment()->getMethod();

				// only paypal_standard supported ATM
				if ($method == 'paypal_standard'){

					$url = '';
					$params = array();

					// order created, let's tell Fanplayr to link details at a later time
					if ($state == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {

						$url .= 'http://my.fanplayr.com/api.track.v1.ordercreated/';

						// we will only have these on the before
						// get session data if we can
						$session = null;
						$sessionKey = '';
						$userKey = '';
						try {
							$session = Mage::getModel('core/session');
						}catch(Exception $e){}
						if ($session) {
							$sessionKey = $session->getData('fanplayr_session_session_key');
							$userKey = $session->getData('fanplayr_session_user_key');
						}

						$params['id'] = $order->getId();
						$params['number'] = $order->getIncrementId(); // getNumber don't work here ...

						$params['accountkey'] = $accountKey;
						$params['sessionkey'] = $sessionKey;
						$params['userkey'] = $userKey;

					// log the order with Fanplayr
					} else if ($state == Mage_Sales_Model_Order::STATE_PROCESSING) {

						$url .= 'http://my.fanplayr.com/api.track.v1.orderpaid/';

						// get order info
						$data = $this->getLastOrderInformation($order->getId());

						$params['domain'] = Mage::getUrl();
						$params['shoptype'] = 'magento';

						$params['id'] = $data['orderId'];
						$params['number'] = $data['orderNumber'];

						$params['email'] = $data['orderEmail'];
						$params['date'] = $data['orderDate'];
						$params['total'] = $data['total'];
						$params['subTotal'] = $data['subTotal'];
						$params['discountamount'] = $data['discount'];
						$params['discountcode'] = $data['discountCode'];
						$params['firstname'] = $data['firstName'];
						$params['lastname'] = $data['lastName'];
						$params['customeremail'] = $data['customerEmail'];
						$params['customerid'] = $data['customerId'];
						$params['shipping'] = $data['shipping'];
						$params['tax'] = $data['tax'];
						$params['products'] = json_encode($data['products']);

						$params['accountkey'] = $accountKey;

						// we don't have these afterwards but we should send through anyway
						$params['sessionkey'] = '';
						$params['userkey'] = '';
					}

					// disable user identity tracking
					$disableUserIdentityTracking = !!Mage::getStoreConfig('fanplayrsocialcoupons/config/disable_user_identity_tracking');
					if ( $disableUserIdentityTracking ) {
						$customerEmail = '';
						$params['email'] = '';
						$params['customeremail'] = '';
						$params['firstname'] = '';
						$params['lastname'] = '';
					}

					// now if we set a URL call it!
					if ($url) {

						$params = http_build_query($params);

						$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL, $url);
						curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
						curl_setopt($ch, CURLOPT_POST, 1);
						curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

						// DEBUG
						//Mage::log('PayPal Return, URL: ' . $url);
						//Mage::log('PayPal Return, Params: ' . $params);
						//Mage::log('PayPal Return, State (' . $state . '): ' . curl_exec($ch));
					}
				}

			}catch(Exception $e) {
				if (method_exists($e, 'getMessage')) {
					Mage::log('Fanplayr Exception. Save order for PayPal. ' . $e->getMessage());
				}else{
					Mage::log('Fanplayr Exception. Save order for PayPal. ' . $e->message);
				}
			}
		}

		private function debug($str) {
			// turn on to debug orders
			// Mage::log($str, null, 'fanplayr.log');
		}

		// ------------------------------------------------------------------------------------------------------
		// utils / helpers

		// gets the last order information
		// needs an ID for later than 1.4, otherwise automatically gets it
		// returns an array of data from the order
		private function getLastOrderInformation($lastOrderId)
		{
			$mageV = Mage::getVersion();
			$mageVa = explode('.', $mageV);

			// community 1.4 and below
			// enterprise 1.9 and below
			$useOldVersion = false;
			if ($this->isMageCommunity() && $mageVa[0] == '1' && intval($mageVa[1]) <= 4
				|| ($this->isMageEnterprise() || $this->isMageProfessional()) && $mageVa[0] == '1' && intval($mageVa[1] <= 9)) {
				$useOldVersion = true;
			}

			$products = array();

			$this->debug('$useOldVersion: ' . print_r($useOldVersion, true));

			// have to get the last order in a stupid way for some reason for 1.4 ...
			if ($useOldVersion) {

				$this->debug('Using old version ...');

				$lastOrderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();

				$this->debug('$lastOrderId: ' . $lastOrderId);

				// seriosuly, why would THIS be the way to do it? fail!
				$orderCollection = Mage::getModel('sales/order')->getCollection()
					->addFilter('increment_id', $lastOrderId)
					->setOrder('created_at', Varien_Data_Collection_Db::SORT_ORDER_DESC)
				;
				$order = $orderCollection->getFirstItem();
				$order->load($lastOrderId);

				$orderId = $order->getId();
				$orderNumber = $order->getRealOrderId();

				$quoteId = $order->getQuoteId();

				$data = $order->getData();

				$this->debug('$data: ' . print_r($data, true));

				// have to be careful here because some versions miss some variables ...
				$orderEmail = array_key_exists('customer_email', $data) ? $data['customer_email'] : '';
				$orderDate = array_key_exists('created_at', $data) ? $data['created_at'] : '';
				$currency = array_key_exists('order_currency_code', $data) ? $data['order_currency_code'] : '';
				$firstName = array_key_exists('customer_firstname', $data) ? $data['customer_firstname'] : '';
				$lastName = array_key_exists('customer_lastname', $data) ? $data['customer_lastname'] : '';
				$customerEmail = array_key_exists('customer_email', $data) ? $data['customer_email'] : '';
				$customerId = array_key_exists('customer_id', $data) ? $data['customer_id'] : '';
				$discountCode = array_key_exists('coupon_code', $data) ? $data['coupon_code'] : '';
				if (!$discountCode) $discountCode = '';
				$discountAmount = array_key_exists('discount_amount', $data) ? round($data['discount_amount'], 2) : 0;

				$orderSubTotal = array_key_exists('subtotal', $data) ? round($data['subtotal'], 2) : 0;
				$orderTotal = $orderSubTotal - abs($discountAmount);

				$shipping = array_key_exists('shipping_amount', $data) ? round($data['shipping_amount'], 2) : 0;
				$tax = array_key_exists('tax_amount', $data) ? round($data['tax_amount'], 2) : 0;

				$products = $this->getProductsFromOrder($order);

			} else {

				$this->debug('Using new method...');

				$order = Mage::getModel('sales/order')->load($lastOrderId);

				$orderId = $order->getId();
				$orderNumber = $order->getRealOrderId();
				$quoteId = $order->getQuoteId();
				$orderEmail = $order->getCustomerEmail();
				$orderDate = $order->getCreatedAt();
				$currency = $order->getOrderCurrencyCode();
				$firstName = $order->getCustomerFirstname();
				$lastName = $order->getCustomerLastname();
				$customerEmail = $order->getCustomerEmail();
				$customerId = $order->getCustomerId();
				$discountCode = $order->getCouponCode();
				if (!$discountCode) $discountCode = '';

				$data = $order->getData();
				// $discountAmount = array_key_exists('base_discount_amount', $data) ? round($data['base_discount_amount'], 2) : 0;
				$discountAmount = array_key_exists('discount_amount', $data) ? round($data['discount_amount'], 2) : 0;

				// $orderSubTotal = round($order->getBaseSubtotal(), 2);
				$orderSubTotal = round($order->getSubtotal(), 2);
				$orderTotal = $orderSubTotal - abs($discountAmount);

				$shipping = round(abs($order->getShippingAmount()) + abs($order->getShippingTaxAmount()), 2);
				$tax = round($order->getTaxAmount(), 2);

				// support disabling of TBuy Shipping module
				$useTbuy = Mage::getStoreConfig('fanplayrsocialcoupons/config/use_tbuy');
				$useTbuy = $useTbuy === '1';

				if ($useTbuy) {
					$orderData = $order->getData();
					if ($orderData && array_key_exists('applied_rule_ids', $orderData) && strlen($orderData['applied_rule_ids'])) {
						$ruleIds = explode(',', $orderData['applied_rule_ids']);

						foreach($ruleIds as $ruleId) {
							$rule = Mage::getModel('salesrule/rule')->load($ruleId);
							$d = $rule->getData();

							$ruleSimpleAction = $d['simple_action'];
							$ruleDiscountAmount = $d['discount_amount'];

							if ($ruleSimpleAction == 'tbuy_sd_percent') {
								$toRemove = abs($shipping * ($ruleDiscountAmount / 100));
								$discountAmount = -1 * round(abs($discountAmount) - $toRemove, 2);
								$shipping = round($shipping - $toRemove, 2);
								$orderTotal = round($orderTotal + $toRemove, 2);
							} else if($ruleSimpleAction == 'tbuy_sd_fixed') {
								$toRemove = abs($ruleDiscountAmount);
								$discountAmount = -1 * round(abs($discountAmount) - $toRemove, 2);
								$shipping = round($shipping - $toRemove, 2);
								$orderTotal = round($orderTotal + $toRemove, 2);
							}
						}
					}
				}

				$products = $this->getProductsFromOrder($order);

				$this->debug('End of new method...');
			}

			$storeCode = Mage::app()->getStore()->getCode();
			$gtmContainerId = Mage::getStoreConfig('fanplayrsocialcoupons/config/gtm_container_id');

			// disable user identity tracking
			$disableUserIdentityTracking = !!Mage::getStoreConfig('fanplayrsocialcoupons/config/disable_user_identity_tracking');
			if ( $disableUserIdentityTracking ) {
				$customerEmail = '';
				$orderEmail = '';
				$firstName = '';
				$lastName = '';
			}

			$this->debug('$orderId: ' . print_r($orderId, true));
			$this->debug('$orderNumber: ' . print_r($orderNumber, true));
			$this->debug('$orderDate: ' . print_r($orderDate, true));
			$this->debug('$orderTotal: ' . print_r($orderTotal, true));
			$this->debug('$orderSubTotal: ' . print_r($orderSubTotal, true));
			$this->debug('$discountAmount: ' . print_r($discountAmount, true));
			$this->debug('$discountCode: ' . print_r($discountCode, true));
			$this->debug('$currency: ' . print_r($currency, true));
			$this->debug('$orderEmail: ' . print_r($orderEmail, true));
			$this->debug('$firstName: ' . print_r($firstName, true));
			$this->debug('$lastName: ' . print_r($lastName, true));
			$this->debug('$customerEmail: ' . print_r($customerEmail, true));
			$this->debug('$customerId: ' . print_r($customerId, true));
			$this->debug('$shipping: ' . print_r($shipping, true));
			$this->debug('$tax: ' . print_r($tax, true));
			$this->debug('$products: ' . print_r($products, true));
			$this->debug('$storeCode: ' . print_r($storeCode, true));
			$this->debug('$gtmContainerId: ' . print_r($gtmContainerId, true));
			$this->debug('$quoteId: ' . print_r($quoteId, true));

			$this->debug('----------------------------------------');

			return array(
				'orderId' => $orderId,
				'orderNumber' => $orderNumber,
				'orderDate' => $orderDate,
				'total' => (float)$orderTotal,
				'subTotal' => (float)$orderSubTotal,
				'discount' => (float)$discountAmount,
				'discountCode' => $discountCode,
				'currency' => $currency,
				'orderEmail' => $orderEmail,
				'firstName' => $firstName,
				'lastName' => $lastName,
				'customerEmail' => $customerEmail,
				'customerId' => $customerId,
				'shipping' => (float)$shipping,
				'tax' => (float)$tax,
				'products' => $products,
				'storeCode' => $storeCode,
				'gtmContainerId' => $gtmContainerId,
				'quoteId' => $quoteId
			);

		}

		// used to fill out the "products" data
		// this will normally be printed later as a JSON, but this simply returns an array or arrays
		// it is likely we will not get all the information
		private function getProductsFromOrder($order)
		{
			$products = array();

			$items = $order->getAllItems();

			foreach ($items as $itemId => $item) {
				if ($item->getQtyOrdered() > 0 && !$item->getParentItemId()){

					// bug in Magento 1.5 and below?
					// just make sure it doesn't break things!
					$sku = '';
					try { $sku = $item->getSku(); }catch(Exception $e){}

					$products[] = array(
						'qty' => $item->getQtyOrdered(),
						'id' => $item->getProductId(),
						'sku' => $sku,
						'name' => $item->getName(),
						'price' => $item->getPrice(),
						'catId' => '',
						'catName' => ''
					);
				}
			}

			return $products;
		}

		public function isMageEnterprise() {
			return Mage::getConfig ()->getModuleConfig ( 'Enterprise_Enterprise' ) && Mage::getConfig ()->getModuleConfig ( 'Enterprise_AdminGws' ) && Mage::getConfig ()->getModuleConfig ( 'Enterprise_Checkout' ) && Mage::getConfig ()->getModuleConfig ( 'Enterprise_Customer' );
		}

		public function isMageProfessional() {
			return Mage::getConfig ()->getModuleConfig ( 'Enterprise_Enterprise' ) && !Mage::getConfig ()->getModuleConfig ( 'Enterprise_AdminGws' ) && !Mage::getConfig ()->getModuleConfig ( 'Enterprise_Checkout' ) && !Mage::getConfig ()->getModuleConfig ( 'Enterprise_Customer' );
		}

		public function isMageCommunity() {
			return !$this->isMageEnterprise() && !$this->isMageProfessional();
		}
	}