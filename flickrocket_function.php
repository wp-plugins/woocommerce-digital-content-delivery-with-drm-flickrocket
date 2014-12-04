<?php 	
class Flickrocket
{
	private $post_errors 	= array();
	public $admin_email, $admin_pass, $theme_id, $email, $pass, $transactionID, $projectID, $license, $count, $sandbox_active;
	public $flick_rocket_admin_email, $flick_rocket_admin_password, $flick_rocket_theme_id, $soapobj, $webservice_url;
	
	//check live or sandbox flickrocket api
	public function __construct(){
		$useSandbox = get_option( 'sandbox_active', false );
		
		if ($useSandbox == 'yes'){ 
			$webservice_url = 'http://sandbox.flickrocket.com/Services/OnDemandOrder/service.asmx?WSDL';
		
		}else{ 
			$webservice_url = 'https://www.flickrocket.com/Services/OnDemandOrder/service.asmx?WSDL'; 
		}
							
		$this->soapobj 		= new SoapClient($webservice_url, array('trace' => true));
	}
	
	//check flickrocket account
	public function flickRocketCheckAccount(){
				
		$param 				= array();
		$param['EMail'] 	= $this->flick_rocket_admin_email;
		$param['Password'] 	= $this->flick_rocket_admin_password;
		$param['ThemeID'] 	= $this->flick_rocket_theme_id;
				
		if($this->sandbox_active == 'true')
			$webservice_url_chk = 'http://sandbox.flickrocket.com/Services/OnDemandOrder/service.asmx?WSDL';
		else
			$webservice_url_chk	= 'https://www.flickrocket.com/Services/OnDemandOrder/service.asmx?WSDL';
			
						
		$soapobjchk 			= new SoapClient($webservice_url_chk, array('trace' => true));
		$ret 					= $soapobjchk->CheckAccount($param);
				
		if (!empty($ret))
			return $ret->ErrorCode;
		else
			return 1;
	}
	
	//check flickrocket user exists 
	public function flickRocketCheckUserExists( $flickRAdminEmail, $flickRAdminPass, $flickRUserEmail, $flickRUserPSW ){
			
		$param 				= array();
		$param['EMail'] 	= $flickRAdminEmail; 
		$param['Password'] 	= $flickRAdminPass;
		$param['XML'] 		= '<?xml version="1.0" encoding="utf-8" ?>
								<Customer>
									<EMail>'.$flickRUserEmail.'</EMail>
									<Password>'.$flickRUserPSW.'</Password>
								</Customer>';
		return $ret = $this->soapobj->CheckUserExists($param);
	}
	
	//check flickrocket user data
	public function flickRocketPrepareLoginEx( $admin_email, $admin_pass, $theme_id, $email, $pass ){
	
		$param 				= array();
		$param['EMail'] 	= $admin_email;
		$param['Password'] 	= $admin_pass;
		$param['ThemeID'] 	= $theme_id;
		$param['XML'] 		= '<?xml version="1.0" encoding="utf-8" ?>
								<Customer>
									<EMail>'.$email.'</EMail>
									<Password>'.$pass.'</Password>
								</Customer>';
		
		return $ret = $this->soapobj->PrepareLoginEx($param);
	}
	
	//create shop on flickrocket
	public function flickRocketCreateShopOrder( $admin_email, $admin_pass, $theme_id, $email, $pass, $transactionID, $projectID, $license, $count, $xmlString ){
	
		$param 				= array();
		$param['EMail'] 	= $admin_email;
		$param['Password'] 	= $admin_pass;
		$param['XML'] 		= '<?xml version="1.0" encoding="utf-8" ?>
								<Order>
									<TransactionID>'.$transactionID.'</TransactionID>
									<EMail>'.$email.'</EMail>
									<Password>'.$pass.'</Password>
									<ThemeID>'.$theme_id.'</ThemeID>
									<Items>
										'.$xmlString.'
									</Items>
								</Order>';
		
		return $ret = $this->soapobj->CreateShopOrder($param);
	}
	
	//change user password 
	public function flickRocketChangePassword( $admin_email, $admin_pass, $theme_id, $email, $pass, $newpass ){
	
		$param 				= array();
		$param['EMail'] 	= $admin_email;
		$param['Password'] 	= $admin_pass;
		$param['XML'] 		= '<?xml version="1.0" encoding="utf-8" ?>
								<Customer>
									<EMail>'.$email.'</EMail>
									<Password>'.$pass.'</Password>
									<NewPassword>'.$newpass.'</NewPassword>
								</Customer>';
		$ret = $this->soapobj->ChangeCustomerPassword($param);
		return $ret;
	}
	
	
	//reset user password 
	public function flickRocketResetPassword( $admin_email, $admin_pass, $theme_id, $email, $pass, $newpass ){
	
		$param 				= array();
		$param['EMail'] 	= $admin_email;
		$param['Password'] 	= $admin_pass;
		$param['XML'] 		= '<?xml version="1.0" encoding="utf-8" ?>
								<Customer>
									<EMail>'.$email.'</EMail>
									<Password>_use_only_after_external_validation_</Password>
									<NewPassword>'.$newpass.'</NewPassword>
								</Customer>';
		$ret = $this->soapobj->ChangeCustomerPassword($param);
		return $ret;
	}
	
	//change user email address 
	public function flickRocketChangeEmail( $admin_email, $admin_pass, $theme_id, $email, $pass, $newemail ){
	
		$param 				= array();
		$param['EMail'] 	= $admin_email;
		$param['Password'] 	= $admin_pass;
		$param['XML'] 		= '<?xml version="1.0" encoding="utf-8" ?>
								<Customer>
									<EMail>'.$email.'</EMail>
									<NewEMail>'.$newemail.'</NewEMail>
									<Password>'.$pass.'</Password>
								</Customer>';
								
		$ret = $this->soapobj->ChangeCustomerEmail($param);
		return $ret;
	}
	
	//get projects list select box from flickrocket api
	public function flickRocketGetProjects($fieldName, $selectedValue){
		
		$FRObj				= new FlickRocketWooocommerce();
		$config 			= $FRObj::get_flickrocket_config_data();		
		
		$param 				= array();
		$param['EMail'] 	= $config['flickrocket_user_email'];
		$param['Password'] 	= $config['flickrocket_user_password'];
		$param['ThemeID'] 	= $config['flickrocket_theme_id'];
		
		$fieldsValue 		= $this->soapobj->GetProjects($param);
		$selectBox = '';
		
		$selectBox = '<select name="' . $fieldName . '"><option value=""> -- Select Project ID -- </option>';
		
		if(count($fieldsValue->Projects->stProject) > 1){
			foreach($fieldsValue->Projects->stProject as $sbValue){
				$selectedField = $sbValue->LongProjectID == $selectedValue ? 'selected' : '';
				$selectBox .= '<option value="' . $sbValue->LongProjectID . '" ' . $selectedField . '>' . $sbValue->Name . ' (' . $sbValue->LongProjectID . ') </option>';
			}
		}else{
			$sbValue = $fieldsValue->Projects->stProject;
			$selectedField = $sbValue->LongProjectID == $selectedValue ? 'selected' : '';
			$selectBox .= '<option value="' . $sbValue->LongProjectID . '" ' . $selectedField . '>' . $sbValue->Name . ' (' . $sbValue->LongProjectID . ') </option>';
		}
		
		$selectBox .= '</select>';
		
		return $selectBox;
	}
	
	//get flickrocket license data
	public function flickRocketGetLicenses(){
		
		$FRObj				= new FlickRocketWooocommerce();
		$config 			= $FRObj::get_flickrocket_config_data();		
		
		$param 				= array();
		$param['EMail'] 	= $config['flickrocket_user_email'];
		$param['Password'] 	= $config['flickrocket_user_password'];
		
		$fieldsValue 		= $this->soapobj->GetLicenses($param);
				
		return $fieldsValue;
	}
	
	
	//get flickrocket theme id
	public function flickRocketThemeID(){
		$FRObj				= new FlickRocketWooocommerce();
		$config 			= $FRObj::get_flickrocket_config_data();		
		
		$param 				= array();
		$param['EMail'] 	= $config['flickrocket_user_email'];
		$param['Password'] 	= $config['flickrocket_user_password'];
		
		$fieldsValue 		= $this->soapobj->GetThemes($param);

		return $fieldsValue;
	}
	
	
	//get flickrocket theme id
	public function flickRocketThemeIDBYAjax($fr_email_address, $fr_password){
		$param 				= array();
		$param['EMail'] 	= $fr_email_address;
		$param['Password'] 	= $fr_password;
		
		$fieldsValue 		= $this->soapobj->GetThemes($param);

		return $fieldsValue;
	}
}


?>
