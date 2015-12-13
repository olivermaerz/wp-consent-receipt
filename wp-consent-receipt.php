<?php
/*
Plugin Name: WP Consent Receipt
Plugin URI: https://olivermaerz.github.io/wp-consent-receipt/
Description: Consent Receipt Plugin for WordPress
Version: 0.9
Author: Oliver Maerz
Author URI: http://www.olivermaerz.com
License: GPL2
*/
/*
Copyright 2015  Oliver Maerz  (email : om-cr@berlinco.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


if(!class_exists('WP_Consent_Receipt')) {
    class WP_Consent_Receipt {
        /**
         * Construct the plugin object
         */
        public function __construct() {
        	// require some 3rd party libraries
        	require_once('php-jwt/src/JWT.php');

            // register actions
            add_action('admin_init', array($this, 'admin_init'));
			add_action('admin_menu', array($this, 'add_menu'));

			// shortcode to embed form in page
			add_shortcode('wp_consent_receipt', array($this, 'display_form'));

			// register filters
			add_filter('wp_consent_receipt_button', array($this, 'create_button'), 9, 2);

        } // END public function __construct
    
        /**
         * Activate the plugin
         */
        public static function activate() {
            // Do nothing
        } // END public static function activate
    
        /**
         * Deactivate the plugin
         */     
        public static function deactivate() {
            // Do nothing
        } // END public static function deactivate

        /**
		 * hook into WP's admin_init action hook
		 */
		public function admin_init() {
		    // Set up the settings for this plugin
		    $this->init_settings();
		} // END public static function activate


		/**
		 * Initialize some custom settings
		 */     
		public function init_settings() {
		    // register the settings for this plugin

			
			
		    register_setting('wp_consent_receipt', 'key_uri');
		    register_setting('wp_consent_receipt', 'api_uri');

		    add_settings_section('wp_consent_receipt_main', 'Main Settings', array(&$this, 'admin_section_text'), 'wp_consent_receipt');
			add_settings_field('api_uri', 'API URI', array(&$this, 'admin_api_uri_field'), 'wp_consent_receipt', 'wp_consent_receipt_main');
			add_settings_field('key_uri', 'Key URI', array(&$this, 'admin_key_uri_field'), 'wp_consent_receipt', 'wp_consent_receipt_main');

			// TODO: implement validation of the settings entered (valid URI?)

		} // END public function init_settings()


		/**
		 * add a menu
		 */     
		public function add_menu() {
		    add_options_page('Consent Receipt Settings', 'WP Consent Receipt', 'manage_options', 'wp_consent_receipt', array(&$this, 'plugin_settings_page'));
		} // END public function add_menu()

		/**
		 * Menu Callback
		 */     
		public function plugin_settings_page() {
		    if(!current_user_can('manage_options'))
		    {
		        wp_die(__('You do not have sufficient permissions to access this page.'));
		    }

		    // Render the settings template
		    include(sprintf("%s/templates/settings.php", dirname(__FILE__)));
		} // END public function plugin_settings_page()


		/**
		 * text for admin menu
		 */   
		public function admin_section_text() {
			echo 'At this point configuration options are limited to the URI for the Consent Receipt API (for example <code>https://mvcr.herokuapp.com/api</code>) and URI for the key (for example <code>https://mvcr.herokuapp.com/api/jwk</code>). For the latest consent receipt specs please see <a href="https://github.com/KantaraInitiative/CISWG/tree/master/ConsetReceipt/specification">https://github.com/KantaraInitiative/CISWG/tree/master/ConsetReceipt/specification</a>.';
		} // END public function admin_section_text()

		/**
		 * display the key_uri field for admin menu
		 */   
		public function admin_key_uri_field() {
			//echo 'crap';
			echo '<input type="text" name="key_uri" id="key_uri" class="regular-text code" value="' . get_option('key_uri') . '">';
		} // END public function  admin_key_uri_field()

		/**
		 * display the api_uri field for admin menu
		 */   
		public function admin_api_uri_field() {
			//echo 'crap';
			echo '<input type="text" name="api_uri" id="api_uri" class="regular-text code" value="' . get_option('api_uri') . '">';
		} // END public function admin_api_uri_field()

		/**
		 * get the key to verify Consent Receipt
		 */  
		private function get_key() {
			$content = file_get_contents(get_option('key_uri'));
			$key = json_decode($content, true);

			//var_dump($key);
			//base64_decode(data)
			return ($key);
		} // END private function get_key()


		/**
		 * Decode the received Consent Receipt JWT 
		 */  
		private function decode_jwt($jwt, $key) {
			//$key = chunk_split( $key , 64 , "\n" );
			//$key = base64_decode($key);
			var_dump($key);

			$decoded = JWT::decode($jwt, $key, array('RS256'));

			var_dump($decoded);

			/*
			 NOTE: This will now be an object instead of an associative array. To get
			 an associative array, you will need to cast it as such:
			*/

			$decodedArray = (array) $decoded;

			/**
			 * You can add a leeway to account for when there is a clock skew times between
			 * the signing and verifying servers. It is recommended that this leeway should
			 * not be bigger than a few minutes.
			 *
			 * Source: http://self-issued.info/docs/draft-ietf-oauth-json-web-token.html#nbfDef
			 */
			//JWT::$leeway = 60; // $leeway in seconds
			//$decoded = JWT::decode($jwt, $key, array('RS256'));

			return $decodedArray;
		} // END private function decode_jwt()
		
		/**
		 * Make the Jason API call to the backend via http
		 */  
	    private function make_jason_http_request($data) {
			// use key 'http' even if you send the request to https://...
			$options = array(
			    'http' => array(
			        'header'  => "Content-type: application/json\r\n",
			        'method'  => 'POST',
			        'content' => json_encode($data),
			    ),
			);

			$context  = stream_context_create($options);
			$result = file_get_contents(get_option('api_uri'), false, $context);

			//var_dump($result);
			$keyArray = $this->get_key();
			
			// extract the key
			
			$modulus = $keyArray['keys'][0]['n'];;
			$exponent = $keyArray['keys'][0]['e'];
			$rsa = new Crypt_RSA();

			$modulus = new Math_BigInteger(JWT::urlsafeB64Decode($modulus), 256);
			$exponent = new Math_BigInteger(JWT::urlsafeB64Decode($exponent), 256);

			$rsa->loadKey(array('n' => $modulus, 'e' => $exponent));
			$rsa->setPublicKey();
			$pubKey = $rsa->getPublicKey();

			$decodedResult = $this->decode_jwt($result, $pubKey);

	    } // END private function make_jason_http_request

	    public function display_form($atts) {
	    	return "This shortcode will eventually display a consent receipt form for testing purposes.";
	    } // END public function display_form()


	    public function create_button($html, $consentReceiptData) {
	    	// create the consent receipt button 
	    	//var_dump($consentReceiptData);

	    	return $html . "button";

	    } // END public function create_button()

    } // END class WP_Consent_Receipt
} // END if(!class_exists('WP_Consent_Receipt'))



if(class_exists('WP_Consent_Receipt')) {
    // Installation and uninstallation hooks
    register_activation_hook(__FILE__, array('WP_Consent_Receipt', 'activate'));
    register_deactivation_hook(__FILE__, array('WP_Consent_Receipt', 'deactivate'));

    // instantiate the plugin class
    $wp_consent_receipt = new WP_Consent_Receipt();
}







