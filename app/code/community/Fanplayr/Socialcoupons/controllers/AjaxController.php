<?php
	class Fanplayr_Socialcoupons_AjaxController extends Mage_Core_Controller_Front_Action
	{
		public function indexAction()
		{
			echo $this->jsonMessage(true, 'Please use a valid method.');
		}

		public function getEmbedJsAction()
		{
			// store session data in case the cookie is lost somehow
			$sessionVars = $this->decodeSessionCookie('fanplayr_genius_session');
			if ($sessionVars) {
				$session = Mage::getModel('core/session');
				if ($session){
					if (array_key_exists('key', $sessionVars)) $session->setData('fanplayr_session_session_key',$sessionVars['key']);
					if (array_key_exists('user', $sessionVars)) $session->setData('fanplayr_session_user_key', $sessionVars['user']);
				}
			}

			// now do the rest ...
			$p = $this->getRequest()->getParams();

			$block = new Mage_Core_Block_Template();
			$block->setTemplate('fanplayr/embed.phtml');

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

			$block->assign('widgetKeys', Mage::getStoreConfig('fanplayrsocialcoupons/config/widget_keys'));
			$block->assign('widgetKeysGenius', Mage::getStoreConfig('fanplayrsocialcoupons/config/widget_keys_genius'));
			$block->assign('accountKey', Mage::getStoreConfig('fanplayrsocialcoupons/config/acc_key'));
			$block->assign('deputizeUrl', $shopUrl . 'fanplayr/coupon/deputize/a/%a/d/%d/');
			$block->assign('sessionCouponUrl', $shopUrl . 'fanplayr/coupon/session/code/%c/');

			$block->assign('noTags', true);

			// get data and add it ...
			$data = Fanplayr_Socialcoupons_Model_EmbedObserver::getData(
				array_key_exists('ac', $p) ? $p['ac'] == '1' : false,
				array_key_exists('tt', $p) ? $p['tt'] : '',
				array_key_exists('tp', $p) ? $p['tp'] : '',
				array_key_exists('tc', $p) ? $p['tc'] : '',
				array_key_exists('tpn', $p) ? $p['tpn'] : '',
				array_key_exists('tcn', $p) ? $p['tcn'] : '',

				array_key_exists('tpp', $p) ? $p['tpp'] : '',
				array_key_exists('tps', $p) ? $p['tps'] : '',
				array_key_exists('tpi', $p) ? $p['tpi'] : '',
				array_key_exists('tpu', $p) ? $shopUrl . '/' . $p['tpu'] : ''
			);

			$disableAtcEmpty = htmlspecialchars(Mage::getStoreConfig('fanplayrsocialcoupons/config/disable_atc_empty'));
			if ( $data['lineItemCount'] === 0 && $disableAtcEmpty === '1' ) {
				$block->assign('applyToCartUrl', '');
			} else {
				$block->assign('applyToCartUrl', $shopUrl . 'fanplayr/coupon/add/code/%c/');
			}

			$block->assign('data', $data);

			$embedType = intval(Mage::getStoreConfig('fanplayrsocialcoupons/config/wait_for_onload'));
			$block->assign('embedType', $embedType);

			$this->getResponse()
				->clearHeaders()
				->setHeader('Cache-Control', 'no-cache, must-revalidate')
				->setHeader('Expires', 'Mon, 26 Jul 1997 05:00:00 GMT')
				->setHeader('Content-Type', 'text/javascript')
				->setBody($block->toHtml());
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