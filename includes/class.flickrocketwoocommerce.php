<?php
	if(!session_id()){ ob_start(); session_start();}
	
	class FlickRocketWooocommerce{
	
		public static $flickObj;
		
		static $flickrocket_password_key	= 'flickrocket_password';
		
		static $flickrocket_email_key		= 'flickrocket_email';
	
		public static function init() {
			// Hooks
						
			add_action( 'add_meta_boxes', array( get_class(), 'flickRocketProjectIDField' ), 1, 1 );
			
			add_action( 'save_post', array( get_class(), 'myplugin_save_postdata' ) );
			
			add_action( 'woocommerce_product_options_general_product_data', array( get_class(), 'woo_add_custom_general_fields' ) );
			
			add_action( 'woocommerce_process_product_meta', array( get_class(), 'woo_add_custom_general_fields_save' ) );
			
			add_filter( 'woocommerce_my_account_my_orders_actions', array( get_class(), 'get_user_order_list_digital_button' ) );
			
			add_action( 'woocommerce_product_after_variable_attributes', array( get_class(), 'display_license_variations_field' ), 10, 2 );

			add_action( 'save_post', array( get_class(), 'variable_fields_process' ), 12, 1 );

			add_action( 'woocommerce_after_checkout_billing_form', array( get_class(), 'user_login_form' ) );
			
			add_action( 'user_register', array( get_class(), 'flickrocket_registration_save' ) );
			
			if ( is_user_logged_in() ) {			
				add_action( 'profile_update', array( get_class(), 'update_wordpress_and_flickrocket_password' ), 15);
			}	
						
			add_action( 'woocommerce_order_details_after_order_table', array( get_class(), 'flickRocketPaymentComplete' ) );
			
			add_filter( 'gettext', array( get_class(), 'custom_user_message' ), 10, 2 );
			
			$config			=	self::get_flickrocket_config_data();
			
			self::$flickObj	=	new Flickrocket($config['flickrocket_use_sandbox']); 
			
			$_SESSION['flickRocketPrepareLoginEx']	=  1;
		}
		
		//update email address and password on wordpress and flickrocket section.
		public static function update_wordpress_and_flickrocket_password( $user_id ) {
		
			global $wpdb;
			
			$userPsw 	= $_REQUEST['pass1'];
						
			$emailID    = $_REQUEST['email'];
			
			$config = self::get_flickrocket_config_data();		
		
			$flickRAdminEmail	= $config['flickrocket_user_email']; 
		
			$flickRAdminPass	= $config['flickrocket_user_password'];
		
			$flickRThemeID		= $config['flickrocket_theme_id'];
				
			$oldPassword 		= get_user_meta( $user_id, self::$flickrocket_password_key, true );
		
			$oldEmailID 		= get_user_meta($user_id, self::$flickrocket_email_key, true);
			
			if($emailID != '' && $oldEmailID != $emailID){
				
				$changeEmail = self::$flickObj->flickRocketChangeEmail( $flickRAdminEmail, $flickRAdminPass, $flickRThemeID, $oldEmailID, $oldPassword, $emailID );
				
				if($changeEmail->ErrorCode == 0){
				
					update_user_meta($user_id, self::$flickrocket_email_key, $emailID);
					
				}else{
					
					$fr_email = get_user_meta($user_id, self::$flickrocket_email_key, true);
				
					if($fr_email != ''){
						
						$updateData = "UPDATE " . $wpdb->base_prefix . "users SET user_email='$fr_email' WHERE ID='$user_id'";
						
						$wpdb->query($updateData);
					}
				}
			}
			
			if($userPsw != ''){ 
			
				$userEmailID = get_user_meta($user_id, self::$flickrocket_email_key, true);
				
				$changePsw = self::$flickObj->flickRocketChangePassword( $flickRAdminEmail, $flickRAdminPass, $flickRThemeID, $userEmailID, $oldPassword, $userPsw );
				
				
				if($changePsw->ErrorCode == 0){
				
					update_user_meta( $user_id, self::$flickrocket_password_key, $userPsw );
					
				}else{
				
					wp_set_password( $oldPassword, $user_id );
				}
				
			}
			
			if($changeEmail->ErrorCode == 0 || $changePsw->ErrorCode == 0){
					
				header('location:'.get_permalink(woocommerce_get_page_id('myaccount')));
			
				exit;
			}
		}
		
		
		public static function custom_user_message($translation, $text){
			
			if('Profile updated.' == $text){
			
				$current_user = wp_get_current_user();
			
				$foo_condition = '';
			
				if(!$foo_condition)
					return "The email can't be changed because it is already in use.";
			}
			return $translation;
		}
			
		
		//check user exist in flickrockt
		public static function check_user_exist_flickrocket($flickRUserEmail, $flickRUserPSW){
		
			global $wpdb, $woocommerce;
						
			$config = self::get_flickrocket_config_data();		
		
			$flickRAdminEmail	= $config['flickrocket_user_email']; 
			
			$flickRAdminPass	= $config['flickrocket_user_password'];
			
			$flickRThemeID		= $config['flickrocket_theme_id'];
		
			$result = self::$flickObj->flickRocketCheckUserExists( $flickRAdminEmail, $flickRAdminPass, $flickRUserEmail, $flickRUserPSW );
			
			return $result;
						
		}
		
		
		//*************************** Add project id field area box*************************
		
		
		public static function flickRocketProjectIDField() {
		
			$screens = array( 'product' );
		
			foreach ( $screens as $screen ) {
		
				add_meta_box(
					
					'flickrocket_projectid', __( 'Digital Content Delivery (incl. DRM) - FlickRocket', 'flickrocket_textdomain' ), array( get_class(), 'flickrocket_projectid_custom_box' ), $screen
				);
			}
		}
		
		public static function user_login_form(){
		
			echo '<script>
				 jQuery(window).load(function(){
						jQuery("#createaccount").attr("checked",true);
						jQuery(".create-account").show();
						jQuery("#createaccount").parent(".form-row-wide").hide();
					});
				</script>';
		}
		
		//Dispaly project id box
		public static function flickrocket_projectid_custom_box( $post ) {
		
			wp_nonce_field( 'flickrocket_projectid_custom_box', 'flickrocket_projectid_custom_box_nonce' );
			
			$value = get_post_meta( $post->ID, '_flickrocket_project_key_id', true );
			
			$config = self::get_flickrocket_config_data();
			
			$flickRAdminSandbox = $config['flickrocket_use_sandbox']; 
			$flickRAdminEmail	= $config['flickrocket_user_email']; 
			$flickRAdminPass	= $config['flickrocket_user_password'];
			$flickRThemeID		= $config['flickrocket_theme_id']; 
			
			if($flickRAdminSandbox == 'yes'){ $domainValue = 'sandbox'; }
			
			else{	$domainValue = 'www'; }
			
			$projectWizardURL = "http://" . $domainValue . ".flickrocket.com/FlickRocketContentTools/?name=".$flickRAdminEmail."&password=".$flickRAdminPass."&theme=".$flickRThemeID."&type=1";
			
			$getProjectSB = self::$flickObj->flickRocketGetProjects('flickrocket_project_id', esc_attr( $value ));
			
			echo '<label for="flickrocket_project_id">';
			
			_e( "FlickRocket Project ID:", 'flickrocket_textdomain' );
			
			echo '</label> ';
			
			echo $getProjectSB;
			
			echo '<div style="margin-left:130px;"><div style="margin:10px 0;">Unique product identifier within the FlickRocket system</div>';
			
			echo '<div><input type="button" class="button button-primary" name="projectWizard" value="Project Wizard" onclick=\'window.open("'.$projectWizardURL.'", "FlickRocket", "left=10,top=10,width=700,height=518,toolbar=0,resizable=1");\'></div>';
			
			echo '<div style="margin:10px 0;">Alternatively you can use the FlickRocket Content tools for desktop ( <a href="http://www.flickrocket.com/app/download_components.aspx?component=FlickRocket Content Tools (Win)">Windows</a> / <a href="http://www.flickrocket.com/app/download_components.aspx?component=FlickRocket Content Tools (Mac)">MacOSX</a>)</div></div>';
		
		}
		
		public static function myplugin_save_postdata( $post_id ) {
			
			if ( ! isset( $_POST['flickrocket_projectid_custom_box_nonce'] ) )
				
				return $post_id;
			
			$nonce = $_POST['flickrocket_projectid_custom_box_nonce'];
			
			if ( ! wp_verify_nonce( $nonce, 'flickrocket_projectid_custom_box' ) )
				
				return $post_id;

			
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
				
				return $post_id;
			
			// Check the user's permissions.
			
			if ( 'page' == $_POST['post_type'] ) {
			
				if ( ! current_user_can( 'edit_page', $post_id ) )
					
					return $post_id;
			
			} else {
			
				if ( ! current_user_can( 'edit_post', $post_id ) )
					
					return $post_id;
			}
			
			$mydata = sanitize_text_field( $_POST['flickrocket_project_id'] );
			
			update_post_meta( $post_id, '_flickrocket_project_key_id', $mydata );
		}
		
		
		//*************************** Add License field *************************
				
		
		public static function woo_add_custom_general_fields() {
	 
			global $woocommerce, $post;
			
			$getLicenseSB = self::$flickObj->flickRocketGetLicenses();
			
			echo '<div class="options_group" id="flickrocket_license_area" style="margin:13px; border-top:none;">';
			
			echo '<label for="_product_license_id" class="licencebox">';
			
				 _e( 'License', 'woocommerce' );
			
			echo '</label>';
			
			echo '<select id="_product_license_id" name="_product_license_id">';
			
			echo '<option value=""> - - Select License - - </option>';
			
			$licenseIDValue = get_post_meta( $post->ID, '_product_license_id', true );
			
			foreach($getLicenseSB->Licenses->stLicense as $sbValue){
			
				$selectedField = $sbValue->ID == $licenseIDValue ? 'selected' : '';
			
				echo '<option value="' . $sbValue->ID . '" ' . $selectedField . '>' . $sbValue->Name . '</option>';
			}
			
			echo '</select><br /><br />';
			
			echo '<div style="margin:0 0 10px 150px;">License as defined in the FlickRocket backend</div></div>';
			
		}
		
		public static function woo_add_custom_general_fields_save( $post_id ){
		
			$woocommerce_text_field = $_POST['_product_license_id'];
			
			$filckrocketPT = $_POST['_flickrocket'] == 'on' ? 'yes' : 'no';
			
			update_post_meta( $post_id, '_product_license_id', esc_attr( $woocommerce_text_field ) );
			
			update_post_meta( $post_id, '_flickrocket', esc_attr( $filckrocketPT ) );
		}
			
		// Get flickrocket config data from db	
		public static function get_flickrocket_config_data() {
			
			global $wpdb, $woocommerce;
			
			$fr_email 		= get_option( 'flickrocket_user_email', false );
			$fr_password 	= get_option( 'flickrocket_user_password', false );
			$fr_themeid 	= get_option( 'flickrocket_theme_id', false );
			$fr_type 		= get_option( 'sandbox_active', false );
				
			$array['flickrocket_user_email'] 	= $fr_email 	== '' ? 'sandbox@flickrocket.com' : $fr_email;
			$array['flickrocket_user_password']	= $fr_password 	== '' ? 'sandbox1971' : $fr_password;
			$array['flickrocket_theme_id'] 		= $fr_themeid 	== '' ? '829' : $fr_themeid;
			$array['flickrocket_use_sandbox'] 	= $fr_type;
			
			return $array;
		}		
		 
		// Display license variations field as a selectbox.
		public static function display_license_variations_field( $loop, $variation_data ) {
			?>
            <tr>
                <td colspan="2">
                    <div id="flickRocketVMB_<?php echo $loop; ?>" class="flickRocketVMB">
                        <label><?php _e( 'License', 'woocommerce' ); ?></label>
                        <?php 
                        $getLicenseSB = self::$flickObj->flickRocketGetLicenses();
			
						echo '<select id="variations_license_id" name="variations_license_id[' . $loop . ']">';
						
						echo '<option value=""> - - Select License - - </option>';
						if(count($getLicenseSB->Licenses->stLicense) > 0){
							foreach($getLicenseSB->Licenses->stLicense as $sbValue){
							
								$selectedField = $sbValue->ID == $variation_data['_variations_license_id'][0] ? 'selected' : '';
							
								echo '<option value="' . $sbValue->ID . '" ' . $selectedField . '>' . $sbValue->Name . '</option>';
							}
						}else{
							$sbValue = $getLicenseSB->Licenses->stLicense;
							$selectedField = $sbValue->ID == $variation_data['_variations_license_id'][0] ? 'selected' : '';
							echo '<option value="' . $sbValue->ID . '" ' . $selectedField . '>' . $sbValue->Name . '</option>';
						}
						
						echo '</select>';
						?>
                    </div>
                </td>
            </tr>
			<?php
		}
		 
		// show license field on variations section 
		public static function display_license_variations_fields_js() {
			?>
            <tr>
                <td>
                    <div>
                        <label><?php _e( 'License ID', 'woocommerce' ); ?></label>
                        <input type="text" size="5" name="variations_license_id[' + loop + ']" />
                    </div>
                </td>
            </tr>
			<?php
		}
		 
		public static function variable_fields_process( $post_id ) {
			if (isset( $_POST['variable_sku'] ) ) :
			$variable_sku 			= $_POST['variable_sku'];
			$variable_post_id 		= $_POST['variable_post_id'];
			$variable_custom_field 	= $_POST['variations_license_id'];
			$filckrocketVPT 		= $_POST['variable_is_flickrocket'];
			
			for ( $i = 0; $i < sizeof( $variable_sku ); $i++ ) :
				$variation_id = (int) $variable_post_id[$i];
				$flickRocketVMBData =  $filckrocketVPT[$i] == 'on' ? 'yes' : 'no';
				if ( isset( $variable_custom_field[$i] ) ) {
					update_post_meta( $variation_id, '_variations_license_id', stripslashes( $variable_custom_field[$i] ) );
					update_post_meta( $variation_id, '_vri_flickrocket', stripslashes( $flickRocketVMBData ) );
				}
			endfor;
			endif;
		}
		
		public static function flickrocket_registration_save( $user_id ) {
			if($_POST['billing_email'] != ''){ 
				update_user_meta($user_id, self::$flickrocket_email_key, $_POST['billing_email']);
			}	
			update_user_meta($user_id, self::$flickrocket_password_key, $_POST['account_password']);
		}
		
		//change password on flickrocket api
		public static function flickrocket_after_login($user_login) {
		
			global $wpdb;
			
			$password = @$_POST['password'] == '' ? $_POST['pwd'] : $_POST['password'];
			
			if($password != ''){	
				$config = self::get_flickrocket_config_data();		
			
				$flickRAdminEmail	= $config['flickrocket_user_email']; 
				$flickRAdminPass	= $config['flickrocket_user_password'];
				$flickRThemeID		= $config['flickrocket_theme_id'];
				$userEmailID		= $wpdb->get_var("SELECT user_email FROM ".$wpdb->base_prefix."users WHERE ID=$user_id");

				$oldPassword = get_user_meta($user_id, self::$flickrocket_password_key, true);
				self::$flickObj->flickRocketChangePassword( $flickRAdminEmail, $flickRAdminPass, $flickRThemeID, $userEmailID, $oldPassword, $password );
				
				$user_id = $wpdb->get_var("SELECT ID FROM ".$wpdb->base_prefix."users WHERE user_login='$user_login'");
				update_user_meta($user_id, self::$flickrocket_password_key, $password);
			}
		}		
		
		//get flickrocket digital button			
		public static function get_user_order_list_digital_button($orderListData) {
			
			global $wpdb, $woocommerce;
			
			$orderURL 		= $orderListData['view']['url'];
			$orderID		= end(explode('&order=', $orderURL));
			$orderDetils	= new WC_Order( $orderID );
			
			$items			= $orderDetils->get_items();
			
			$order_item_id	= $wpdb->get_var("SELECT order_item_id FROM ".$wpdb->base_prefix."woocommerce_order_items WHERE order_id=$orderID");
			
			$productID		= $items[$order_item_id]['product_id'];
			$quantity		= $items[$order_item_id]['qty'];
			$variationPID	= $items[$order_item_id]['variation_id'];
	
			$orderStatus	= $orderDetils->status;
			$customer_userID= $orderDetils->customer_user;
			$userEmailID	= $wpdb->get_var("SELECT user_email FROM ".$wpdb->base_prefix."users WHERE ID=$customer_userID");
			$transactionID	= 'REGAN'.$orderID;
			$flickRUserPSW	= $wpdb->get_var("SELECT meta_value FROM ".$wpdb->base_prefix."usermeta WHERE user_id='$customer_userID' AND meta_key='flickrocket_password'");
			
			if($orderStatus == 'Completed' || $orderStatus == 'completed'){
			
				$config = self::get_flickrocket_config_data();		
			
				$flickRAdminEmail	= $config['flickrocket_user_email']; 
				$flickRAdminPass	= $config['flickrocket_user_password'];
				$flickRThemeID		= $config['flickrocket_theme_id'];
				$flickRUserEmail	= $userEmailID;
				$flickRProjectID	= $wpdb->get_var("SELECT meta_value FROM ".$wpdb->base_prefix."postmeta WHERE post_id='$productID' AND meta_key='_flickrocket_project_key_id'");
				$flickRLicenseID	= $wpdb->get_var("SELECT meta_value FROM ".$wpdb->base_prefix."postmeta WHERE post_id='$productID' AND meta_key='_product_license_id'");
				$flickRVLicenseID	= $wpdb->get_var("SELECT meta_value FROM ".$wpdb->base_prefix."postmeta WHERE post_id='$variationPID' AND meta_key='_variations_license_id'");
								
				$flickRLicenseID	= $flickRVLicenseID == '' ? $flickRLicenseID : $flickRVLicenseID;
				
				$flickproduct 		= '';
				
				if ($flickRProjectID != '' && $flickRLicenseID != '')
				{
					$flickproduct 	= 1;
					$createorder	= '';
					
					$createorder	= self::$flickObj->flickRocketCheckUserExists( $flickRAdminEmail, $flickRAdminPass, $flickRUserEmail, $flickRUserPSW );
				}
				
				if($flickproduct)
				{
					$custonerData 	= ''; 
										
					if ($createorder->ErrorCode == 0)
					{	
						if( $_SESSION['flickRocketPrepareLoginEx'] <= 1 ){
							$_SESSION['digital_button_link'] = '';
							$custonerData 	= self::$flickObj->flickRocketPrepareLoginEx( $flickRAdminEmail, $flickRAdminPass, $flickRThemeID, $flickRUserEmail, $flickRUserPSW );
							
							$pos = strpos($custonerData->sURL, 'http');
							if($pos === false){
								$httpVal = 'http://';
							}else{
								$httpVal = '';
							}
							
							if ($custonerData->ErrorCode == 0 && $custonerData->sURL != '')
								echo '<a class="button button-primary" target="_blank" href="'.$httpVal.$custonerData->sURL.'">Digital Content</a>';
								$_SESSION['digital_button_link'] = $custonerData->sURL;
								
							if ($custonerData->ErrorCode < 0)
							{
									if ($custonerData->ErrorCode == -1)
										$error2 = 'Error: -1(FlickRocket User not found)';
									if ($custonerData->ErrorCode == -2)
										$error2 = 'Error: -2(Malformed XML)';
									if ($custonerData->ErrorCode == -3)
										$error2 = 'Error: -3(Customer root in XML not found)';
									if ($custonerData->ErrorCode == -4)
										$error2 = 'Customer user not found';
								echo '<div style="margin-bottom:20px;background: no-repeat scroll 10px 9px #FF0000;color:white;padding: 15px 27px 10px 43px;font-size:12px;">'
										.$error2.'</div>';
							}
						}else{
							echo '<a class="button button-primary" target="_blank" href="http://' . $_SESSION['digital_button_link'] . '">Digital Content</a>';
						}
						
						$_SESSION['flickRocketPrepareLoginEx']++;
					}
					else
					{
						if ($createorder->ErrorCode == '-1')
							$error = 'Error: -1(FlickRocket User not found)';
						if ($createorder->ErrorCode == '-2')
							$error = 'Error: -2(Malformed XML)';
						if ($createorder->ErrorCode == '-3')
							$error = 'Error: -3(Order Root in XML not found)';
						if ($createorder->ErrorCode == '-4'){
							$error = 'Error: -4(Password or PasswordHash Node not found)';
						}
						if ($createorder->ErrorCode == '-5'){
							$error = '';
							$result = self::$flickObj->flickRocketCheckUserExists( $flickRAdminEmail, $flickRAdminPass, $flickRUserEmail, $flickRUserPSW );
							$companyNameA = $result->Companies->string;
							$totalCompany = count($companyNameA);
							if($totalCompany > 1){
								foreach($companyNameA as $companyName){
									$companyNameS .= $companyNameA . $companyNameA == '' ? '' : ', ';
								}
							}else{
								$companyNameS = $result->Companies->string;
							}

							echo '<p id="open_pupupbox"><a href = "javascript:void(0)" onclick = "document.getElementById(\'light\').style.display=\'block\';document.getElementById(\'fade\').style.display=\'block\'">&nbsp;</a></p><div id="light" class="white_content"><div>The password you have specified does not match the records of the digital delivery backend for your email. To access all your content one account, you need to use the same password as you have with the following services:</div><div style="margin-top:5px;">&raquo; ' . $companyNameS . '</div><div style="margin-top:20px;"><a href="'. home_url() .'/wp-admin/profile.php">Change Password</a></div><a href = "javascript:void(0)" onclick = "document.getElementById(\'light\').style.display=\'none\';document.getElementById(\'fade\').style.display=\'none\'"><div class="close_btn"></div></a></div><div id="fade" class="black_overlay"></div>';
						}
						echo '<div style="margin-bottom:20px;background: no-repeat scroll 10px 9px #FF0000;color:white;padding: 15px 27px 10px 43px;font-size:12px;" class="flick_error">'.$error.'</div>';
					}
				}
			}
			return $orderListData;
		}
		
		
		//show payment status complete when use coupan code		
		public static function flickRocketPaymentComplete( $value ){
		
			global $wpdb, $woocommerce;
			
			$orderID		= $value->id;
			$orderDetils	= new WC_Order( $orderID );
			
			$order_item_id	= $wpdb->get_var("SELECT order_item_id FROM ".$wpdb->base_prefix."woocommerce_order_items WHERE order_id=$orderID");
			
			$items			= $orderDetils->get_items();
			$xmlString		= '';
			foreach($items as $itemsDetails){
				
				$productID		= $itemsDetails['product_id'];
				$quantity		= $itemsDetails['qty'];
				$variationPID	= $itemsDetails['variation_id'];
				
				$flickRProjectID	= $wpdb->get_var("SELECT meta_value FROM ".$wpdb->base_prefix."postmeta WHERE post_id='$productID' AND meta_key='_flickrocket_project_key_id'");
				$flickRLicenseID	= $wpdb->get_var("SELECT meta_value FROM ".$wpdb->base_prefix."postmeta WHERE post_id='$productID' AND meta_key='_product_license_id'");
				$flickRVLicenseID	= $wpdb->get_var("SELECT meta_value FROM ".$wpdb->base_prefix."postmeta WHERE post_id='$variationPID' AND meta_key='_variations_license_id'");
								
				$flickRLicenseID	= $flickRVLicenseID == '' ? $flickRLicenseID : $flickRVLicenseID;
				
				$xmlString	.='<Item>
								<ProjectID>'.$flickRProjectID.'</ProjectID>
								<LicenseID>'.$flickRLicenseID.'</LicenseID>
								<Count>'.$quantity.'</Count>
							</Item>';
			}
	
			$orderStatus	= $orderDetils->status;
			$customer_userID= $orderDetils->customer_user;
			$userEmailID	= $wpdb->get_var("SELECT user_email FROM ".$wpdb->base_prefix."users WHERE ID=$customer_userID");
			$transactionID	= 'REGAN'.$orderID;
			$flickRUserPSW	= $wpdb->get_var("SELECT meta_value FROM ".$wpdb->base_prefix."usermeta WHERE user_id='$customer_userID' AND meta_key='flickrocket_password'");			
			
			$config = self::get_flickrocket_config_data();		
		
			$flickRAdminEmail	= $config['flickrocket_user_email']; 
			$flickRAdminPass	= $config['flickrocket_user_password'];
			$flickRThemeID		= $config['flickrocket_theme_id'];
			$flickRUserEmail	= $userEmailID;
			
			$flickproduct 		= '';
			
			if ($flickRProjectID != '' && $flickRLicenseID != '')
			{
				$flickproduct 	= 1;
				
				$createorder	= '';
								
				$digital_button_link = get_post_meta($orderID, 'digital_button_link', true);
				
				if($digital_button_link != 1 || $digital_button_link != '1'){
								
					$createorder	= self::$flickObj->flickRocketCreateShopOrder( $flickRAdminEmail, $flickRAdminPass, $flickRThemeID, $flickRUserEmail, $flickRUserPSW, $transactionID, $flickRProjectID, $flickRLicenseID, $quantity, $xmlString );
				
				}else{
				
					$createorder	= self::$flickObj->flickRocketCheckUserExists( $flickRAdminEmail, $flickRAdminPass, $flickRUserEmail, $flickRUserPSW );
				
				}
				update_post_meta($orderID, 'digital_button_link', 1);
			}
			
			if($orderStatus == 'completed' || $orderStatus == 'pending'){
				
				if($flickproduct)
				{
					$custonerData 	= ''; 
										
					if ($createorder->ErrorCode == 0)
					{
						$custonerData 	= self::$flickObj->flickRocketPrepareLoginEx( $flickRAdminEmail, $flickRAdminPass, $flickRThemeID, $flickRUserEmail, $flickRUserPSW );
						
						$pos = strpos($custonerData->sURL, 'http');
						if($pos === false){
							$httpVal = 'http://';
						}else{
							$httpVal = '';
						}
												
						if ($custonerData->ErrorCode == 0 && $custonerData->sURL != '')
							echo '<div style="clear:both; padding-bottom:15px;"><div style="float:left; width:60%;">To access the digital content you have ordered:</div><div style="float:left;"> <a class="button button-primary" target="_blank" href="'.$httpVal.$custonerData->sURL.'">Digital Content</a></div></div><div style="clear:both;">&nbsp;</div>';
							
						if ($custonerData->ErrorCode < 0){
							if ($custonerData->ErrorCode == -1)
								$error2 = 'Error: -1(FlickRocket User not found)';
							if ($custonerData->ErrorCode == -2)
								$error2 = 'Error: -2(Malformed XML)';
							if ($custonerData->ErrorCode == -3)
								$error2 = 'Error: -3(Customer root in XML not found)';
							if ($custonerData->ErrorCode == -4)
								$error2 = 'Customer user not found';
							echo '<div style="margin-bottom:20px;background: no-repeat scroll 10px 9px #FF0000;color:white;padding: 15px 27px 10px 43px;font-size:12px;">'.$error2.'</div>';
						}
					}
					else{
						if ($createorder->ErrorCode == '-1')
							$error = 'Error: -1(FlickRocket User not found)';
						if ($createorder->ErrorCode == '-2')
							$error = 'Error: -2(Malformed XML)';
						if ($createorder->ErrorCode == '-3')
							$error = 'Error: -3(Order Root in XML not found)';
						if ($createorder->ErrorCode == '-4'){
							//$error = 'Error: -4(User With this E-Mail already exists)';
							$error = '';
							$result = self::$flickObj->flickRocketCheckUserExists( $flickRAdminEmail, $flickRAdminPass, $flickRUserEmail, $flickRUserPSW );
							$companyNameA = $result->Companies->string;
							$totalCompany = count($companyNameA);
							if($totalCompany > 1){
								foreach($companyNameA as $companyName){
									$companyNameS .= $companyNameA . $companyNameA == '' ? '' : ', ';
								}
							}else{
								$companyNameS = $result->Companies->string;
							}
							
							echo '<p id="open_pupupbox"><a href = "javascript:void(0)" onclick = "document.getElementById(\'light\').style.display=\'block\';document.getElementById(\'fade\').style.display=\'block\'">&nbsp;</a></p><div id="light" class="white_content"><div>The password you have specified does not match the records of the digital delivery backend for your email. To access all your content one account, you need to use the same password as you have with the following services:</div><div style="margin-top:5px;">&raquo; ' . $companyNameS . '</div><div style="margin-top:20px;"><a href="'. home_url() .'/wp-admin/profile.php">Change Password</a></div><a href = "javascript:void(0)" onclick = "document.getElementById(\'light\').style.display=\'none\';document.getElementById(\'fade\').style.display=\'none\'"><div class="close_btn"></div></a></div><div id="fade" class="black_overlay"></div>';
						}
						if ($createorder->ErrorCode == '-5'){
							//$error = 'Error: -5(Unknown Error)';
							$error = '';
							$result = self::$flickObj->flickRocketCheckUserExists( $flickRAdminEmail, $flickRAdminPass, $flickRUserEmail, $flickRUserPSW );
							$companyNameA = $result->Companies->string;
							$totalCompany = count($companyNameA);
							if($totalCompany > 1){
								foreach($companyNameA as $companyName){
									$companyNameS .= $companyNameA . $companyNameA == '' ? '' : ', ';
								}
							}else{
								$companyNameS = $result->Companies->string;
							}
							
							echo '<p id="open_pupupbox"><a href = "javascript:void(0)" onclick = "document.getElementById(\'light\').style.display=\'block\';document.getElementById(\'fade\').style.display=\'block\'">&nbsp;</a></p><div id="light" class="white_content"><div>The password you have specified does not match the records of the digital delivery backend for your email. To access all your content one account, you need to use the same password as you have with the following services:</div><div style="margin-top:5px;">&raquo; ' . $companyNameS . '</div><div style="margin-top:20px;"><a href="'. home_url() .'/wp-admin/profile.php">Change Password</a></div><a href = "javascript:void(0)" onclick = "document.getElementById(\'light\').style.display=\'none\';document.getElementById(\'fade\').style.display=\'none\'"><div class="close_btn"></div></a></div><div id="fade" class="black_overlay"></div>';

						}
						if ($createorder->ErrorCode == '-6')
							$error = 'Error: -6(No Customer Password in XML)';
						if ($createorder->ErrorCode == '-7')
							$error = 'Error: -7(Invalid UnlockCode(s))';
						if ($createorder->ErrorCode == '-8')
							$error = 'Error: -8(Expired UnlockCode(s))';
						if ($createorder->ErrorCode == '-9')
							$error = 'Error: -9(UnlockCode(s) usage exceed)';
						if ($createorder->ErrorCode == '-10')
							$error = 'Error: -10(API Usage not allowed. Please contact FlickRocket.)';
						if ($createorder->ErrorCode == '-11')
							$error = 'Error: -11(Invalid project.)';
						if ($createorder->ErrorCode == '-12')
							$error = 'Error: -12(Project deleted.)';
						echo '<div style="margin-bottom:20px;background: no-repeat scroll 10px 9px #FF0000;color:white;padding: 15px 27px 10px 43px;font-size:12px;" class="flick_error">'.$error.'</div>';
					}
				}
			}
			return $orderListData;
		}
	}
	
	
	if(isset($_REQUEST['fr_action']) && $_REQUEST['fr_action'] == 'get_theme_id'){
		$FlickrocketObj 		= new Flickrocket();
		$counter 				= 0;
		$fr_email_address 		= $_REQUEST['fr_email'];
		$fr_password 			= $_REQUEST['fr_password'];
		$responseData 			= array();
		$themeDataArray 		= $FlickrocketObj->flickRocketThemeIDBYAjax($fr_email_address, $fr_password);
		
		if($themeDataArray->GetThemesResult == 1){
			if(count($themeDataArray->Themes->stThemes) > 1){
				foreach($themeDataArray->Themes->stThemes as $themeDetails){
					$responseData[$counter]['ID'] = $themeDetails->ID;
					$responseData[$counter]['Name'] = $themeDetails->Name;
					$counter++;
				}
			}else{
				$responseData[$counter]['ID'] = $themeDataArray->Themes->stThemes->ID;
				$responseData[$counter]['Name'] = $themeDataArray->Themes->stThemes->Name;
			}
		}else{
			$responseData['result'] = 'error';
		}
		
		echo json_encode($responseData);
		exit;
	}
	
	
	if(isset($_REQUEST['fr_action']) && $_REQUEST['fr_action'] == 'check_fr'){ 
		
		$FlickrocketObj 								= new Flickrocket();
	
		$FlickrocketObj->flick_rocket_admin_email 		= $_REQUEST['fr_email'];
		
		$FlickrocketObj->flick_rocket_admin_password	= $_REQUEST['fr_password'];
		
		$FlickrocketObj->flick_rocket_theme_id			= $_REQUEST['fr_themeid'];
		
		$FlickrocketObj->sandbox_active					= $_REQUEST['fr_type'];
	
		echo $FRResult 	= $FlickrocketObj->flickRocketCheckAccount();		
	}

	if(isset($_REQUEST['fr_action']) && $_REQUEST['fr_action'] == 'save_fr'){ 
		
		$FlickrocketObj 								= new Flickrocket();
	
		$FlickrocketObj->flick_rocket_admin_email 		= $_REQUEST['fr_email'];
		
		$FlickrocketObj->flick_rocket_admin_password	= $_REQUEST['fr_password'];
		
		$FlickrocketObj->flick_rocket_theme_id			= $_REQUEST['fr_themeid'];
		
		$FlickrocketObj->sandbox_active					= $_REQUEST['fr_type'];
		
		$fr_type = $_REQUEST['fr_type'] == 'true' ? 'yes' : 'no';
	
		$FRResult 	= $FlickrocketObj->flickRocketCheckAccount();		
				
		if($FRResult == '-1'){ echo '-1'; }
		
		else{
			echo 0;
			update_option( 'flickrocket_user_email', $_REQUEST['fr_email'], '', 'yes' );
			update_option( 'flickrocket_user_password', $_REQUEST['fr_password'], '', 'yes' );
			update_option( 'flickrocket_theme_id', $_REQUEST['fr_themeid'], '', 'yes' );
			update_option( 'sandbox_active', $fr_type, '', 'yes' );
		}
	}
	
?>
