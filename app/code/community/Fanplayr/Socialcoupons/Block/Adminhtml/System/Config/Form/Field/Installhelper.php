<?php

class Fanplayr_Socialcoupons_Block_Adminhtml_System_Config_Form_Field_Installhelper extends Mage_Adminhtml_Block_System_Config_Form_Field
{
	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
	{

		// ------------------------------------------------------------------------
		// get local details
		$secret = Mage::getStoreConfig('fanplayrsocialcoupons/config/secret');
		$secretInner = Mage::getStoreConfig('fanplayrsocialcoupons/config/secret_inner');
		$accKey = Mage::getStoreConfig('fanplayrsocialcoupons/config/acc_key');
		$shopId = Mage::getStoreConfig('fanplayrsocialcoupons/config/shop_id');

		if (empty($secret)) {
			$secret = md5(uniqid("Things are fine, seriously. I love this!", true));
			$this->updateConfig('fanplayrsocialcoupons/config/secret', $secret);
		}
		if (empty($secretInner)) {
			$secretInner = md5(uniqid("And other things are great ...", true));
			$this->updateConfig('fanplayrsocialcoupons/config/secret_inner', $secretInner);
		}

		// default some others
		$prefix = Mage::getStoreConfig('fanplayrsocialcoupons/config/dep_prefix');
		if (empty($prefix)) {
			$this->updateConfig('fanplayrsocialcoupons/config/dep_prefix', 'F2P9P31R_');
		}

		// setup vars in case we need 'em
		// ------------------------------------------------------------------------
		// get current admin user info
		$userId = Mage::getSingleton('admin/session')->getUser()->getId();
        $user = Mage::getModel('admin/user')->load($userId);
		$userData = $user->getData();

		// get details to send along ...
		$firstname = array_key_exists('firstname', $userData) ? $userData['firstname'] : '';
		$lastname = array_key_exists('lastname', $userData) ? $userData['lastname'] : '';
		$email = array_key_exists('email', $userData) ? $userData['email'] : '';
		$shopUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);

		$customShopUrl = Mage::getStoreConfig('fanplayrsocialcoupons/config/custom_url');
		$customShopUrlUsed = true;
		if (empty($customShopUrl)) {
			$customShopUrlUsed = false;
			$customShopUrl = $shopUrl;
		}
		$shopUrl = $customShopUrl;

		$customEmbedUrl = Mage::getStoreConfig('fanplayrsocialcoupons/config/custom_embed_url');

		$shopName = Mage::getStoreConfig('general/store_information/name');
		$shopPhone = Mage::getStoreConfig('general/store_information/phone');
		$shopAddress = Mage::getStoreConfig('general/store_information/address');
		$shopCountry = Mage::getStoreConfig('general/country/default');
		$shopTz = Mage::getStoreConfig('general/locale/timezone');

		$adminUrlFull = Mage::helper('adminhtml')->getUrl('/');
		$adminUrl = substr($adminUrlFull, 0, strpos($adminUrlFull, 'index/index'));

		// add index.php to shop URL, just in case they don't have URL rewriting ong
		if (!$customShopUrlUsed){
			if (!strpos($shopUrl, 'index.php/'))
				$shopUrl .= 'index.php/';
		}

		$logoSrc = 'http://localhost/magento/skin/frontend/default/default/' . Mage::getStoreConfig('design/header/logo_src');
		$skinDir = Mage::getBaseUrl('skin').'frontend/socialcoupons';

		$queryString = 'secret=' . urlencode($secret);
		$queryString .= '&email=' . urlencode($email);
		$queryString .= '&name=' . urlencode($firstname . ' ' . $lastname);
		$queryString .= '&shopUrl=' . urlencode($shopUrl);
		$queryString .= '&shopName=' . urlencode($shopName);
		$queryString .= '&adminUrl=' . urlencode($adminUrl);

		$queryString .= '&phone=' . urlencode($shopPhone);
		//$queryString .= '&address=' . urlencode($shopAddress);
		$queryString .= '&address=';
		$queryString .= '&country=' . urlencode($shopCountry);
		$queryString .= '&tz=' . urlencode($shopTz);

		$queryString .= '&authUser=' . urlencode(array_key_exists('PHP_AUTH_USER', $_SERVER) ? $_SERVER['PHP_AUTH_USER'] : '');
		$queryString .= '&authPass=' . urlencode(array_key_exists('PHP_AUTH_PW', $_SERVER) ? $_SERVER['PHP_AUTH_PW'] : '');

		// setup our general always needed HTML
		// ------------------------------------------------------------------------
		$mageVersion = Mage::getVersion();

		$gamafiedKeys = htmlspecialchars(Mage::getStoreConfig('fanplayrsocialcoupons/config/widget_keys'));
		$sntKeys = htmlspecialchars(Mage::getStoreConfig('fanplayrsocialcoupons/config/widget_keys_genius'));
		$disableOnUrls = htmlspecialchars(Mage::getStoreConfig('fanplayrsocialcoupons/config/disable_on_urls'));

		$depPrefix = htmlspecialchars(Mage::getStoreConfig('fanplayrsocialcoupons/config/dep_prefix'));
		$depRoutes = htmlspecialchars(Mage::getStoreConfig('fanplayrsocialcoupons/config/dep_extra_rewrite_routes'));
		$gtmContainerId = htmlspecialchars(Mage::getStoreConfig('fanplayrsocialcoupons/config/gtm_container_id'));
		$customEmbedUrlPost = htmlspecialchars(Mage::getStoreConfig('fanplayrsocialcoupons/config/custom_embed_url_post'));

		$useTbuy = htmlspecialchars(Mage::getStoreConfig('fanplayrsocialcoupons/config/use_tbuy'));

		$disableUserIdentifyTracking = htmlspecialchars(Mage::getStoreConfig('fanplayrsocialcoupons/config/disable_user_identity_tracking'));
		$couponApplyUtm = htmlspecialchars(Mage::getStoreConfig('fanplayrsocialcoupons/config/coupon_apply_utm'));

		// have to use this variable for backwards compatability ...
		$currentEmbedType = htmlspecialchars(Mage::getStoreConfig('fanplayrsocialcoupons/config/wait_for_onload'));
		if (!$currentEmbedType) $currentEmbedType = '0';
		$embedOptions = '';
		$embedOptions .= '<option value="0"'.($currentEmbedType=='0'||!$currentEmbedType?' selected':'').'>Defered Script: Normal</option>';
		$embedOptions .= '<option value="1"'.($currentEmbedType=='1'?' selected':'').'>Defered Script: Lazy Loading</option>';
		$embedOptions .= '<option value="2"'.($currentEmbedType=='2'?' selected':'').'>Tag Management System (User Only)</option>';
		$embedOptions .= '<option value="3"'.($currentEmbedType=='3'?' selected':'').'>Tag Management System (User &amp; Order)</option>';
		$embedOptions .= '<option value="4"'.($currentEmbedType=='4'?' selected':'').'>Direct Embed</option>';
		$embedOptions .= '<option value="5"'.($currentEmbedType=='5'?' selected':'').'>Direct Embed: Lazy Loading</option>';
		$embedOptions .= '<option value="6"'.($currentEmbedType=='6'?' selected':'').'>Google Tag Manager</option>';
		$embedOptions .= '<option value="6"'.($currentEmbedType=='7'?' selected':'').'>Google Tag Manager (Universal Analytics Enhanced Ecommerce)</option>';

		$currentLayoutHookHome = Mage::getStoreConfig('fanplayrsocialcoupons/config/layout_hook_home');
		if (!$currentLayoutHookHome) $currentLayoutHookHome = 'content';
		$layoutHooksHome = '';
		$layoutHooksHome .= '<option value="content"'.($currentLayoutHookHome=='content'?' selected':'').'>content</option>';
		$layoutHooksHome .= '<option value="footer.before"'.($currentLayoutHookHome=='footer.before'?' selected':'').'>footer.before</option>';
		$layoutHooksHome .= '<option value="before_body_end"'.($currentLayoutHookHome=='before_body_end'?' selected':'').'>before_body_end</option>';

		$currentLayoutHook = Mage::getStoreConfig('fanplayrsocialcoupons/config/layout_hook');
		if (!$currentLayoutHook) $currentLayoutHook = 'content';
		$layoutHooks = '';
		$layoutHooks .= '<option value="content"'.($currentLayoutHook=='content'?' selected':'').'>content</option>';
		$layoutHooks .= '<option value="footer.before"'.($currentLayoutHook=='footer.before'?' selected':'').'>footer.before</option>';
		$layoutHooks .= '<option value="before_body_end"'.($currentLayoutHook=='before_body_end'?' selected':'').'>before_body_end</option>';

		$currentLayoutHookOrder = Mage::getStoreConfig('fanplayrsocialcoupons/config/layout_hook_order');
		if (!$currentLayoutHookOrder) $currentLayoutHookOrder = 'content';
		$layoutHooksOrder = '';
		$layoutHooksOrder .= '<option value="content"'.($currentLayoutHookOrder=='content'?' selected':'').'>content</option>';
		$layoutHooksOrder .= '<option value="footer.before"'.($currentLayoutHookOrder=='footer.before'?' selected':'').'>footer.before</option>';
		$layoutHooksOrder .= '<option value="before_body_end"'.($currentLayoutHookOrder=='before_body_end'?' selected':'').'>before_body_end</option>';

		$customShopUrlEdit = (empty($accKey) && empty($customShopUrl)) ? $shopUrl : $customShopUrl;

		// ------------------------------------------------------------------------
		// check that all themes have FP templates!

		// we may need to add Fanplayr templates to other directories
		// is this just for default though?
		$templatesWithNoFanplayr = array();

		foreach(array(getcwd().'/app/design/frontend/default/', getcwd().'/app/design/frontend/enterprise/') as $path) {
			$dir = opendir($path);
			while($d = readdir($dir)){
				if (is_dir($path . $d) && $d != '.' && $d != '..') {
					if (is_dir($path . $d . '/template') && !is_dir($path . $d . '/template/fanplayr')){
						array_push($templatesWithNoFanplayr, $d);
					}
				}
			}
		}

		// ------------------------------------------------------------------------

		$generalHtml = <<<EOT
			<script>
				var c = document.createElement("link");
				c.setAttribute("rel", "stylesheet");
				c.setAttribute("type", "text/css");
				c.setAttribute("href", '{$skinDir}/fanplayr_socialcoupons.css');
				document.getElementsByTagName("head")[0].appendChild(c);
			</script>
			<script>
				$.noConflict()(function($) {
					// remove unneeded TDs, we want all the space!
					// hope this is OK with the "Magento Way"?
					// v1.4.1 and above

					$('#row_fanplayrsocialcoupons_config_installed td:eq(0)').remove();
					$('#row_fanplayrsocialcoupons_config_installed td.scope-label').remove();
					//$('#row_fanplayrsocialcoupons_config_installed td:eq(2)').remove();
					//$('#row_fanplayrsocialcoupons_config_installed td').css('width', 'auto');

					if ("{$mageVersion}" == "1.4.0"){
						// for v1.4.0
						$('#fanplayrsocialcoupons_config td:eq(0)').remove();
						$('#fanplayrsocialcoupons_config td:eq(1)').remove();
						$('#fanplayrsocialcoupons_config td:eq(1)').remove();
						$('#fanplayrsocialcoupons_config td').css('width', 'auto');
					}

					var fieldSet = fanplayrJQuery('#fanplayrsocialcoupons_config-head');
					if (!fieldSet.hasClass('open')) {
						// open it automatically !
						try {
							var a = fieldSet.attr('onclick');
							eval(a.substr(0, a.indexOf('return')));
						}catch(e){}
					}

					// set up some vars we need
					Fanplayr.configAccKey = "{$accKey}";
					Fanplayr.configSecret = "{$secret}";
					Fanplayr.configSecretInner = "{$secretInner}";
					Fanplayr.configShopId = "{$shopId}";
					Fanplayr.configShopUrl = "{$shopUrl}";
				});
			</script>
			<div id="fanplayrsocialcoupons-console-simple">
				<table>
					<tr><td><label></label></td><td></td></tr>
					<tr><td><label>Account Key</label></td><td><input type="text" id="fanplayrsocialcoupons-console-acckey" name="fanplayrsocialcoupons-console-acckey" value="{$accKey}" /></td></tr>
					<tr><td><label>Secret</label></td><td><input type="text" id="fanplayrsocialcoupons-console-secret" name="fanplayrsocialcoupons-console-secret" value="{$secret}" /></td></tr>
					<tr><td><label>Shop ID</label></td><td><input type="text" id="fanplayrsocialcoupons-console-shopid" name="fanplayrsocialcoupons-console-shopid" value="{$shopId}" /></td></tr>
				</table>
			</div>
			<div id="fanplayrsocialcoupons-console" style="display:none;">
				<div>
					<p>Please be very careful when editing these values manually. Not for the faint of heart.</p>
					<p>Disable on URLs: please provide a comma separated list of URLs on which to disable Fanplayr. By default Fanplayr is disabled for URLs containing "/checkout/onepage/" and "/checkout/multishipping/".</p>
					<p>Embed Type: Do not use "Direct Embed" if you are using a Full Page Cache.</p>
				</div>
				<table>
					<tr><td><label></label></td><td></td></tr>
					<tr><td><label>Custom URL</label></td><td><input type="text" id="fanplayrsocialcoupons-console-url" name="fanplayrsocialcoupons-console-url" value="{$customShopUrlEdit}" /></td></tr>
					<tr><td><label>Gamafied</label></td><td><input type="text" id="fanplayrsocialcoupons-console-gamafied" name="fanplayrsocialcoupons-console-gamafied" value="{$gamafiedKeys}" /></td></tr>
					<tr><td><label>S &amp; T</label></td><td><input type="text" id="fanplayrsocialcoupons-console-snt" name="fanplayrsocialcoupons-console-snt" value="{$sntKeys}" /></td></tr>
					<tr><td><label>Disable on URLs</label></td><td><input type="text" id="fanplayrsocialcoupons-console-disableonurls" name="fanplayrsocialcoupons-console-disableonurls" value="{$disableOnUrls}" /></td></tr>
					<tr><td><label>Embed Type</label></td><td><select size="1" id="fanplayrsocialcoupons-console-embedtype" name="fanplayrsocialcoupons-console-embedtype">{$embedOptions}</select></td></tr>
					<tr><td><label>Layout Hook (User: Home)</label></td><td><select size="1" id="fanplayrsocialcoupons-console-layouthookhome" name="fanplayrsocialcoupons-console-layouthookhome">{$layoutHooksHome}</select></td></tr>
					<tr><td><label>Layout Hook (User: Other)</label></td><td><select size="1" id="fanplayrsocialcoupons-console-layouthook" name="fanplayrsocialcoupons-console-layouthook">{$layoutHooks}</select></td></tr>
					<tr><td><label>Layout Hook (Order)</label></td><td><select size="1" id="fanplayrsocialcoupons-console-layouthookorder" name="fanplayrsocialcoupons-console-layouthookorder">{$layoutHooksOrder}</select></td></tr>
					<tr><td colspan="2"><small>Please note this may cause problems on multi-site if you use different "websites".</small></td></tr>
					<tr><td><label>Custom Embed URL</label></td><td><input type="text" id="fanplayrsocialcoupons-console-customembedurl" name="fanplayrsocialcoupons-console-customembedurl" value="{$customEmbedUrl}" /></td></tr>
					<tr><td><label>Custom Embed URL (Postfix)</label></td><td><input type="text" id="fanplayrsocialcoupons-console-customembedurlpost" name="fanplayrsocialcoupons-console-customembedurlpost" value="{$customEmbedUrlPost}" /></td></tr>

					<tr><td colspan="2"><b>Deputization</b></td></tr>
					<tr><td><label>Prefix</label></td><td><input type="text" id="fanplayrsocialcoupons-console-depprefix" name="fanplayrsocialcoupons-console-depprefix" value="{$depPrefix}" /></td></tr>
					<tr><td><label>Extra Routes</label></td><td><input type="text" id="fanplayrsocialcoupons-console-deproutes" name="fanplayrsocialcoupons-console-deproutes" value="{$depRoutes}" /></td></tr>
					<tr><td><label>GTM Container Public ID</label></td><td><input type="text" id="fanplayrsocialcoupons-console-gtmcontainerid" name="fanplayrsocialcoupons-console-gtmcontainerid" value="{$gtmContainerId}" /></td></tr>

					<tr><td><label title="Set to '1' to disable">Disable TBuy Discounts</label></td><td><input type="text" id="fanplayrsocialcoupons-console-usetbuy" name="fanplayrsocialcoupons-console-usetbuy" value="{$useTbuy}" /></td></tr>

					<tr><td><label title="Set to '1' to disable">Disable User Identity Tracking</label></td><td><input type="text" id="fanplayrsocialcoupons-disableuseridentitytracking" name="fanplayrsocialcoupons-disableuseridentitytracking" value="{$disableUserIdentifyTracking}" /></td></tr>

					<tr><td><label title="If set, the string will be added to the cart URL after a '?'. ie 'utm_source=Shop&utm_medium=banner&utm_campaign=FanPlayr'">Apply to Cart UTM</label></td><td><input type="text" id="fanplayrsocialcoupons-couponapplyutm" name="fanplayrsocialcoupons-couponapplyutm" value="{$couponApplyUtm}" /></td></tr>

					<tr><td colspan="2"><a href="#" id="fanplayrsocialcoupons-console-hide" onclick="Fanplayr.console.hide(); return false;">Hide</a></td></tr>

				</table>
			</div>

			<div id="fanplayrsocialcoupons-console-action">
				<table>
					<tr><td colspan="2"><a href="#" id="fanplayrsocialcoupons-console-save" onclick="Fanplayr.console.save(); return false;">Save</a></td></tr>
				</table>
				<div id="fanplayrsocialcoupons-console-saving" style="display: none;"><b>Please be patient, saving ...</b></div>
			</div>
EOT;
		if (array_key_exists('fanplayrsocialcoupons-console-display', $_COOKIE) && $_COOKIE['fanplayrsocialcoupons-console-display'] == 'show') {
			$generalHtml .=  <<< EOT
				<script>
					document.getElementById('fanplayrsocialcoupons-console').style.display = 'block';
				</script>
EOT;
		}

		// actual logic time !
		// ------------------------------------------------------------------------

		// the HTML to output to the admin control
		$ouputHtml = '';

		if (empty($secret) || empty($accKey) || empty($shopId)) {

			// our install HTML
			$installHtml = <<<EOT
				<div id="fanplayr-install-wrapper">
					<p>
						<img src="{$skinDir}/images/fanplayr_logo.png" width="200" height="65" alt="Fanplayr Logo" title="Fanplayr" />
					</p>
					<div id="fanplayr-install-description">
						<p>Welcome to Fanplayr, the leader in targeted conversions.</p>
						<p>Please enter your account details below. You can get these from your <a href="//fanplayr.com/contact/" target="_blank">Fanplayr account manager</a>.</p>
					</div>
				</div>
EOT;
			$outputHtml = $installHtml;
		} else {
			$errorGettingInstallData = false;
			try {
				$m = json_decode($this->httpGetContent('http://my.fanplayr.com/api.magentoCheckInstall/', array(
					'acc_key' => $accKey,
					'shop_id' => $shopId,
					'secret' => $secret,
					'version' => $this->getExtensionVersion()
				)));
			}catch (Exception $e) {
				$errorGettingInstallData = true;
			}
			if (!$m) $errorGettingInstallData = true;

			// just give 'em an error
			if ($errorGettingInstallData) {
				$installHtml = <<<EOT
					<div id="fanplayr-error-wrapper">
						<p>
							<img src="{$skinDir}/images/fanplayr_logo.png" width="200" height="65" alt="Fanplayr Logo" title="Fanplayr" />
						</p>
						<div id="fanplayr-error-description">
							<p>Sorry, there was a problem with your account details.</p>
							<p>Please enter your account details below. You can get these from your <a href="//fanplayr.com/contact/" target="_blank">Fanplayr account manager</a>.</p>
						</div>
					</div>
EOT;
			$outputHtml = $installHtml;
			}else{
				// the server said there was an error
				if ($m->error) {
					$outputHtml = 'ERROR: ' . $m->message . ' - This may be due to a wrongly linked account. To unlink your account and try again <a href="#" onclick="Fanplayr.unlink();">click here</a>.';
				// all is good, let's continue!
				}else{

					// if it's a new version warn 'em!
					$newVersionWarningHtml = '';

					if ($m->newVersion) {
						$newVersionWarningHtml = $m->newVersion;
					}

					try {
						$campData = addslashes($this->httpGetContent('http://my.fanplayr.com/api.magentoGetCampaigns/all/1/', array(
							'acc_key' => $accKey,
							'shop_id' => $shopId,
							'secret' => $secret,
							'version' => $this->getExtensionVersion()
						)));
					} catch (Exception $e) {
						$campData = '';
					}

					$outputHtml = '';

					if ($newVersionWarningHtml){
						$outputHtml .= '<div id="fanplayr-new-version">' . $newVersionWarningHtml . '</div>';
					}else {
						$outputHtml .= '';
					}

					$outputHtml .= "<script> fanplayrCampData = null; try { fanplayrCampData = ";
					$outputHtml .= $campData == '' ? 'null;' : 'fanplayrJQuery.parseJSON("'.$campData.'");';
					$outputHtml .= "} catch(e){} </script>";

					if (count($templatesWithNoFanplayr)) {
						$outputHtml .= '<div id="fanplayr-template-warning"><span>'.count($templatesWithNoFanplayr).'</span> Magento templates do not include Fanplayr templates. This could cause Fanplayr to incorrectly track visitors on some or all pages. Please click the "Add Templates" button below.<br /><br />Optionally, you can do this manually by copying the Fanplayr templates from /app/design/frontend/base/default/template/fanplayr into the Magento templates in question.';
						$outputHtml .= '<br /><br /><button onclick="Fanplayr.addTemplates(); return false;" id="fanplayr-template-warning-add" title="Add Templates" type="button" class="scalable save"><span>Add Templates</span></button>';
						$outputHtml .= '</div>';
					}

					$outputHtml .= <<<EOT
						<script>
							fanplayrJQuery().ready(function() {
								var $ = fanplayrJQuery;

								$('#fanplayr-none-wrapper, #fanplayr-draft-wrapper, #fanplayr-head-wrapper').css('display','none');

								Fanplayr.fillCampaignList($('#fanplayr-campaign-list'), fanplayrCampData);

								if (Fanplayr.hasDraftCampaigns || Fanplayr.hasPublishedCampaigns || Fanplayr.hasRunningCampaigns) {
									if (Fanplayr.hasPublishedCampaigns || Fanplayr.hasRunningCampaigns) {
										$('#fanplayr-head-wrapper').css('display', 'block');
									}else {
										$('#fanplayr-draft-wrapper').css('display', 'block');
									}
								}
							});
							function refreshPage() {
								window.location.reload();
							}
						</script>

						<div id="fanplayr-none-wrapper">
							<p>
								<img src="{$skinDir}/images/fanplayr_logo.png" width="200" height="65" alt="Fanplayr Logo" title="Fanplayr" />
							</p>
							<div id="fanplayr-none-description">
								<p>Your Store has been linked to Fanplayr, but you still need to create a campaign. Click below to get started.</p>
								<p>You will have to <a href="#" onclick="refreshPage();">refresh</a> the page to see campaigns once you have created them.</p>
							</div>
							<a href="http://my.fanplayr.com/login/" id="fanplayr-start-button" target="_blank"><div class="fanplayr-icon fanplayr-icon-star"></div>Create a Fanplayr Campaign</a>
						</div>
						<script>
							fanplayrJQuery().ready(function() {
								if (fanplayrCampData == null || fanplayrCampData.campaigns.length == 0)
									fanplayrJQuery('#fanplayr-none-wrapper').css('display', 'block');
							});
						</script>

						<div id="fanplayr-draft-wrapper">
							<p>
								<img src="{$skinDir}/images/fanplayr_logo.png" width="200" height="65" alt="Fanplayr Logo" title="Fanplayr" />
							</p>
							<div id="fanplayr-draft-description">
								<p>You've got a campaign but it's not quite ready to show on your store. Edit a current campaign, and once it's published you can show it on your Store.</p>
							</div>
						</div>

						<div id="fanplayr-head-wrapper">
							<p>
								<img src="{$skinDir}/images/fanplayr_logo.png" width="200" height="65" alt="Fanplayr Logo" title="Fanplayr" />
							</p>
							<div id="fanplayr-head-description">
								<p>Awesome. Looks like there's campaigns that can be running on the store!</p>
							</div>
						</div>

						<table cellspacing="0" id="fanplayr-campaign-list"></table>
EOT;
				}
			}
		}

		$showConsoleHtml = '<div id="fanplayr-show-console"><a href="javascript:Fanplayr.console.show();" style="margin-bottom: 15px; display: block;">Show Fanplayr Console</a></div>';

		//
		// Return
		$jsHtml = <<<EOT
			<script type="text/javascript" src="{$skinDir}/jquery-1.7.2.min.js"></script>
			<script type="text/javascript" src="{$skinDir}/fanplayr_socialcoupons.js"></script>
EOT;
		return $jsHtml . $showConsoleHtml . $outputHtml . $generalHtml;
	}

	// -------------------------------------------------------------------------
	// helpers

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
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

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

	public function updateConfig($key, $value)
	{
		Mage::getConfig()->saveConfig($key, $value);
		Mage::getConfig()->reinit();
		Mage::app()->reinitStores();
	}
}