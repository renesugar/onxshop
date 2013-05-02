<?php
/** 
 * Copyright (c) 2013 Laposa Ltd (http://laposa.co.uk)
 * Licensed under the New BSD License. See the file LICENSE.txt for details.
 *
 */

require_once 'controllers/component/client/twitter.php';


class Onxshop_Controller_Component_Client_Twitter_Auth extends Onxshop_Controller_Component_Client_Twitter {

	/**
	 * mainAction
	 */
	
	public function mainAction() {
		
		// start auth on request (clicked on login via Twitter button) 
		if ($_POST['twitter_auth']) {
			$this->oAuth();
		}
		
		// endSession() signs users out of client-facing applications.
		if ($this->GET['twitter_logout']) {
			$response   = $this->twitter->account->endSession();
		}
		
		// when comming back from Twitter, oauth_token and oauth_verifier is provided in GET
		if ($this->GET['oauth_token'] && $this->GET['oauth_verifier']) {
			
			//print_r($_SESSION['TWITTER_REQUEST_TOKEN']);
			if (isset($_SESSION['TWITTER_REQUEST_TOKEN'])) $this->oAuthCallback();
			
		}
		
		$token = $this->getAccessToken();
		
		$this->initTwitter($this->getTwitterUsername(), $token);
		
		// verifyCredentials() tests if supplied user credentials are valid with minimal overhead.
		if ($token && $this->twitter->isAuthorised()) {
			
			$user_profile = $this->twitterCall('accountVerifyCredentials');
			
			if ($user_profile) {
				
				$this->tpl->assign('USER_PROFILE', $user_profile);
				
				if (is_numeric($user_profile->id)) {
					//try to login
					$this->loginToOnxshop($user_profile);
				}
				
			} else {
				
				//don't cache the actual controler
				return false;
				
			}
			
			$this->tpl->parse('content.authorised');
			
		} else {
			
			$this->tpl->parse('content.login');
		}
		
		return true;
	}
	
	/**
	 * loginToOnxshop
	 */
	 
	public function loginToOnxshop($user_profile) {
		
		require_once('models/client/client_customer.php');
		$Customer = new client_customer();
		$Customer->setCacheable(false);
		
		if ($customer_detail = $Customer->getUserByTwitterId($user_profile->id)) {
		
			//already exists a valid account, we can login
			msg("{$customer_detail['email']} is already registered", 'ok', 1);
			$_SESSION['client']['customer'] = $customer_detail;	
			
		} else {
		
			msg("Twitter ID {$user_profile->id} sucessfully authorised, but must register locally", 'ok', 1);
			
			//forward to registration
			$this->mapUserToOnxshop($user_profile);
			onxshopGoTo("/page/13");//TODO get node_id from conf
		
		}
	}
	
	/**
	 * mapUserToOnxshop
	 */
	 
	public function mapUserToOnxshop($user_profile) {
		
		//map to Onxshop schema
		$onxshop_client_customer = array();
		$name = explode(" ", $user_profile->name);
		
		$onxshop_client_customer['first_name'] = $name[0];
		$onxshop_client_customer['last_name'] = $name[1];
		$onxshop_client_customer['twitter_id'] = $user_profile->id;
		$onxshop_client_customer['profile_image_url'] = $user_profile->profile_image_url;
		
		//save to session
		$_SESSION['r_client']['customer'] = $onxshop_client_customer;
		
	}

}