<?php
include_once('flickrocket_function.php');


if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WC_Settings_FlipRocket' ) ) :

/**
 * WC_Settings_FlipRocket
 */
class WC_Settings_FlipRocket extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'flickrocket';
		$this->label = __( 'FlickRocket', 'woocommerce' );

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
	}

	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings() {
		
		$flickObjT = new Flickrocket();
		$flickRocketThemeID = $flickObjT->flickRocketThemeID();
		$frThemeValue = $flickRocketThemeID->Themes;
		
		$themeIDArray = array();
		$themeIDArray[''] = 'Select Theme ID';
		foreach($frThemeValue->stThemes as $themeData){
			$themeIDArray[$themeData->ID] = $themeData->Name ." ( " . $themeData->ID . " )";
		}
		
		return apply_filters( 'woocommerce_' . $this->id . '_settings', array(
			
			array( 'title' => __( '', 'woocommerce' ), 'type' => 'title', 'desc' => __( '<div id="fr_message"></div>' ), 'id' => 'fr_message_test'),

			array( 'title' => __( 'Configuration Settings', 'woocommerce' ), 'type' => 'title', 'desc' => __( 'Leave email and password empty to use the sandbox test environment. Please specify the Flickrocket account details.', 'woocommerce' ), 'id' => 'fr_account_page_options' ),

			array(
				'title' => __( 'User Email', 'woocommerce' ),
				'desc' 		=> __( 'Email of user registered with FlickRocket with Shop Management permission', 'woocommerce' ),
				'id' 		=> 'flickrocket_user_email',
				'type' 		=> 'text',
				'class'		=> 'fr_settings_fields',
				'default'	=> '',
				'desc_tip'	=> true,
			),

			array(
				'title' => __( 'User Password', 'woocommerce' ),
				'desc' 		=> __( 'Flickrocket user password.', 'woocommerce' ),
				'id' 		=> 'flickrocket_user_password',
				'type' 		=> 'password',
				'class'		=> 'fr_settings_fields',
				'default'	=> '',
				'desc_tip'	=> true,
			),

			array(
				'title' => __( 'Theme', 'woocommerce' ),
				'desc' 		=> __( 'Themes are managed in FlickRocket under (Shop -> Themes)', 'woocommerce' ),
				'id' 		=> 'flickrocket_theme_id',
				'type' 		=> 'select',
				'class'		=> 'theme_id',
				'options' 	=> $themeIDArray,
				'default'	=> '',
				'desc_tip'	=> true,
			),
			
			array(
				'title'         => __( '', 'woocommerce' ),
				'desc'          => __( 'Use FlickRocket Sandbox', 'woocommerce' ),
				'id'            => 'sandbox_active',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => 'start',
				'autoload'      => false
			),

			array( 'type' => 'sectionend', 'id' => 'account_registration_options'),
			
			array( 'title' => __( '', 'woocommerce' ), 'type' => 'title', 'desc' => __( '<div style="margin-left:235px;" class="button button-primary" id="check_fr_details">Check</div>', 'woocommerce' ), 'id' => 'fr_account_page_options' ),
			
			array( 'title' => __( '', 'woocommerce' ), 'type' => 'title', 'desc' => __( "If you don't have a FlickRocket account yet, you can sign up to your <a href='http://www.flickrocket.com/' target='_blank'>free account here</a>.", 'woocommerce' ), 'id' => 'fr_account_page_options' ),
			
array( 'title' => __( '', 'woocommerce' ), 'type' => 'title', 'desc' => __( '<div class="button-primary" id="save_fr_details">Update Settings</div>', 'woocommerce' ), 'id' => 'fr_account_page_options' ),

		)); // End pages settings
	}
	
	public function output() {
		global $current_section;

		$settings = $this->get_settings();

		WC_Admin_Settings::output_fields( $settings );
	
	}

	/**
	 * Save settings
	 */
	public function save() {
		global $current_section;
			
		$settings = $this->get_settings();
		WC_Admin_Settings::save_fields( $settings );

	}
}

endif;

return new WC_Settings_FlipRocket();

?>