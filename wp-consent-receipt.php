<?php
/*
Plugin Name: WP Consent Receipt
Plugin URI: https://olivermaerz.github.io/wp-consent-receipt/
Description: Consent Receipt Plugin for WordPress
Version: 0.28
Author: Oliver Maerz
Author URI: http://www.olivermaerz.com
License: GPL2
Text Domain: wp-consent-receipt
Domain Path: /languages
*/
/*
Copyright (c) 2015, 2016 Oliver Maerz  (email : om-wpcr@berlinco.com)

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

    	private $receiptHtml; 

        /**
         * Construct the plugin object
         */
        public function __construct() {
        	// add phptseclib and php-jwt/src to includepath
        	set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__)) . '/phpseclib' . 
        									PATH_SEPARATOR . realpath(dirname(__FILE__)) . '/php-jwt/src' );
        	// require some 3rd party libraries 
        	// TODO: replace require_once with autoloader
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

        	$this->receiptHtml = '';

        	//restore_include_path();

        	// workaround for WP bug with mailto
        	//add_filter( 'wp_mail_from', function() {
    		//	return 'donotreply@kantarainitiative.org';
			//} );

            // register actions
            add_action('admin_init', array($this, 'admin_init'));
			add_action('admin_menu', array($this, 'add_menu'));
			add_action( 'wp_enqueue_scripts', array($this, 'add_javascript_css'));

			// shortcode to embed form in page
			add_shortcode('wp_consent_receipt', array($this, 'display_form'));

			// register filters
			add_filter('wp_consent_receipt_button', array($this, 'create_button'), 9, 2);

			// register hooks
			add_action('wp_consent_receipt', array($this, 'get_consent_receipt'));

			// add action for the receipt download
			add_action('template_redirect', array($this, 'cr_downoad_redirect'));

			// activate session 
			add_action('init', array($this, 'register_session'), 1);

			// add hack from Dan Cameron to allow string attachments for wp_mail
			add_action( 'phpmailer_init', array($this, '_add_string_attachments'));

			// add handler to return private key
			add_action('parse_request', array($this, 'my_custom_url_handler'));

			// ad widget code before the end body tage
			add_action( 'wp_footer',  array($this, 'add_cr_widget'));

			// load translations
 			add_action('plugins_loaded', array($this, 'load_translations'));


        } // END public function __construct

        /*
  		 * load translation files
         */
		public  function load_translations() {
			load_plugin_textdomain('wp-consent-receipt', false, 'wp-consent-receipt/languages');
		}
 
    
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


		// start the session
		public function register_session(){
    		if( !session_id() )
        		session_start();
		}

		/**
		 * Initialize some custom settings
		 */     
		public function init_settings() {
		    // register the settings for this plugin

			// locally stored keys
			register_setting('wp_consent_receipt', 'private_key');
		    register_setting('wp_consent_receipt', 'public_key');

		    // key to be retrieved via http
		    register_setting('wp_consent_receipt', 'key_uri');
		    // api uri to call
		    register_setting('wp_consent_receipt', 'api_uri');

		    add_settings_section('wp_consent_receipt_main', 'Main Settings', array(&$this, 'admin_section_text'), 'wp_consent_receipt');

		    add_settings_field('private_key', 'Private Key', array(&$this, 'admin_private_key_field'), 'wp_consent_receipt', 'wp_consent_receipt_main');
			add_settings_field('public_key', 'Public Key', array(&$this, 'admin_public_key_field'), 'wp_consent_receipt', 'wp_consent_receipt_main');
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
		        wp_die(__('You do not have sufficient permissions to access this page.','wp-consent-receipt'));
		    }

		    // Render the settings template
		    include(sprintf("%s/templates/settings.php", dirname(__FILE__)));
		} // END public function plugin_settings_page()


		/**
		 * text for admin menu
		 */   
		public function admin_section_text() {
			//echo 'At this point configuration options are limited to the URI for the Consent Receipt API (for example <code>http://mvcr7.herokuapp.com/api/api</code>) and URI for the key (for example <code>http://mvcr7.herokuapp.com/api/jwk</code>). For the latest consent receipt specs please see <a href="https://github.com/KantaraInitiative/CISWG/tree/master/ConsetReceipt/specification">https://github.com/KantaraInitiative/CISWG/tree/master/ConsetReceipt/specification</a>.';
			echo 'Only private key field and public key field are used at this time. URI fields are ignored.';
		} // END public function admin_section_text()


		/**
		 * display the private_key_ field for admin menu
		 */   
		public function admin_private_key_field() {
			//echo 'crap';
			echo '<textarea cols="80" rows="15" name="private_key" id="key_uri" class="regular-text code">' . get_option('private_key') . '</textarea>';
		} // END public function  admin_private_key_field()


		/**
		 * display the public_key_ field for admin menu
		 */   
		public function admin_public_key_field() {
			//echo 'crap';
			echo '<textarea cols="80" rows="5" name="public_key" id="key_uri" class="regular-text code">' . get_option('public_key') . '</textarea>';
		} // END public function  admin_public_key_field()

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
		 * get the external public key (via http request) to verify Consent Receipt
		 */  
		private function get_key() {
			$content = file_get_contents(get_option('key_uri'));
			$key = json_decode($content, true);

			return ($key);
		} // END private function get_key()

		/**
		 * get the private key stored in the config
		 */  
		private function get_private_key() {
			$key = get_option('private_key');
			return ($key);
		} // END private function get_private_key()

		/**
		 * get the public key stored in the config
		 */  
		private function get_public_key() {
			$key = get_option('public_key');
			return ($key);
		} // END private function get_public_key()

		/**
		 * Decode the received Consent Receipt JWT 
		 */  
		private function decode_jwt($jwt, $key) {
			$decoded = Firebase\JWT\JWT::decode($jwt, $key, array('RS256'));
			$decodedArray = (array) $decoded;

			return $decodedArray;
		} // END private function decode_jwt()


		/**
		 * Encode Consent Receipt  
		 */  
		private function encode_jwt($array, $key) {
			$jwt = Firebase\JWT\JWT::encode($array, $key, 'RS256');

			return $jwt;
		} // END private function encode_jwt()



		/**
		 * Generate Consent Receipt
		 */
		private function generate_receipt($consentReceiptData) {

			$privateKey = $this->get_private_key();

			$encoded = $this->encode_jwt($consentReceiptData, $privateKey);

			return array($consentReceiptData,$encoded);

		}

		/** 
		 * Generate UUID
		 */
		private function gen_uuid() {
		    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
		        // 32 bits for "time_low"
		        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

		        // 16 bits for "time_mid"
		        mt_rand( 0, 0xffff ),

		        // 16 bits for "time_hi_and_version",
		        // four most significant bits holds version number 4
		        mt_rand( 0, 0x0fff ) | 0x4000,

		        // 16 bits, 8 bits for "clk_seq_hi_res",
		        // 8 bits for "clk_seq_low",
		        // two most significant bits holds zero and one for variant DCE1.1
		        mt_rand( 0, 0x3fff ) | 0x8000,

		        // 48 bits for "node"
		        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
		    );
		}

		
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
	     *  download consent receipt 
	     */
	    public function cr_downoad_redirect() {
			if ($_SERVER['REQUEST_URI'] === '/downloads/KantaraInitiativeConsentReceipt.jwt') {
				if( !session_id())
        			session_start();

				header("Content-type: application/jwt",true,200);
				header("Content-Disposition: attachment; filename=KantaraInitiativeConsentReceipt.jwt");
				header("Pragma: no-cache");
				header("Expires: 0");

				

				echo $_SESSION['receiptRaw'];
				exit();
		 	}
		}

	    /**
		 * Create a button and consent receipt table 
		 */ 
	    public function create_button($html, $consentReceiptData) {
	    	// create the consent receipt button 
	    	try {

	    		$no_email = $consentReceiptData['noEmail'];

	    		// get rid of  array elements we do not want to have in the receipt (configuration elements etc.)
	    		unset($consentReceiptData['noEmail']);

	    		// generate the UUID and update the jti field:
	    		$consentReceiptData['jti'] = $this->gen_uuid();
    

	    		// make request via external API 
				//list($receipt,$receiptRaw) = $this->make_jason_http_request($consentReceiptData);

	    		// generate Receipt
	    		list($receipt,$receiptRaw) = $this->generate_receipt($consentReceiptData);

				// stick the raw receipt into the session
				$_SESSION['receiptRaw'] = $receiptRaw;


				
				

				//Display Download Receipt button
				$html .= '
				<!-- Trigger the modal with a button -->
				<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#myModal">' . __('Show Consent Receipt', 'wp-consent-receipt') . '</button>

				<a href="/downloads/KantaraInitiativeConsentReceipt.jwt" download="KantaraInitiativeConsentReceipt.jwt"><button class="btn btn-primary" type="button" name="crdl" id="download_cr">' . __('Download Consent Receipt', 'wp-consent-receipt') . '</button></a>';

	   			//Iterate through the array 
	   			$this->receiptHtml = $this->display_receipt_data($receipt,0);


	   			// send email to user with receipt

				// set cr so we can check later and add the cr string in the hack below _add_string_attachments
				$attachments = array();
				$cr_attachment = array(
					'cr' => 1,
				);
				$attachments[] = wp_json_encode( $cr_attachment );

				//$email_message = $this->receiptHtml;
				$email_message = __('Attached your Consent Receipt for') . ' ' . $receipt['dataController']['company'];
				

				$headers = array(
					'Content-Type: text/html; charset=UTF-8',
					"From: {$consentReceiptData['dataController']['company']} <{$consentReceiptData['dataController']['email']}>"
					);
				//$headers .= 'Content-type: text/html' . "\r\n";

				if (!$no_email) {
					// Does want to receive the CR by email
					wp_mail( $receipt['sub'] , 'Your Consent Receipt', $email_message, $headers, $attachments );

				}

				

			} catch (Exception $e) {
				echo 'Error: ',  $e->getMessage(), "\n";
			}


	    	return $html;

	    } // END public function create_button()


	    private function display_receipt_data($receipt_array, $indent){

	    	foreach($receipt_array as $key => $value) {

	    		if (is_string($key)) {
	    			if ($indent) {
		    			$displayHtml .= '<span class="label label-default">' . __($key,'wp-consent-receipt') . '</span>';	
	    			} else {
		    			$displayHtml .= '<span class="label label-info">' . __($key,'wp-consent-receipt') . '</span>';	
		    		}
	    		}

			    if (!is_array($value))
			    {
				    $displayHtml .= '<div class="well well-small word-wrap">';
				    
			        if (is_bool($value)) {
			        	// value is of type boolean replace with Yes and No
			        	if ($value) {
			        		$displayHtml .= 'Yes';
			        	} else {
			        		$displayHtml .= 'No';
			        	}
			        } elseif ($key === 'iat') {
			        	// value is time stamp convert to readable date/time
			        	$displayHtml .=  date('m/d/Y H:i:s', ($value + 0) );
			        } elseif (filter_var($value, FILTER_VALIDATE_URL)) { 
			        	// value is a url ... add a href etc.
  						$displayHtml .=  '<a href="' . esc_attr($value) . '" target="_blank">' . esc_attr($value) . '</a>'; 
			        } else {
			        	$displayHtml .=  esc_attr($value);
			        }
			        
				    $displayHtml .= '</div>'; 

			    } else {
			    	if (is_string($key)) {
			      		// assoc array
				       	$displayHtml .= '<div class="alert alert-info">';
				    	$displayHtml .= $this->display_receipt_data($value,$indent+1);
				    	$displayHtml .= '</div>';
			    	} else {
			    		$displayHtml .= $this->display_receipt_data($value,$indent+1);
			    		$displayHtml .= '<hr>';
			    	}
			    }  
			}
			return $displayHtml;
	    }

	    // add hack from Dan Cameron to allow string attachments for wp_mail
		public function _add_string_attachments( $phpmailer ) {
			// the previous attachment will fail since it's not an actual file
			// we will use the error to attach it as a string
			if ( '' !== $phpmailer->ErrorInfo ) {
				
				// error info
				$error_info = $phpmailer->ErrorInfo;
				$translations = $phpmailer->getTranslations();
				
				// See if there was an error attaching something to the email
				if ( false !== stripos( $error_info, $translations['file_access'] ) ) {
					// remove the messaging
					$attachment_string = str_replace( $translations['file_access'], '', $error_info );
						
					// the result will be the json encoded string that was attempted to be attached by default
					$attachment = json_decode( $attachment_string );
					
					// check if we wanted to send a consent receipt 
					if ( isset( $attachment->cr ) ) {
						
						// create the string attachment
						$file_name = 'KantaraInitiativeConsentReceipt.jwt';
						$cr_attachment_string = $_SESSION['receiptRaw'];
						try {
							$phpmailer->AddStringAttachment( $cr_attachment_string, $file_name, 'base64', 'application/jwt' );	
						} catch ( phpmailerException $e ) {
							
						
						}
					}
				}
			}
		}

		// handler to return public key with custom url ...
		public function my_custom_url_handler() {
			if( isset($_GET['cr_public_key']) ) {
		    	echo get_option('public_key');
    			exit();
			}
		} // END function my_custom_url_handler


		function add_cr_widget() {
		    echo '<!-- Modal -->
		<div class="modal hide fade" id="myModal" role="dialog">
		<div class="modal-dialog modal-lg">
		  <div class="modal-content">
		    <div class="modal-header">
		      <button type="button" class="close" data-dismiss="modal">&times;</button>
		      <h4 class="modal-title">Consent Receipt</h4>
		    </div>
		    <div class="modal-body">';
		    echo $this->receiptHtml;
		    echo '
		    </div>
		    <div class="modal-footer">
		      <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
		    </div>
		  </div>
		</div>
		</div>';
		}


    } // END class WP_Consent_Receipt

} // END if(!class_exists('WP_Consent_Receipt'))






if(class_exists('WP_Consent_Receipt')) {
    // Installation and uninstallation hooks
    register_activation_hook(__FILE__, array('WP_Consent_Receipt', 'activate'));
    register_deactivation_hook(__FILE__, array('WP_Consent_Receipt', 'deactivate'));

    // instantiate the plugin class
    $wp_consent_receipt = new WP_Consent_Receipt();
}
