<?php
	class Fanplayr_Socialcoupons_Model_EmbedObserver
	{
		public function getEmbedAction()
		{
			$r = Mage::app()->getRequest();
			$pCnt = $r->getControllerName(); // onepage, multishipping, cart
			$pAct = $r->getActionName(); // index
			$pRte = $r->getRouteName(); // checkout
			$pMod = $r->getModuleName(); // checkout

			if ($pMod == 'admin')
				return;

			$_ac = Mage::getStoreConfig('fanplayrsocialcoupons/config/acc_key');
			if (empty($_ac))
				return;

			$atPayment = $pMod == 'checkout' && $pCnt != 'cart';
			$atCart = $pMod == 'checkout' && $pCnt == 'cart';

			// AIT checkout compatability
			$atCart = $pMod == 'aitcheckout'&& $pCnt == 'checkout';

			// StreamCheckout
			$atCart = $pRte == 'streamcheckout'&& $pMod == 'streamcheckout';

			// DEPUTIES: if we have cookies set then reset it to 3 hours later
			// so we don't forget
			$cookie = Mage::getSingleton('core/cookie');
			$d = $cookie->get('fanplayr_deputy_codes');
			if ($d) $cookie->set('fanplayr_deputy_codes', $d, 10800, '/');

			// v1.0.14: added this to allow disabling on specific URLs
			$disableThisUrl = false;
			$disabledUrlString = Mage::getStoreConfig('fanplayrsocialcoupons/config/disable_on_urls');
			if (!empty($disabledUrlString)) {
				$disabledUrls = explode(',', $disabledUrlString);
				if (count($disabledUrls)) {
					$currentUrl = Mage::helper('core/url')->getCurrentUrl();
					foreach($disabledUrls as $url) {
						$url = trim($url);
						if (strstr($currentUrl, $url) !== false) {
							$disableThisUrl = true;
							break;
						}
					}
				}
			}

			// don't show at payment, or custom disabled URLs
			if (!$atPayment && !$disableThisUrl) {

				$block = Mage::getSingleton('core/layout')->createBlock(
					'Mage_Core_Block_Template',
					'fanplayr_embed_block'
				);

				// this variable has changed from simply doing "wait for onload" to being for differnt types
				// 0: Ajax: Normal
				// 1: Ajax: Lazy load
				// 2: TMS: User Only
				// 3: TMS: User and Order
				// 4: No Ajax: Normal
				// 5: No Ajax: Lazy Load
				// 6: Ajax: Normal, GTM
				// 7: Ajax: Normal, GTM Extended
				$embedType = intval(Mage::getStoreConfig('fanplayrsocialcoupons/config/wait_for_onload'));

				$directEmbed = true;
				// ajax embeds
				if ($embedType >= 0 && $embedType <= 3 || $embedType == 6 || $embedType = 7) {
					$directEmbed = false;
				}

				if ($directEmbed){
					$block->setTemplate('fanplayr/embed.phtml');
				}else{
					$block->setTemplate('fanplayr/embed_ajax.phtml');
				}

				// enc data
				$pageType = '';
				$currentProduct = '';
				$currentProductName = '';
				$currentProductPrice = '';
				$currentProductSku = '';
				$currentProductImage = '';
				$currentProductUrl = '';
				$currentCategory = '';
				$currentCategoryName = '';

				$pageType = $this->getPageType();

				if ($pageType == 'prod') {
					// just in case we'll protect with @
					@$cP = Mage::registry('current_product');
					if ($cP){
						$currentProduct = $cP->getId();
						$currentProductName = $cP->getName();
						$currentProductPrice = $cP->getPrice();
						$currentProductSku = $cP->getSku();
						$currentProductImage = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $cP->getImage();
						$currentProductUrl = $cP->getUrlPath();

						$categories = $cP->getCategoryIds();
						foreach($categories as $k => $_category_id){
							$_category = Mage::getModel('catalog/category')->load($_category_id);
							$currentCategory = $_category->getId();
							$currentCategoryName = $_category->getName();
							break;
						}
					}
				} else if ($pageType == 'cat') {
					// url for category ?
					@$cC = Mage::registry('current_category');
					if ($cC) {
						$currentCategory = $cC->getId();
						$currentCategoryName = $cC->getName();
					}
				}

				// shop URL
				$shopUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
				if (!strpos($shopUrl, 'index.php/'))
					$shopUrl .= 'index.php/';
				$customEmbedUrl = trim(Mage::getStoreConfig('fanplayrsocialcoupons/config/custom_embed_url'));
				if (!empty($customEmbedUrl)) $shopUrl = $customEmbedUrl;

				// make sure that the shop URL is protocol agnostic
				if (strpos($shopUrl, '//') !== false) {
					$shopUrl = substr($shopUrl, strpos($shopUrl, '//'));
				}

				$block->assign('shopUrl', $shopUrl);

				if (!$directEmbed){
					$block->assign('tt', $pageType);
					$block->assign('tc', $currentCategory);
					$block->assign('tcn', $currentCategoryName);

					$block->assign('tp', $currentProduct);
					$block->assign('tpn', $currentProductName);

					$block->assign('tpp', $currentProductPrice);
					$block->assign('tps', $currentProductSku);
					$block->assign('tpi', $currentProductImage);
					$block->assign('tpu', $currentProductUrl);

				}else {
					// direct embed so we need to set some other things we'd normally set in AjaxController
					// store session data in case the cookie is lost somehow
					$sessionVars = $this->decodeSessionCookie('fanplayr_genius_session');
					if ($sessionVars) {
						$session = Mage::getModel('core/session');
						if ($session){
							if (array_key_exists('key', $sessionVars)) $session->setData('fanplayr_session_session_key',$sessionVars['key']);
							if (array_key_exists('user', $sessionVars)) $session->setData('fanplayr_session_user_key', $sessionVars['user']);
						}
					}

					$block->assign('widgetKeys', Mage::getStoreConfig('fanplayrsocialcoupons/config/widget_keys'));
					$block->assign('widgetKeysGenius', Mage::getStoreConfig('fanplayrsocialcoupons/config/widget_keys_genius'));
					$block->assign('accountKey', Mage::getStoreConfig('fanplayrsocialcoupons/config/acc_key'));

					$block->assign('deputizeUrl', $shopUrl . 'fanplayr/coupon/deputize/a/%a/d/%d/');
					$block->assign('sessionCouponUrl', $shopUrl . 'fanplayr/coupon/session/code/%c/');

					$block->assign('noTags', false);

					$data = $this->getData(
						($pageType == 'cart'),
						$pageType,
						$currentProduct,
						$currentCategory,
						$currentCategoryName,
						$currentProductName,
						$currentProductPrice,
						$currentProductSku,
						$currentProductImage,
						$currentProductUrl
					);

					$disableAtcEmpty = htmlspecialchars(Mage::getStoreConfig('fanplayrsocialcoupons/config/disable_atc_empty'));

					if ( $data['lineItemCount'] === 0 && $disableAtcEmpty === '1' ) {
						$block->assign('applyToCartUrl', '');
					}else{
						$block->assign('applyToCartUrl', $shopUrl . 'fanplayr/coupon/add/code/%c/');
					}

					$block->assign('data', $data);
				}

				$customEmbedUrlPost = Mage::getStoreConfig('fanplayrsocialcoupons/config/custom_embed_url_post');

				$block->assign('embedType', $embedType);
				$block->assign('customEmbedUrlPost', $customEmbedUrlPost);

				$currentLayoutHook = Mage::getStoreConfig('fanplayrsocialcoupons/config/layout_hook' . ($pageType == 'home' ? '_home' : ''));
				if (!$currentLayoutHook)
					$currentLayoutHook = 'content';

				$contentBlock = Mage::getSingleton('core/layout')->getBlock($currentLayoutHook);
				if ($contentBlock) $contentBlock->insert($block);
			}
		}

		public static function getData($atCart = null, $pageType = null, $currentProduct = null, $currentCategory = null, $currentProductName = null, $currentCategoryName = null, $currentProductPrice = null, $currentProductSku = null, $currentProductImage = null, $currentProductUrl = null)
		{
			$itemCount = 0;
			$itemQty = 0;
			$subtotal = 0;
			$discount = 0;
			$custEmail = '';
			$custId = '';
			$couponCode = '';
			$currencyIso = '';
			$custSegment = '';
			$custGroup = '';

			$products = array();
			$productString = '';
			$quoteId = '';

			// get the current customer
			$customer = Mage::getSingleton('customer/session');
			$custGroup = $customer->getCustomerGroupId();

			$customerId = $customer->getCustomerId();
			if (self::isMageEnterprise()){
 				$websiteId = Mage::app()->getStore()->getWebsiteId();
				$segments = Mage::getModel('enterprise_customersegment/customer')->getCustomerSegmentIdsForWebsite($customerId,$websiteId);
				$custSegment = implode(',', $segments);
			}

			// get cart data
			$cartData = null;
			$cartMod = Mage::getSingleton('checkout/session');

			$mageV = Mage::getVersion();
			$mageVa = explode('.', $mageV);

			if ($cartMod) {
				$quote = $cartMod->getQuote();
				if ($quote) {

					$session = Mage::getSingleton("core/session");

					$quoteId = $cartMod->getQuoteId();

					$updateProductDetails = $session->getData("fanplayr_update_product_details");
					if ($updateProductDetails === true || $updateProductDetails === null) {

						//Mage::log('Updating product details JSON');

						$products = self::getProductsFromQuote($quote);

						$productString = addslashes(json_encode($products));
						$session->setData("fanplayr_product_cache", $productString);

						$session->setData("fanplayr_update_product_details", false);
					}else{
						$productCache = $session->getData("fanplayr_product_cache");
						if (strlen($productCache) > 0) {
							$productString = $productCache;
						}else{
							// empty array JSON of products
							$productString = "[]";
						}
					}

					$cartData = $quote->getData();

					$itemCount = array_key_exists('items_count', $cartData) ? $cartData['items_count'] : 0;
					$itemQty = array_key_exists('items_qty', $cartData) ? $cartData['items_qty'] : 0;

					$custEmail = array_key_exists('customer_email', $cartData) ? $cartData['customer_email'] : '';
					$custId = array_key_exists('customer_id', $cartData) ? $cartData['customer_id'] : '';
					$couponCode = array_key_exists('coupon_code', $cartData) ? $cartData['coupon_code'] : '';
					$currencyIso = array_key_exists('quote_currency_code', $cartData) ? $cartData['quote_currency_code'] : '';

					// before discount
					$subtotal = array_key_exists('subtotal', $cartData) ? $cartData['subtotal'] : 0;
					$discount = array_key_exists('subtotal_with_discount', $cartData) ? $subtotal - $cartData['subtotal_with_discount'] : 0;
					// after discount, before shipping / tax
					$total = array_key_exists('subtotal_with_discount', $cartData) ? $cartData['subtotal_with_discount'] : 0;

					// support disabling of TBuy Shipping module
					$useTbuy = Mage::getStoreConfig('fanplayrsocialcoupons/config/use_tbuy');
					$useTbuy = $useTbuy === '1';

					if ($useTbuy) {
						if (array_key_exists('applied_rule_ids', $cartData) && strlen($cartData['applied_rule_ids'])) {
							$ruleIds = explode(',', $cartData['applied_rule_ids']);
							// get shipping amount
							$shippingAddress = $quote->getShippingAddress();

							$shipping = 0;
							if ($shippingAddress) {
								$shipping = round(abs($shippingAddress['shipping_amount']) + abs($shippingAddress['shipping_tax_amount']), 2);
							}
							foreach($ruleIds as $ruleId) {
								$rule = Mage::getModel('salesrule/rule')->load($ruleId);
								$d = $rule->getData();
								$ruleSimpleAction = $d['simple_action'];
								$ruleDiscountAmount = $d['discount_amount'];
								if ($ruleSimpleAction == 'tbuy_sd_percent'){
									$toRemove = $shipping * ($ruleDiscountAmount / 100);
									$discount = round($discount - $toRemove, 2);
									$total = round($total + $toRemove, 2);
									$toRemove;
								}else if($ruleSimpleAction == 'tbuy_sd_fixed') {
									$toRemove = $ruleDiscountAmount;
									$discount = round($discount - $toRemove, 2);
									$total = round($total + $toRemove, 2);
								}
							}
						}
					}

				}
			}

			$storeCode = Mage::app()->getStore()->getCode();
			$gtmContainerId = Mage::getStoreConfig('fanplayrsocialcoupons/config/gtm_container_id');

			// disable user identity tracking
			$disableUserIdentityTracking = !!Mage::getStoreConfig('fanplayrsocialcoupons/config/disable_user_identity_tracking');
			if ( $disableUserIdentityTracking ) {
				$custEmail = '';
			}

			$data = array(
				'atCart' => $atCart,
				'lineItemCount' => (int)$itemCount,
				'numItems' => (int)$itemQty,
				'total' => (float)$total,
				'subTotal' => (float)$subtotal,
				'discount' => (float)$discount,
				'customerEmail' => $custEmail,
				'couponCode' => $couponCode,
				'currency' => $currencyIso,
				'customerId' => $custId,
				'customerSegment' => $custSegment,
				'customerGroup' => $custGroup,
				'pageType' => $pageType,
				'productId' => $currentProduct,
				'productName' => $currentProductName,
				'categoryId' => $currentCategory,
				'categoryName' => $currentCategoryName,
				'version' => 3,
				'shopType' => 'magento',
				'products' => $productString,
				'storeCode' => $storeCode,
				'gtmContainerId' => $gtmContainerId,

				'productPrice' => $currentProductPrice,
				'productSku' => $currentProductSku,
				'productImage' => $currentProductImage,
				'productUrl' => $currentProductUrl,

				'quoteId' => $quoteId
			);

			return $data;
		}

		function getPageType()
		{
			// pageType, currentPRoduct, currentCategory
			// tt: home, cart, page, srch, cat, prod, blog
			$r = Mage::app()->getRequest();
			$pCnt = $r->getControllerName();
			$pAct = $r->getActionName();
			$pRte = $r->getRouteName();
			$pMod = $r->getModuleName();

			// home
				// index, index, cms, cms
			if ($pCnt == 'index' && $pAct == 'index' && $pRte == 'cms') {
				return 'home';
			}

			// product
				// product, view, catalog, catalog
			if ($pCnt == 'product') {
				return 'prod';
			}

			// category
				// category, view, catalog, catalog
			if ($pCnt == 'category') {
				return 'cat';
			}

			// search
				// result, index, catalogsearch, catalogsearch
				// advanced, index, catalogsearch, catalogsearch
				// term, popular, catalogsearch, catalogsearch
				// seo_sitemap, category, catalog, catalog
			if ($pCnt == 'result' || $pCnt == 'advanced' || $pCnt == 'term' || $pCnt == 'seo_sitemap') {
				return 'srch';
			}

			// cart
				// cart, index, checkout, checkout
			if ($pCnt == 'cart') {
				return 'cart';
			}

			// blog (nah !)

			// page
				// page, view, cms, cms
				// index, index, contacts, contacts
				// guest, form, sales, sales
			return 'page';
		}

		public static function isMageEnterprise() {
			return Mage::getConfig ()->getModuleConfig ( 'Enterprise_Enterprise' ) && Mage::getConfig ()->getModuleConfig ( 'Enterprise_AdminGws' ) && Mage::getConfig ()->getModuleConfig ( 'Enterprise_Checkout' ) && Mage::getConfig ()->getModuleConfig ( 'Enterprise_Customer' );
		}

		public static function isMageProfessional() {
			return Mage::getConfig ()->getModuleConfig ( 'Enterprise_Enterprise' ) && !Mage::getConfig ()->getModuleConfig ( 'Enterprise_AdminGws' ) && !Mage::getConfig ()->getModuleConfig ( 'Enterprise_Checkout' ) && !Mage::getConfig ()->getModuleConfig ( 'Enterprise_Customer' );
		}

		public static function isMageCommunity() {
			return !self::isMageEnterprise() && !self::isMageProfessional();
		}

		// -------------------------------------------------------------------
		// coupon deputization

		// called before outpout, replaced coupons with deputized coupons
		// called on http_response_send_before
		//
		public function deputizeReplaceAction($observer)
		{
			$response = $observer->getResponse();
			if (!$response) return;

			$session = Mage::getSingleton("core/session");
			$lastDeputiesUsed = $session->getData("fanplayr_used_deputies");

			// don't even bother to go further if we haven't used a deputized code
			if (!is_array($lastDeputiesUsed)) {
				return;
			}

			// ------------------------------------------------------
			// only for pages we want
			$r = Mage::app()->getRequest();
			$pCnt = $r->getControllerName(); // onepage, multishipping, cart
			$pAct = $r->getActionName(); // index
			$pRte = $r->getRouteName(); // checkout
			$pMod = $r->getModuleName(); // checkout

			// full route
			$route = $pCnt.'/'.$pAct.'/'.$pRte.'/'.$pMod;

			$extraAllowedRoutes = array();
			$depRoutes = Mage::getStoreConfig('fanplayrsocialcoupons/config/dep_extra_rewrite_routes');
			// just in case we have problems here we don't want it to freak out
			try {
				$extraAllowedRoutes = array_merge($extraAllowedRoutes, explode(',', $depRoutes));
			}catch (Exception $e) {
				$extraAllowedRoutes = array();
			}

			// checkout pages, order view pages
			if (
				($pMod == 'checkout' && $pAct != 'success')
				|| $route == 'order/view/sales/sales'
				|| $route == 'index/index/onestepcheckout/onestepcheckout' // osc
				|| $route == 'ajax/add_coupon/onestepcheckout/onestepcheckout' // osc
				|| $route == 'ajax/set_methods_separate/onestepcheckout/onestepcheckout' // osc
				|| $route == 'checkout/updateSteps/aitcheckout/aitcheckout' //AIT checkout
				|| $route == 'cart/couponPost/aitcheckout/aitcheckout' //AIT checkout
				|| $route == 'checkout/index/aitcheckout/aitcheckout' //AIT checkout
				|| $route == 'index/index/streamcheckout/streamcheckout' // StreamCheckout by Made People
				|| $route == 'index/saveAll/streamcheckout/streamcheckout' // StreamCheckout (AJAX)
				|| $route == 'index/applyCoupon/streamcheckout/streamcheckout' // StreamCheckout (Coupon)
				|| $route == 'index/index/firecheckout/firecheckout' // FireCheckout
				|| $route == 'index/saveCoupon/firecheckout/firecheckout' // FireCheckout, input coupon
				|| $route == 'index/index/opc/onepage' // IWD One Page Checkout
				|| $route == 'coupon/couponPost/opc/onepage' // IWD One Page Checkout: coupon input (AJAX)
				|| in_array($route, $extraAllowedRoutes)
			){
				// do nothing
			}else {
				// yes I know this is a silly way to "else return"
				// but it makes the above code cleaner
				return;
			}

			// ------------------------------------------------------
			// now we should be doing the deputization
			// as we are only doing a simple search/replace this is
			// why we REQUIRE a "prefix" as to not destroy any
			// HTML tags etc

			$deputies = $session->getData("fanplayr_deputy_codes");

			// cookie fallback
			if (!$deputies) {
				$cookie = Mage::getSingleton('core/cookie');
				$d = $cookie->get('fanplayr_deputy_codes');
				if ($d) $deputies = (Array)json_decode($d);
			}

			$html     = $response->getBody();

			foreach($lastDeputiesUsed as $lastDeputyUsed){
				if (is_array($deputies) && array_key_exists($lastDeputyUsed, $deputies)) {

					// replace deputies
					if (array_key_exists($lastDeputyUsed, $deputies))
						$html = str_replace($deputies[$lastDeputyUsed], $lastDeputyUsed, $html);
				}
			}

			$response->setBody($html);
		}

		// used when a user inputs a coupon,
		// called on controller_action_predispatch
		public function deputizeInputAction($observer)
		{
			$r = Mage::app()->getRequest();
			$pCnt = $r->getControllerName(); // onepage, multishipping, cart
			$pAct = $r->getActionName(); // index
			$pRte = $r->getRouteName(); // checkout
			$pMod = $r->getModuleName(); // checkout

			$route = $pCnt . '/' . $pAct . '/' . $pRte . '/' . $pMod;

			// which input do we use?
			$inpVar = 'coupon_code';
			$inpVarIsArray = false;
			$setPost = false;

			// one step checkout
			if ($route == 'ajax/add_coupon/onestepcheckout/onestepcheckout') {
				$inpVar = 'code';
			}

			// amasty cancel
			if ($route == 'checkout/cancelCoupon/amcoupons/amcoupons') {
				$inpVar = 'amcoupon_code_cancel';
			}

			// AIT Checkout
			if ($route == 'cart/couponPost/aitcheckout/aitcheckout') {
				// it uses 'getPost' rather than 'getParam'
				$setPost = true;
			}

			// StreamCheckout: uses POST
			if ($route == 'index/applyCoupon/streamcheckout/streamcheckout') {
				$setPost = true;
			}

			// special for StreamCheckout SaveOrder
			if ($route == 'index/saveOrder/streamcheckout/streamcheckout') {
				$setPost = true;
				$inpVar = 'stream_coupon_code';
			}

			// FireCheckout
			if ($route == 'index/saveCoupon/firecheckout/firecheckout') {
				$setPost = true;
				$inpVar = 'coupon';
				// it's an array so had to add a bunch of extra stuff :(
				$inpVarIsArray = 'code';
			}

			// IWD: for some reason we don't need to do on set ...
			// ROUTE: coupon/couponPost/opc/onepage
			// VAR: coupon_code
			// isPost

			$depCoupon = Mage::app()->getRequest()->getParam($inpVar); // normal coupon
			if ($inpVarIsArray) {
				if (is_array($depCoupon) && array_key_exists($inpVarIsArray, $depCoupon)) {
					$depCoupon = $depCoupon[$inpVarIsArray];
				}else{
					// error getting coupon for array fields
					return;
				}
			}
			$disallowedPrefix = Mage::getStoreConfig('fanplayrsocialcoupons/config/dep_prefix');

			// remove spaces
			$depCoupon = trim($depCoupon);

			$session = Mage::getSingleton("core/session");

			// <SESSION_COUPON
			$disallowUsingOldPrefixMethod = true;
			$allowedCoupons = $session->getData("fanplayr_session_codes");
			if ( $allowedCoupons && is_array($allowedCoupons) ) {
				if ( in_array($depCoupon, $allowedCoupons) ) {
					// let's let this coupon through ...
					$disallowUsingOldPrefixMethod = false;
				}
			}
			// SESSION_COUPON>

			if ( $disallowUsingOldPrefixMethod ) {
				// if someone enters the actual code, and it is a prefix code
				// then set it to prefix with "..." on the end
				if (strpos($depCoupon, $disallowedPrefix) === 0) {
					Mage::app()->getRequest()->setParam($inpVar, $disallowedPrefix . '...');
					if ($setPost){
						if ($inpVarIsArray) {
							$tmpIn = Mage::app()->getRequest()->getPost($inpVar);
							$tmpIn[$inpVarIsArray] = $disallowedPrefix . '...';
							Mage::app()->getRequest()->setPost($inpVar, $tmpIn);
						}else{
							Mage::app()->getRequest()->setPost($inpVar, $disallowedPrefix . '...');
						}
					}
					return;
				}
			}

			$deputies = $session->getData("fanplayr_deputy_codes");

			// cookie fallback
			if (!$deputies) {
				$cookie = Mage::getSingleton('core/cookie');
				$d = $cookie->get('fanplayr_deputy_codes');
				if ($d) $deputies = (Array)json_decode($d);
			}

			if (is_array($deputies) && array_key_exists($depCoupon, $deputies)) {
				$actualCoupon = array_key_exists(strtoupper($depCoupon), $deputies) ? $deputies[strtoupper($depCoupon)] : '';

				// must be set first to get actual coupon
				// need to set as array, to support those horrible multi-coupon extensions
				$sessionDeps = $session->getData('fanplayr_used_deputies');
				if (!is_array($sessionDeps))
					$sessionDeps = array();
				// and only add it if it's not there so no duplicates
				if (!in_array($depCoupon, $sessionDeps))
					array_push($sessionDeps, $depCoupon);
				$session->setData('fanplayr_used_deputies', $sessionDeps);

				Mage::app()->getRequest()->setParam($inpVar, $actualCoupon);
				if ($setPost){
					if ($inpVarIsArray) {
						$tmpIn = Mage::app()->getRequest()->getPost($inpVar);
						$tmpIn[$inpVarIsArray] = $actualCoupon;
						Mage::app()->getRequest()->setPost($inpVar, $tmpIn);
					}else{
						Mage::app()->getRequest()->setPost($inpVar, $actualCoupon);
					}
				}
			}
		}

		// when adding a product we should refresh product info on next call
		public function checkQuoteAddAction($observer)
		{
			$session = Mage::getSingleton("core/session");
			$session->setData("fanplayr_update_product_details", true);
		}

	// when removing a product we should refresh product info on next call
		public function checkQuoteRemoveAction($observer)
		{
			$session = Mage::getSingleton("core/session");
			$session->setData("fanplayr_update_product_details", true);
		}

		// when updating a product we should refresh product info on next call
		// we only do this for checkout 'updatePost' and 'delete' actions though
		// update is called many many times for simple things like cart views
		public function checkQuoteUpdateQtyAction($observer)
		{
			$r = Mage::app()->getRequest();
			$pCnt = $r->getControllerName(); // onepage, multishipping, cart
			$pAct = $r->getActionName(); // index
			$pRte = $r->getRouteName(); // checkout
			$pMod = $r->getModuleName(); // checkout

			$route = "$pMod/$pRte/$pAct/$pCnt";
			if ($route == "checkout/checkout/updatePost/cart"
					|| $route == "checkout/checkout/delete/cart") {
				$session = Mage::getSingleton("core/session");
				$session->setData("fanplayr_update_product_details", true);
			}
		}

		// -------------------------------------------------------------------
		// gets an array of all products from cart with needed details for tracking
		// quite expensive so should only be called when it has changed

		private static function getProductsFromQuote($quote)
		{
			$products = array();

			foreach ($quote->getAllVisibleItems() as $item) {

				if ($option = $item->getOptionByCode('simple_product')) {
					$p = $option->getProduct();
				} else {
					$p = $item->getProduct();
				}

				// bug in Magento 1.5 and below?
				// just make sure it doesn't break things!
				$sku = '';
				try { $sku = !$p ? '' : $p->getSku(); }catch(Exception $e){}

				$products[] = array(
					'qty' => $item->getQty(),
					'price' => $item->getCalculationPrice(),
					'catName' => '',
					'id' => !$p ? '' : $p->getId(),
					'sku' => $sku,
					'name' => $item->getName(),
					'catId' => !$p ? '' : implode(',', $p->getCategoryIds())
				);
			}

			return $products;
		}

		// -------------------------------------------------------------------
		// decode session cookie

		// in case sessions aren't being sent through correctly
		// we will store this each hit, and then get it back later

		private function decodeSessionCookie( $cookie_name )
		{
			if ( array_key_exists($cookie_name, $_COOKIE) && !empty($_COOKIE[$cookie_name]) ) {
				$data   = array();
				$params = explode(';', $_COOKIE[$cookie_name]);

				foreach ( $params as $param ) {
					$parts = explode('=', $param);
					if (count($parts) != 2)
						continue;

					$type  = substr($parts[1], 0, 1);
					$value = substr($parts[1], 1);

					if ( empty($type) ) continue;

					if ( $type === 'n' || $type === 'u' ) { $value = null; }
					if ( $type === 'b' )                  { $value = (intval($value, 10) !== 0); }
					if ( $type === 'f' )                  { $value = floatval($value); }
					if ( $type === 's' )                  { $value = urldecode($value); }

					$data[urldecode($parts[0])] = $value;
				}

				return $data;
			}
			return null;
		}
	}