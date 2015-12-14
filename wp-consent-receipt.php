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
Copyright (c) 2015  Oliver Maerz  (email : om-wpcr@berlinco.com)

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
        	// add phptseclib and php-jwt/src to includepath
        	set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__)) . '/phpseclib' . 
        									PATH_SEPARATOR . realpath(dirname(__FILE__)) . '/php-jwt/src' );
        	// require some 3rd party libraries 
        	// TODO: replace require_once mess with autoloader
        	require_once('JWT.php');
        	require_once('phpseclib/Math/BigInteger.php');
        	require_once('phpseclib/Crypt/RSA/MSBLOB.php');
        	require_once('phpseclib/Crypt/RSA/OpenSSH.php');
        	require_once('phpseclib/Crypt/RSA/PKCS.php');
        	require_once('phpseclib/Crypt/RSA/PKCS1.php');
        	require_once('phpseclib/Crypt/RSA/PKCS8.php');
        	require_once('phpseclib/Crypt/RSA/PuTTY.php');
        	require_once('phpseclib/Crypt/RSA/Raw.php');
        	require_once('phpseclib/Crypt/RSA/XML.php');
        	require_once('phpseclib/Crypt/Hash.php');
        	require_once('phpseclib/Crypt/RSA.php');

        	//print_r(get_declared_classes());

        	//restore_include_path();

            // register actions
            add_action('admin_init', array($this, 'admin_init'));
			add_action('admin_menu', array($this, 'add_menu'));
			add_action( 'wp_enqueue_scripts', array($this, 'add_javascript_css'));

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
		 * enqueue javascript file for consent receipt button
		 */  
		public function add_javascript_css() {
			wp_enqueue_script('wp-consent-receipt', plugin_dir_url( __FILE__ ) . 'js/wp-consent-receipt.js', array(), '0.9.1', true );
			wp_enqueue_style('wp-consent-receipt-style', plugin_dir_url( __FILE__ ) . 'css/wp-consent-receipt.css');
		} // END private function add_javascript()

		/**
		 * get the key to verify Consent Receipt
		 */  
		private function get_key() {
			$content = file_get_contents(get_option('key_uri'));
			$key = json_decode($content, true);

			return ($key);
		} // END private function get_key()


		/**
		 * Decode the received Consent Receipt JWT 
		 */  
		private function decode_jwt($jwt, $key) {
			$decoded = Firebase\JWT\JWT::decode($jwt, $key, array('RS256'));
			$decodedArray = (array) $decoded;

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
			$keyArray = $this->get_key();
			
			// extract the key
			$modulus = $keyArray['keys'][0]['n'];;
			$exponent = $keyArray['keys'][0]['e'];

			$rsa = new phpseclib\Crypt\RSA();

			$modulus = new \phpseclib\Math\BigInteger(Firebase\JWT\JWT::urlsafeB64Decode($modulus), 256);
			$exponent = new \phpseclib\Math\BigInteger(Firebase\JWT\JWT::urlsafeB64Decode($exponent), 256);

			$rsa->load(array('n' => $modulus, 'e' => $exponent));
			$rsa->setPublicKey();
			$pubKey = $rsa->getPublicKey();

			$decodedResult = $this->decode_jwt($result, $pubKey);

			return array($decodedResult,$result);

	    } // END private function mlake_jason_http_request


	    /**
		 * Display a consent receipt form (i.e. for shortcode)
		 */ 
	    public function display_form($atts) {
	    	return "This shortcode will eventually display a consent receipt form for testing purposes.";
	    } // END public function display_form()


	    /**
		 * Create a button and consent receipt table 
		 */ 
	    public function create_button($html, $consentReceiptData) {
	    	// create the consent receipt button 
	    	try {
				list($receipt,$receiptRaw) = $this->make_jason_http_request($consentReceiptData);

				// stick the raw receipt into the session
				$_SESSION['receiptRaw'] = $receiptRaw;


				$receiptHtml = '
					<p>
					<input type="button" name="cr" onclick="showTable(true);" value="Show Consent Receipt" id="toggleButton">
					</p>
					
					<table class="table table-hover ConsentReceipt" id="ConsentReceiptID">
					<tbody>

						<tr>
							<td>Jurisdiction of organization</td>
							<td>' . $receipt['jurisdiction'] . '</td>
						</tr>

						<tr>
							<td>ID of natural person</td>
							<td>' .  $receipt['sub'] . '</td>
						</tr>


						<tr>
							<td>Privacy Policy Link</td>
							<td>' . $receipt['policy_uri'] . '</td>
						</tr>


						<tr>
							<td>Link to Short Privacy Notice</td>
							<td>' . $receipt['notice'] . '</td>
						</tr>

						<tr>
							<td>Data Controller</td>
							<td>Contact name: ' . $receipt['data_controller']->contact;

			if ($receipt['data_controller']->on_behalf){
				$receiptHtml .= " (is not acting on behalf of organization)<br>\n";

			} else {
				$receiptHtml .= " (is acting on behalf of organization)<br>\n";
			}

					
			$receiptHtml .= "Organization name: " . $receipt['data_controller']->company . "<br>\n";
			$receiptHtml .= "Address: " . $receipt['data_controller']->address . "<br>\n";
			$receiptHtml .= "Contact email: " . $receipt['data_controller']->email   . "<br>\n";
			$receiptHtml .= "Phone number: " . $receipt['data_controller']->phone   . "<br>\n";

			$receiptHtml .= '

							</td>
						</tr>

						<tr>
							<td>Consent transaction data</td>
							<td>';

			foreach($receipt['consent_payload'] as $payloadKey => $payloadValue) {
			   $receiptHtml .= "$payloadKey: $payloadValue<br>\n";
			}

			$receiptHtml .= '					
							</td>
						</tr>


						<tr>
							<td>Purpose</td>
							<td>';


			foreach($receipt['purpose'] as $payloadValue) {
    			$receiptHtml .= "$payloadValue<br>\n";
   			}

   			$receiptHtml .= '
							</td>
						</tr>


						<tr>
							<td>PII categories</td>
							<td>';

			foreach($receipt['pii_collected'] as $payloadKey => $payloadValue) {
				$receiptHtml .= "Category: $payloadKey<br>\nValue: $payloadValue<br><br>\n";
			}

			$receiptHtml .= '
							</td>
						</tr>


						<tr>
							<td>Sensitive information</td>
							<td>';


			foreach($receipt['sensitive'] as $payloadValue) {
    			$receiptHtml .= "$payloadValue<br>\n";
   			}

   			$receiptHtml .= '
							</td>
						</tr>

						<tr>
							<td>3rd party sharing of personal information</td>
							<td>';

			foreach($receipt['sharing'] as $payloadValue) {
    			$receiptHtml .= "$payloadValue<br>\n";
   			}

   			$receiptHtml .= '
							</td>
						</tr>

						<tr>
							<td>Consent Context</td>
							<td>';

			foreach($receipt['context'] as $payloadValue) {
				$receiptHtml .= "$payloadValue<br>\n";
			}

			$receiptHtml .= '
							</td>
						</tr>

						<tr>
							<td>Issuer</td>
							<td>' . $receipt['iss'] . '</td>
						</tr>

						<tr>
							<td>Transaction number</td>
							<td>' .  chunk_split($receipt['jti'], 40, "<br />\n") . '</td>
						</tr>

						<tr>
							<td>Time stamp</td>
							<td>' .  gmdate("D M j, Y G:i:s T", $receipt['iat']) . '</td>
						</tr>

					</tbody>
					</table>';

					$html .= $receiptHtml;

					
    
			} catch (Exception $e) {
				echo 'Error: ',  $e->getMessage(), "\n";
			}



		


	    	return $html;

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







