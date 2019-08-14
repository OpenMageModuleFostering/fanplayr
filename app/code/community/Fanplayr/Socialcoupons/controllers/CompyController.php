<?php
	class Fanplayr_Socialcoupons_CompyController extends Mage_Core_Controller_Front_Action
	{
		private $countryLookup;
		private $shippingLookup;
		private $paymentLookup;
		private $regionLookup;

		public function indexAction()
		{
			echo $this->jsonMessage(true, 'Please use a valid method.');
		}

		public function isPerm()
		{
			$p = $this->getRequest()->getParams();

			// required input
			$secret = array_key_exists('secret', $p) ? $p['secret'] : '';
			$accKey = array_key_exists('acckey', $p) ? $p['acckey'] : '';

			// error, needs more info
			if (empty($secret) || empty($accKey)) {
				echo $this->jsonMessage(true, "Error. Needs 'secret' and 'acckey'.");
				return false;
			}

			$actualSecret = Mage::getStoreConfig('fanplayrsocialcoupons/config/secret');
			$actualAccKey = Mage::getStoreConfig('fanplayrsocialcoupons/config/acc_key');

			if ($actualSecret != $secret || $actualAccKey != $accKey) {
				echo $this->jsonMessage(true, "Error. Either your 'secret' or 'acckey' are incorrect.");
				return false;
			}

			return true;
		}

		public function unlinkAction()
		{
			if (!$this->isPerm()) return;

			$this->updateConfig('fanplayrsocialcoupons/config/secret', '', false);
			$this->updateConfig('fanplayrsocialcoupons/config/secret_inner', '', false);
			$this->updateConfig('fanplayrsocialcoupons/config/acc_key', '', false);
			$this->updateConfig('fanplayrsocialcoupons/config/shop_id', '', false);
			$this->updateConfig('fanplayrsocialcoupons/config/widget_keys', '', false);
			$this->updateConfig('fanplayrsocialcoupons/config/widget_keys_genius', '', false);
			$this->updateConfig('fanplayrsocialcoupons/config/disable_on_urls', '', false);
			$this->updateConfig('fanplayrsocialcoupons/config/wait_for_onload', '', false);

			$this->updateConfig('fanplayrsocialcoupons/config/dep_prefix', '', false);
			$this->updateConfig('fanplayrsocialcoupons/config/dep_extra_rewrite_routes', '', false);

			$this->updateConfig('fanplayrsocialcoupons/config/gtm_container_id', '', false);

			$this->updateConfig('fanplayrsocialcoupons/config/custom_embed_url', '', true);
			$this->updateConfig('fanplayrsocialcoupons/config/custom_embed_url_post', '', false);

			$this->updateConfig('fanplayrsocialcoupons/config/use_tbuy', '', false);

			$this->updateConfig('fanplayrsocialcoupons/config/disable_user_identity_tracking', '', false);
			$this->updateConfig('fanplayrsocialcoupons/config/coupon_apply_utm', '', false);

			//$this->addNotice('Fanplayr details removed.');
			echo $this->jsonMessage(false, 'Fanplayr unlink successful".');
		}

		public function addTemplateAction()
		{
			// protect
			if (!$this->isPerm()) return;

			$numTemplatesWithNoFanplayr = 0;

			$originalPath = getcwd() . '/app/design/frontend/base/default/template/fanplayr';

			foreach(array(getcwd().'/app/design/frontend/default/', getcwd().'/app/design/frontend/enterprise/') as $path) {

				$dir = opendir($path);

				while($d = readdir($dir)){
					if (is_dir($path . $d) && $d != '.' && $d != '..') {
						if (is_dir($path . $d . '/template') && !is_dir($path . $d . '/template/fanplayr')){
							symlink($originalPath, $path . $d . '/template/fanplayr');
							$numTemplatesWithNoFanplayr++;
						}
					}
				}

			}

			echo $this->jsonMessage(false, $numTemplatesWithNoFanplayr . ' templates added.');
		}

		public function consoleUpdateAction()
		{
			// a bit of dodgy stuff here, but should be fine
			// hopefully doesn't make it less secure!
			$p = $this->getRequest()->getParams();

			// required input
			$secretInner = array_key_exists('secretinner', $p) ? $p['secretinner'] : '';
			$secret = array_key_exists('secret', $p) ? $p['secret'] : '';

			$actualSecret = Mage::getStoreConfig('fanplayrsocialcoupons/config/secret');
			$actualSecretInner = Mage::getStoreConfig('fanplayrsocialcoupons/config/secret_inner');

			if (empty($secret) || empty($secretInner)) {
				echo $this->jsonMessage(true, "Error. Needs 'secret' and 'super secret'!");
				return false;
			}

			if ($actualSecret != $secret || $secretInner != $actualSecretInner) {
				echo $this->jsonMessage(true, "Error. Either your 'secret' or 'super secret' are incorrect.");
				return false;
			}

			// -----------------------------

			$this->updateConfig('fanplayrsocialcoupons/config/secret', array_key_exists('secretnew', $p) ? $p['secretnew'] : '', false);
			$this->updateConfig('fanplayrsocialcoupons/config/acc_key', array_key_exists('acckeynew', $p) ? $p['acckeynew'] : '', false);
			$this->updateConfig('fanplayrsocialcoupons/config/shop_id', array_key_exists('shopid', $p) ? $p['shopid'] : '', false);
			$this->updateConfig('fanplayrsocialcoupons/config/widget_keys', array_key_exists('gamafied', $p) ? $p['gamafied'] : '', false);
			$this->updateConfig('fanplayrsocialcoupons/config/widget_keys_genius', array_key_exists('genius', $p) ? $p['genius'] : '', false);
			$this->updateConfig('fanplayrsocialcoupons/config/custom_url', array_key_exists('url', $p) ? $p['url'] : '', false);
			$this->updateConfig('fanplayrsocialcoupons/config/disable_on_urls', array_key_exists('disableonurls', $p) ? $p['disableonurls'] : '', false);

			// a bit of a hack, used to be "waitforonload" but is now used for "embedtype"
			$this->updateConfig('fanplayrsocialcoupons/config/wait_for_onload', (array_key_exists('embedtype', $p) ? $p['embedtype'] : '0'), false);

			$this->updateConfig('fanplayrsocialcoupons/config/layout_hook', (array_key_exists('layouthook', $p) ? $p['layouthook'] : 'content'), false);
			$this->updateConfig('fanplayrsocialcoupons/config/layout_hook_home', (array_key_exists('layouthookhome', $p) ? $p['layouthookhome'] : 'content'), false);
			$this->updateConfig('fanplayrsocialcoupons/config/layout_hook_order', (array_key_exists('layouthookorder', $p) ? $p['layouthookorder'] : 'content'), false);

			$this->updateConfig('fanplayrsocialcoupons/config/dep_prefix', array_key_exists('depprefix', $p) ? $p['depprefix'] : '', false);
			$this->updateConfig('fanplayrsocialcoupons/config/dep_extra_rewrite_routes', array_key_exists('deproutes', $p) ? $p['deproutes'] : '', false);

			$this->updateConfig('fanplayrsocialcoupons/config/gtm_container_id', array_key_exists('gtmcontainerid', $p) ? $p['gtmcontainerid'] : '', false);

			$this->updateConfig('fanplayrsocialcoupons/config/custom_embed_url', array_key_exists('customembedurl', $p) ? $p['customembedurl'] : '', false);
			$this->updateConfig('fanplayrsocialcoupons/config/custom_embed_url_post', array_key_exists('customembedurlpost', $p) ? $p['customembedurlpost'] : '', false);

			$this->updateConfig('fanplayrsocialcoupons/config/use_tbuy', array_key_exists('usetbuy', $p) ? $p['usetbuy'] : '', true);

			$this->updateConfig('fanplayrsocialcoupons/config/disable_user_identity_tracking', array_key_exists('disableuseridentitytracking', $p) ? $p['disableuseridentitytracking'] : '', true);
			$this->updateConfig('fanplayrsocialcoupons/config/coupon_apply_utm', array_key_exists('couponapplyutm', $p) ? $p['couponapplyutm'] : '', true);

			//$this->addNotice('Fanplayr details updated.');
			echo $this->jsonMessage(false, 'Fanplayr details updated".');
		}

		public function setInstallDataAction()
		{
			$p = $this->getRequest()->getParams();

			$secret = array_key_exists('secret', $p) ? $p['secret'] : '';
			$accKey = array_key_exists('acckey', $p) ? $p['acckey'] : '';
			$shopId = array_key_exists('shopid', $p) ? $p['shopid'] : '';

			// error, needs more info
			if (empty($secret) || empty($accKey) || empty($shopId)) {
				//$this->addError("Fanplayr: Error setting install data. Please provide 'secret', 'accid' and 'shopid'.");
				echo $this->jsonMessage(true, "Please provide 'secret', 'acckey' and 'shopid'.");
				return;
			}

			// check that the secret is correct
			$actualSecret = Mage::getStoreConfig('fanplayrsocialcoupons/config/secret');
			if ($secret != $actualSecret){
				//$this->addError('Fanplayr: Error setting install data. Secret is incorrect.');
				echo $this->jsonMessage(true, 'Secret is incorrect.');
				return;
			}

			// ok, we can set the new accId
			$this->updateConfig('fanplayrsocialcoupons/config/acc_key', $accKey);
			$this->updateConfig('fanplayrsocialcoupons/config/shop_id', $shopId);

			//$this->addSuccess('Fanplayr: Install data set.');

			// return a good response
			echo $this->jsonMessage(false, 'Thanks, account ID updated to "' . $accKey . '" and shop ID to "' . $shopId . '".');
		}

		public function joinCompleteAction()
		{
			$p = $this->getRequest()->getParams();

			$message = array_key_exists('message', $p) ? $p['message'] : '';
			$error = array_key_exists('error', $p) ? $p['error'] : '';

			//if (!empty($message)) $this->addSuccess("Fanplayr: " . $message);
			//if (!empty($error)) $this->addError("Fanplayr: " . $error);

			$skinDir = Mage::getBaseUrl('skin').'/frontend/socialcoupons';

			$out = <<<EOT
				<html>
					<head>
						<title></title>
						<style>
							#fanplayr-updating-logo {
								width: 200px;
								height: 65px;
								margin: 30px auto;
							}
							#fanplayr-updating-thanks {
								font-family: Helvetica, Arial, Verdana, sans-serif;
								font-size: 120%;
								font-weight: bold;
								text-align:center;
							}
							#fanplayr-updating-spinner {
								width: 43px;
								height: 11px;
								margin: 30px auto;
							}
						</style>
						<script>
							window.top.location.reload(true);
						</script>
					</head>
					<body>
						<div id="fanplayr-updating-logo"><img src="{$skinDir}/images/fanplayr_logo.png" width="200" height="65" alt="Fanplayr Logo" title="Fanplayr" /></div>
						<div id="fanplayr-updating-thanks">Thanks. Updating details.</div>
						<div id="fanplayr-updating-spinner"><img src="{$skinDir}/images/progress-loader.gif" width="43" height="11" alt="Loading ..." title="Loading ..." /></div>
					</body>
				</html>
EOT;
			echo $out;
		}

		public function addWidgetAction()
		{
			$this->addRemoveWidget(false);
		}

		public function removeWidgetAction()
		{
			$this->addRemoveWidget(true);
		}

		private function countryNameFromCode($code)
		{
			if (!$this->countryLookup) $this->countryLookup = Mage::getModel('directory/country_api')->items();

			foreach($this->countryLookup as $k => $v) {
				if (array_key_exists('country_id', $v) && $v['country_id'] == $code)
					return $v['name'];
			}
			return $code;
		}

		private function paymentNameFromCode($code)
		{
			if (!$this->paymentLookup) $this->paymentLookup = Mage::getSingleton('payment/config')->getActiveMethods();

			foreach ($this->paymentLookup as $paymentCode => $paymentModel) {
				if ($paymentCode == $code) {
				   return Mage::getStoreConfig('payment/'.$paymentCode.'/title');
				}
			}

			return $code;
		}

		private function shippingNameFromCode($code)
		{
			if (!$this->shippingLookup) $this->shippingLookup = Mage::getSingleton('shipping/config')->getAllCarriers();

			foreach ($this->shippingLookup as $carrierCode => $carrierModel) {
				$carrierMethods = $carrierModel->getAllowedMethods();
				if (!$carrierMethods)
					return $code;

				$carrierTitle = Mage::getStoreConfig('carriers/'.$carrierCode.'/title');
				foreach ($carrierMethods as $methodCode => $methodTitle) {
					if ($carrierCode.'_'.$methodCode == $code) {
						return '['.$carrierCode.'] '.$methodTitle;
					}
				}
			}

			return $code;
		}

		private function regionNameFromCode($code)
		{
            $countriesArray = Mage::getResourceModel('directory/country_collection')->load()->toOptionArray(false);
            $countries = array();
            foreach ($countriesArray as $a) {
                $countries[$a['value']] = $a['label'];
            }

            $countryRegions = array();
            $regionsCollection = Mage::getResourceModel('directory/region_collection')->load();
            foreach ($regionsCollection as $region) {
                $countryRegions[$region->getCountryId()][$region->getId()] = $region->getDefaultName();
            }

            foreach ($countryRegions as $countryId=>$regions) {
				foreach ($regions as $regionId => $regionName) {
					if ($regionId == $code) {
						return $regionName.', '.$countries[$countryId];
					}
				}
            }

			return $code;
		}

		public function getRulesAction()
		{
			if (!$this->isPerm()) return;


			$p = $this->getRequest()->getParams();

			$offset = array_key_exists('offset', $p) ? $p['offset'] : null;
			if ($offset == null) $offset = 0;
			$offset = (int)$offset;
			$limit = array_key_exists('limit', $p) ? $p['limit'] : null;
			if ($limit == null) $limit = 100;
			$limit = (int)$limit;

			try {
				$rules = Mage::getResourceModel('salesrule/rule_collection')->load();

				//$rules = Mage::getModel('salesrule/rule')->getCollection();
				//$rules->getSelect()->limit($limit, $offset);

				$returnData = array();

				foreach ($rules as $rule)
				{
					if (!$rule-> getIsActive())
						continue;

					// don't send those that have expired over
					// hopefully this will fix any problems with too many coupons being sent?
					if ($rule->getToDate() && strtotime($rule->getToDate()) < time())
						continue;

					$rule = Mage::getModel('salesrule/rule')->load($rule->getId());

					$data = $rule->getData();
					if (array_key_exists('conditions_serialized', $data)) unset($data['conditions_serialized']);
					if (array_key_exists('actions_serialized', $data)) unset($data['actions_serialized']);

					$data['conditions'] = $rule->getConditions()->asArray();

					if ($data['conditions']['conditions']){
						foreach($data['conditions']['conditions'] as $k => $v) {
							if ($v['type'] == 'salesrule/rule_condition_address') {

								// if it's a country, replace the country data
								if ($v['attribute'] == 'country_id') {
									$countryName = $this->countryNameFromCode($v['value']);

									if ($countryName) {
										$data['conditions']['conditions'][$k]['value'] = $countryName;
									}
								}

								// region
								if ($v['attribute'] == 'region_id') {
									$regionName = $this->regionNameFromCode($v['value']);

									if ($regionName) {
										$data['conditions']['conditions'][$k]['value'] = $regionName;
									}
								}

								// payment method
								if ($v['attribute'] == 'payment_method') {
									$paymentName = $this->paymentNameFromCode($v['value']);

									if ($paymentName) {
										$data['conditions']['conditions'][$k]['value'] = $paymentName;
									}
								}

								// shipping method
								if ($v['attribute'] == 'shipping_method') {
									$shippingName = $this->shippingNameFromCode($v['value']);

									if ($countryName) {
										$data['conditions']['conditions'][$k]['value'] = $shippingName;
									}
								}
							}
						}
					}

					array_push($returnData, $data);
				}

				$returnData = array_reverse($returnData);

				echo $this->jsonMessage(false, 'Rewards gathered.', array('rules' => $returnData));
			}catch (Exception $e) {
				echo $this->jsonMessage(true, 'Sorry, there was a problem getting Rules data.');
			}
		}

		// addev in v1.0.18
		public function getPrefixAction()
		{
			if (!$this->isPerm()) return;

			echo $this->jsonMessage(false, 'Deputization Prefix.', array('prefix' => Mage::getStoreConfig('fanplayrsocialcoupons/config/dep_prefix')));
		}

		// addev in v1.0.18
		public function setPrefixAction()
		{
			if (!$this->isPerm()) return;

			$p = $this->getRequest()->getParams();
			$prefix = array_key_exists('prefix', $p) ? $p['prefix'] : '';

			if (empty($prefix)) {
				echo $this->jsonMessage(true, "'prefix' required.");
				return;
			}

			$oldPrefix = Mage::getStoreConfig('fanplayrsocialcoupons/config/dep_prefix');
			$this->updateConfig('fanplayrsocialcoupons/config/dep_prefix', $prefix);

			echo $this->jsonMessage(false, 'Deputization Prefix.', array('oldPrefix' => $oldPrefix, 'newPrefix' => Mage::getStoreConfig('fanplayrsocialcoupons/config/dep_prefix')));
		}

		// added in v1.0.18
		public function getOrdersAction()
		{
			// if (!$this->isPerm()) return;
			$p = $this->getRequest()->getParams();

			$hash = array_key_exists('hash', $p) ? $p['hash'] : null;

			if (!$hash){
				echo $this->jsonMessage(true, 'No hash supplied.', array('orders' => null, 'count' => 0));
				return;
			}

			// remove it
			unset($p['hash']);

			// create hash from vars
			ksort($p);

			// make a string
			$params = '';
			foreach($p as $k => $v) {
				$params .= $k . '=' . $v;
			}

			$secret = Mage::getStoreConfig('fanplayrsocialcoupons/config/secret');
			$accKey = Mage::getStoreConfig('fanplayrsocialcoupons/config/acc_key');

			// add the account keya and secret to the start
			$params = $accKey . $secret . $params;

			$actualHash = md5($params);

			if ($actualHash != $hash){
				//echo $this->jsonMessage(true, 'Not authorized. Hash incorrect.' . $actualHash, array('orders' => null, 'count' => 0));
				echo $this->jsonMessage(true, 'Not authorized. Hash incorrect.', array('orders' => null, 'count' => 0));
				return;
			}

			$orderId = array_key_exists('id', $p) ? $p['id'] : null;
			$orderNumber = array_key_exists('num', $p) ? @$p['num'] : null;

			// id, num, created, updated
			$filter = array_key_exists('filter', $p) ? $p['filter'] : null;

			$to = array_key_exists('to', $p) ? $p['to'] : null;
			$from = array_key_exists('from', $p) ? $p['from'] : null;

			$offset = array_key_exists('offset', $p) ? $p['offset'] : null;
			if ($offset == null) $offset = 0;
			$offset = (int)$offset;
			$limit = array_key_exists('limit', $p) ? $p['limit'] : null;
			if ($limit == null) $limit = 10;
			$limit = (int)$limit;

			$status = array_key_exists('status', $p) ? $p['status'] : null;

			$type = array_key_exists('type', $p) ? $p['type'] : 'count';
			if ($type != 'full' && $type != 'count') {
				echo $this->jsonMessage(true, '"type" must be "full" or "count".', array('orders' => null, 'count' => 0));
				return;
			}

			// single
			if (!empty($orderId) || !empty($orderNumber)) {
				if ($orderId){
					$orders = array(Mage::getModel('sales/order')->load($orderId));
				}else {
					$orders = array(Mage::getModel('sales/order')->loadByIncrementId($orderNumber));
				}

				if (is_array($orders) && $orders[0]->getRealOrderId() == null) {
					echo $this->jsonMessage(true, 'Error getting order.', array('orders' => null, 'count' => 0));
					return;
				}
			// multi
			} else {

				if ($filter == 'id') $filterBy = 'entity_id';
				if ($filter == 'num') $filterBy = 'increment_id';
				if ($filter == 'created') $filterBy = 'created_at';
				if ($filter == 'updated') $filterBy = 'updated_at';

				$orders = Mage::getModel('sales/order')->getCollection();

				if ($filter) {
					if ($from || $to) {
						$orders = $orders->addAttributeToFilter($filterBy, array('from'  => $from, 'to' => $to));
					} else {
						echo $this->jsonMessage(true, 'Need from and/or to for filtering.', array('orders' => array(), 'count' => 0));
						return;
					}
				}
				if ($status) {
					$orders = $orders->addAttributeToFilter('status', array('eq' => $status));
				}
			}

			if ($type == 'count') {
				$count = $orders->count();
				echo $this->jsonMessage(false, 'Orders count.', array('count' => $count));
				return;
			}

			$orders->getSelect()->limit($limit, $offset);

			// will fill this ..
			$orderReturn = array();

			// ---------------------------------------------------
			// yep, gotta support 1.4

			$mageV = Mage::getVersion();
			$mageVa = explode('.', $mageV);

			// community 1.4 and below
			// enterprise?
			$useOldVersion = false;
			if ($this->isMageCommunity() && $mageVa[0] == '1' && intval($mageVa[1]) <= 4) {
				$useOldVersion = true;
			}

			// have to get the last order in a stupid way for some reason for 1.4 ...
			if ($useOldVersion) {

				foreach($orders as $order) {

					$data = $order->getData();
					$quoteId = $order->getQuoteId();

					$orderReturn[] = array(
						'orderId' => array_key_exists('id', $data) ? $data['id'] : '',
						'orderNumber' => $order->getRealOrderId(),
						'orderEmail' => array_key_exists('customer_email', $data) ? $data['customer_email'] : '',
						'orderDate' => array_key_exists('created_at', $data) ? $data['created_at'] : '',
						'updatedDate' => array_key_exists('updated_at', $data) ? $data['updated_at'] : '',
						'currency' => array_key_exists('order_currency_code', $data) ? $data['order_currency_code'] : '',
						'orderTotal' => array_key_exists('subtotal', $data) ? round($data['subtotal'], 2) : 0,
						'firstName' => array_key_exists('customer_firstname', $data) ? $data['customer_firstname'] : '',
						'lastName' => array_key_exists('customer_lastname', $data) ? $data['customer_lastname'] : '',
						'customerEmail' => array_key_exists('customer_email', $data) ? $data['customer_email'] : '',
						'customerId' => array_key_exists('customer_id', $data) ? $data['customer_id'] : '',
						'discountCode' => array_key_exists('coupon_code', $data) ? $data['coupon_code'] : '',
						'discountAmount' => array_key_exists('discount_amount', $data) ? round($data['discount_amount'], 2) : 0,
						'state' => array_key_exists('state', $data) ? $data['state'] : '',
						'status' => array_key_exists('status', $data) ? $data['status'] : '',
						'quoteId' => $quoteId
					);
				}

			}else {

				foreach($orders as $order) {

					$data = $order->getData();
					$quoteId = $order->getQuoteId();

					$orderReturn[] = array(
						'orderId' => $order->getId(),
						'orderNumber' => $order->getRealOrderId(),
						'orderEmail' => $order->getCustomerEmail(),
						'orderDate' => $order->getCreatedAt(),
						'updatedDate' => $order->getUpdatedAt(),
						'currency' => $order->getOrderCurrencyCode(),
						'orderTotal' => round($order->getBaseSubtotal(), 2),
						'firstName' => $order->getCustomerFirstname(),
						'lastName' => $order->getCustomerLastname(),
						'customerEmail' => $order->getCustomerEmail(),
						'customerId' => $order->getCustomerId(),
						'discountCode' => $order->getCouponCode(),
						'discountAmount' =>  array_key_exists('base_discount_amount', $data) ? round($data['base_discount_amount'], 2) : 0,
						'state' => $order->getState(),
						'status' => $order->getStatus(),
						'quoteId' => $quoteId
					);
				}

			}
			// ---------------------------------------------------

			$count = $orders->count();
			echo $this->jsonMessage(false, 'Orders returned.', array('count' => $count, 'orders' => $orderReturn));
		}

		// ------------------------------------------------------------------
		// testing only
		public function clearCartAction()
		{
			if (strpos($_SERVER['HTTP_HOST'], 'fanplayr.com') > 0){
				$cartHelper = Mage::helper('checkout/cart');
				$items = $cartHelper->getCart()->getItems();
				foreach ($items as $item) {
					$itemId = $item->getItemId();
					$cartHelper->getCart()->removeItem($itemId)->save();
				}
			}
		}

		// -------------------------------------------------------------------
		// used by both add / remove
		private function addRemoveWidget($remove = false)
		{
			if (!$this->isPerm()) return;

			$p = $this->getRequest()->getParams();

			// required input
			$secret = array_key_exists('secret', $p) ? $p['secret'] : null;
			$accKey = array_key_exists('acckey', $p) ? $p['acckey'] : null;
			$campKey = array_key_exists('campkey', $p) ? $p['campkey'] : null;

			// could be null for "social-sales" or "genius"
			$type = array_key_exists('type', $p) ? $p['type'] : null;

			$inform = array_key_exists('inform', $p) ? $p['inform'] == '1' : false;
			$shopId = Mage::getStoreConfig('fanplayrsocialcoupons/config/shop_id');

			// tell fanplayr about it
			if ($inform) {
				$m = null;
				try {
					$m = json_decode($this->httpGetContent('http://my.fanplayr.com/api.magentoInformWidget/', array(
						'acc_key' => $accKey,
						'shop_id' => $shopId,
						'secret' => $secret,
						'version' => $this->getExtensionVersion(),
						'camp_key' => $campKey,
						'remove' => ($remove ? '1' : '0'),
						'type' => $type
					)));
				}catch (Exception $e) {
					echo $this->jsonMessage(true, "Error. Could not inform Fanplayr.");
					return;
				}
				if ($m) {
					if ($m->error) {
						echo $this->jsonMessage(true, $m->message);
						return;
					}
				}
			}

			// if that worked (or we skipped) now add to local config to actually show it
			$widgetConf = 'fanplayrsocialcoupons/config/widget_keys';
			if (!empty($type)) {
				// genius, social-sales
				if ($type == 'genius')
					$widgetConf = 'fanplayrsocialcoupons/config/widget_keys_genius';
			}

			$widgetKeys = array();
			try {
				$widgetKeys = json_decode(Mage::getStoreConfig($widgetConf));
			}catch(Exception $e) {
			}

			if (!is_array($widgetKeys))
				$widgetKeys = array();

			if ($remove){
				$this->array_remove_all($widgetKeys, $campKey);
			}else {
				array_push($widgetKeys, $campKey);
			}

			$this->updateConfig($widgetConf, json_encode($widgetKeys));

			echo $this->jsonMessage(false, "Widget set.");
			return;
		}

		// added in 1.0.18
		public function getWidgetAction()
		{
			if (!$this->isPerm()) return;

			$p = $this->getRequest()->getParams();
			$type = array_key_exists('type', $p) ? $p['type'] : null;
			if (empty($type))
				$type = 'social-sales';

			$widgetConf = 'fanplayrsocialcoupons/config/widget_keys';
			if ($type == 'genius')
				$widgetConf = 'fanplayrsocialcoupons/config/widget_keys_genius';

			$widgetKeys = array();
			try {
				$widgetKeys = json_decode(Mage::getStoreConfig($widgetConf));
			}catch(Exception $e) {
			}

			echo $this->jsonMessage(false, "Widget keys: " . $type, array('keys'=>$widgetKeys));
		}

		// -------------------------------------------------------------------------------
		// helpers

		function array_remove( &$array, $val ) {
			foreach ( $array as $i => $v ) {
				if ( $v == $val ) {
					array_splice( $array, $i, 1 );
					return $array;
				}
			}
			return $array;
		}

		function array_remove_all( &$array, $val ) {
			$n = array();
			foreach ( $array as $i => $v ) {
				if ( $v != $val ) {
					array_push($n, $v);
				}
			}
			$array = $n;
			return $array;
		}

		public function jsonMessage($isError, $message, $extras = array())
		{
			$extras['error'] = $isError;
			$extras['message'] = $message;
			$extras['version'] = $this->getExtensionVersion();
			$extras['mage_version'] = Mage::getVersion();

			return json_encode($extras);
		}

		private function getExtensionVersion()
		{
			return (string) Mage::getConfig()->getNode()->modules->Fanplayr_Socialcoupons->version;
		}

		// postVars as k/v array OR
		private function httpGetContent($url, $vars = null)
		{
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1) ;

			if ($vars != null) {
				// WTF ?
				if (is_array($vars)) $vars = str_replace('&amp;', '&', http_build_query($vars));
				//curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
				//curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_URL, $url.'?'.$vars);
			}

			$r = curl_exec($ch);
			curl_close($ch);

			return $r;
		}

		public function updateConfig($key, $value, $refresh = true)
		{
			Mage::getConfig()->saveConfig($key, $value);
			if ($refresh){
				Mage::getConfig()->reinit();
				Mage::app()->reinitStores();
			}
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
?>