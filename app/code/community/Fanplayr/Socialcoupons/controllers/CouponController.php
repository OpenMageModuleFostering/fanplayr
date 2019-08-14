<?php
	class Fanplayr_Socialcoupons_CouponController extends Mage_Core_Controller_Front_Action
	{
		public $hasSucceeded;

		public function log($message)
		{
			Mage::log($message, null, 'fanplayr_coupon.log');
		}

		public function indexAction()
		{
			echo $this->jsonMessage(true, 'Please use a valid method.');
		}

		public function addAction()
		{
			$p = $this->getRequest()->getParams();

			$code = array_key_exists('code', $p) ? $p['code'] : '';

			$redirectUrl = array_key_exists('redirect', $p) ? $p['redirect'] : null;

			// ----------------------------------------
			// if we have a code then apply, otherwise error
			if (!empty($code)) {
				$this->applyCoupon($code);
				$this->redirect($redirectUrl);
			}else {
				$message = $this->__('No coupon code supplied.');
				$this->error($message);
				$this->redirect($redirectUrl);
			}
		}

		// <SESSION_COUPON
		public function sessionAction()
		{
			$p = $this->getRequest()->getParams();

			$code = array_key_exists('code', $p) ? $p['code'] : '';

			$session = Mage::getSingleton("core/session");
			$allowedCoupons = $session->getData("fanplayr_session_codes");

			if ( !$allowedCoupons || is_array($allowedCoupons) ) {
				$allowedCoupons = array();
			}

			if ( !in_array($code, $allowedCoupons) ) {
				array_push($allowedCoupons, $code);
			}

			$session->setData("fanplayr_session_codes", $allowedCoupons);
 		}
 		// SESSION_COUPON>

		public function deputizeAction()
		{
			$p = $this->getRequest()->getParams();

			$actualCode = array_key_exists('a', $p) ? $p['a'] : null;
			$deputyCode = array_key_exists('d', $p) ? $p['d'] : null;

			if ($actualCode && $deputyCode)
			{
				$session = Mage::getSingleton("core/session");

				$deputies = $session->getData("fanplayr_deputy_codes");

				// cookie fallback
				if (!$deputies) {
					$cookie = Mage::getSingleton('core/cookie');
					$d = $cookie->get('fanplayr_deputy_codes');
					if ($d) $deputies = (Array)json_decode($d);
				}

				if (!is_array($deputies))
					$deputies = array();

				$deputies[strtoupper($deputyCode)] = $actualCode;

				$session->setData("fanplayr_deputy_codes", $deputies);

				$cookie = Mage::getSingleton('core/cookie');
				$cookie->set('fanplayr_deputy_codes', json_encode($deputies), 10800, '/');
			}

			//echo json_encode($deputies);
		}

		public function applyCoupon($couponCode)
		{
			$itemInCart = true;
			$this->hasSucceeded = true;

		   $quote = $this->getCurrentQuote();

			if (!$quote->getItemsCount()) {
				$itemInCart = false;
				// store it for later use ...
				$this->remember($couponCode);

				$message = $this->__('There are currently no items in the shopping cart. We will try and apply your coupon code "%s" once you have added some items to your cart.', Mage::helper('core')->htmlEscape($couponCode));
				$this->notice($message);
			}

			if ($itemInCart){


				$oldCouponCode = $quote->getCouponCode();
				if (!strlen($couponCode) && !strlen($oldCouponCode)) {
					$this->redirect();
				}

				// ----------------------------------------
				// check for deputized coupon and replace
				$session = Mage::getSingleton("core/session");
				$deputies = $session->getData("fanplayr_deputy_codes");

				// cookie fallback
				if (!$deputies) {
					$cookie = Mage::getSingleton('core/cookie');
					$d = $cookie->get('fanplayr_deputy_codes');
					if ($d) $deputies = (Array)json_decode($d);
				}

				if (is_array($deputies) && array_key_exists($couponCode, $deputies)) {
					// must be set first to get actual coupon
					// need to set as array, to support those horrible multi-coupon extensions
					$sessionDeps = $session->getData('fanplayr_used_deputies');
					if (!is_array($sessionDeps))
						$sessionDeps = array();
					// and only add it if it's not there so no duplicates
					if (!in_array($couponCode, $sessionDeps))
						array_push($sessionDeps, $couponCode);
					$session->setData('fanplayr_used_deputies', $sessionDeps);

					//
					$couponCode = array_key_exists($couponCode, $deputies) ? $deputies[$couponCode] : '';
				}

				try {
					$this->log('applyCoupon: trying to apply coupon');
					$quote->getShippingAddress()->setCollectShippingRates(true);
					$quote->setCouponCode(strlen($couponCode) ? $couponCode : '')
						->collectTotals()
						->save();
				} catch (Exception $e) {
					$this->log('applyCoupon: failed applying coupon');
					//$this->error('Cannot apply coupon code: ' . $e->getMessage());
					$this->error($this->__('Cannot apply the coupon code'));
				}

				$this->log('applyCoupon: coupon code - ' . $couponCode);
				$this->log('applyCoupon: quote->getCouponCode() - ' . $quote->getCouponCode());
				//$this->log(var_export($quote, true));
				//$this->log(print_r($quote, true));

				if ($couponCode) {
					if ($couponCode != $quote->getCouponCode()) {
						$this->log('applyCoupon: coupon code not valid');
						$this->error($this->__('Coupon code "%s" is not valid.', Mage::helper('core')->htmlEscape($couponCode)));
						//$this->error('Coupon code is invalid.');
					}
				}

				if ($this->hasSucceeded) {
					$this->log('applyCoupon: success');
					$this->success($this->__('Coupon code "%s" was applied.', Mage::helper('core')->htmlEscape($couponCode)));
					//$this->success('Your coupon has been applied.');
				}
			}
		}

		public function redirect( $overrideUrl = null )
		{
			// now go to cart and add thanks message
			$shopUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
			if (!strpos($shopUrl, 'index.php/'))
				$shopUrl .= 'index.php/';
			$customEmbedUrl = trim(Mage::getStoreConfig('fanplayrsocialcoupons/config/custom_embed_url'));
			if (!empty($customEmbedUrl)) $shopUrl = $customEmbedUrl;

			// Added by Matt, Nov 4, 2014 for Dainese.com
			if ( $overrideUrl !== null ) {
				$shopUrl = $overrideUrl;
			}

			// make sure that the shop URL is protocol agnostic
			if (strpos($shopUrl, '//') !== false) {
				$shopUrl = substr($shopUrl, strpos($shopUrl, '//'));
			}

			$protocol = 'http';
			if (array_key_exists('HTTPS', $_SERVER) && !empty($_SERVER['HTTPS'])){
				if ( strtolower($_SERVER['HTTPS']) === 'off' ) {
					// it's damn ISAPI with IIS, do not use HTTPS
				} else {
					$protocol = 'https';
				}
			}

			$shopUrl = $protocol . ':' . $shopUrl;

			$utmTracking = '';
			if ($this->hasSucceeded) {
				// get UTM apply cart tracking
				$couponApplyUtm = Mage::getStoreConfig('fanplayrsocialcoupons/config/coupon_apply_utm');

				if ($couponApplyUtm) {
					$utmTracking = '?' . $couponApplyUtm;
				}

			}

			header('Location: ' . $shopUrl . 'checkout/cart/' . $utmTracking);

			exit(1);
		}

		public function getCurrentQuote()
		{
			return Mage::getSingleton('checkout/session')->getQuote();
		}

		// TODO: multilingual this
		public function error($message)
		{
			Mage::getSingleton('checkout/session')->addError($message);
			$this->hasSucceeded = false;
		}

		public function success($message)
		{
			Mage::getSingleton('checkout/session')->addSuccess($message);
		}

		public function notice($message)
		{
			Mage::getSingleton('checkout/session')->addNotice($message);
		}

		public function remember($couponCode)
		{
			Mage::getSingleton('core/session')->setData('fanplayr_coupon', $couponCode);
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
	}

?>