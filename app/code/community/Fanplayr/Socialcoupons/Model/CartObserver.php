<?php
	class Fanplayr_Socialcoupons_Model_CartObserver
	{
		public $hasSucceeded;

		public function checkSavedCouponAction()
		{
			$couponCode = Mage::getSingleton('core/session')->getData('fanplayr_coupon');

			if ($couponCode) {
				$this->applyCoupon($couponCode);
				if ($this->hasSucceeded) {
					Mage::getSingleton('core/session')->setData('fanplayr_coupon', null);
				}
			}
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
				$this->notice('There are currently no items in the shopping cart. We will try and apply your coupon code "' . $couponCode . '" once you have added some items to your cart.');
			}

			if ($itemInCart){

				// ----------------------------------------
				// check for deputized coupon and replace
				$session = Mage::getSingleton("core/session");
				$deputies = $session->getData("fanplayr_deputy_codes");
				if (is_array($deputies) && array_key_exists($couponCode, $deputies)) {
					// has to be done first so we get actual coupon
					$session->setData('fanplayr_last_deputy_used', $couponCode);
					$couponCode = array_key_exists($couponCode, $deputies) ? $deputies[$couponCode] : '';
				}
				try {
					$quote->getShippingAddress()->setCollectShippingRates(true);
					$quote->setCouponCode(strlen($couponCode) ? $couponCode : '')
						->collectTotals()
						->save();
				} catch (Exception $e) {
					$this->error('Cannot apply coupon code: ' . $e-> getMessage());
					$this->hasSucceeded = false;
					return;
				}

				if ($couponCode) {
					if ($couponCode != $quote->getCouponCode()) {
						$this->error('Coupon code is invalid');
						$this->hasSucceeded = false;
						return;
					}
				}

				if ($this->hasSucceeded) {
					$this->success('Your coupon has been applied.');
				}
			}
		}

		public function getCurrentQuote()
		{
			return Mage::getSingleton('checkout/session')->getQuote();
		}

		// TODO: multilingual this
		public function error($message)
		{
			Mage::getSingleton('core/session')->addError($message);
			$this->hasSucceeded = false;
		}

		public function success($message)
		{
			Mage::getSingleton('core/session')->addSuccess($message);
		}

		public function notice($message)
		{
			Mage::getSingleton('core/session')->addNotice($message);
		}
	}