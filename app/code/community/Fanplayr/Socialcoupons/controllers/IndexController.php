<?php
	class Fanplayr_Socialcoupons_IndexController extends Mage_Core_Controller_Front_Action {        

		public function indexAction()
		{
			echo $this->jsonMessage(true, 'Please call use a valid controller.');
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