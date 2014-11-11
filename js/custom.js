jQuery(document).ready(function(){
	
	var flickRCB = 0;
	var flickRocketPMB = jQuery('#flickrocket_projectid');
	flickRocketPMB.insertAfter('#woocommerce-product-data');
	
	jQuery('#fr_message').hide();
	var tabLink = document.URL;
	var linkArray = tabLink.split('=');
	var tabName = linkArray[2];
	if(tabName == 'flickrocket') {
		jQuery('input.button-primary').hide();
	}
	jQuery("#check_fr_details").click(function(){
		var flickrocket_user_email 		= jQuery('#flickrocket_user_email').val();
		var flickrocket_user_password 	= jQuery('#flickrocket_user_password').val();
		var flickrocket_theme_id 		= jQuery('#flickrocket_theme_id').val();
		var sandbox_active 				= jQuery("#sandbox_active").is(':checked');
						
		if(flickrocket_user_email == ''){
			jQuery('#fr_message').show();
			jQuery('#fr_message').html('<div class="error" style="padding:10px;">Please enter user email address.</div>');
			document.getElementById('flickrocket_user_email').focus();
			return false;
		}
		else if(flickrocket_user_password == ''){
			jQuery('#fr_message').show();
			jQuery('#fr_message').html('<div class="error" style="padding:10px;">Please enter password.</div>');
			document.getElementById('flickrocket_user_password').focus();
			return false;
		}
		else if(flickrocket_theme_id == ''){
			jQuery('#fr_message').show();
			jQuery('#fr_message').html('<div class="error" style="padding:10px;">Please enter theme id.</div>');
			document.getElementById('flickrocket_theme_id').focus();
			return false;
		}
		else{
			jQuery('#fr_message').show();
			jQuery('#fr_message').html('<div class="updated" style="padding:10px;"><img src="images/loading.gif" alt=""></div>');
			jQuery.ajax({
				type: "POST",
				url: "?fr_action=check_fr&fr_email="+flickrocket_user_email+"&fr_password="+flickrocket_user_password+"&fr_themeid="+flickrocket_theme_id+"&fr_type="+sandbox_active,
				dataType:'json',
				cache: false,
				success: function(data_return){	
					if(data_return < 0){
						jQuery('#fr_message').html('<div class="error" style="padding:10px;">Wrong FlickRocket username or password.</div>');
					}else{
						jQuery('#fr_message').html('<div class="updated" style="padding:10px;">FlickRocket username and password is valid.</div>');
					}
				} 
			});
		}
	});
	
	
	jQuery("#save_fr_details").click(function(){
		var flickrocket_user_email 		= jQuery('#flickrocket_user_email').val();
		var flickrocket_user_password 	= jQuery('#flickrocket_user_password').val();
		var flickrocket_theme_id 		= jQuery('#flickrocket_theme_id').val();
		var sandbox_active 				= jQuery("#sandbox_active").is(':checked');
						
		if(flickrocket_user_email == ''){
			jQuery('#fr_message').show();
			jQuery('#fr_message').html('<div class="error" style="padding:10px;">Please enter user email address.</div>');
			document.getElementById('flickrocket_user_email').focus();
			return false;
		}
		else if(flickrocket_user_password == ''){
			jQuery('#fr_message').show();
			jQuery('#fr_message').html('<div class="error" style="padding:10px;">Please enter password.</div>');
			document.getElementById('flickrocket_user_password').focus();
			return false;
		}
		else if(flickrocket_theme_id == ''){
			jQuery('#fr_message').show();
			jQuery('#fr_message').html('<div class="error" style="padding:10px;">Please enter theme id.</div>');
			document.getElementById('flickrocket_theme_id').focus();
			return false;
		}
		else{
			jQuery('#fr_message').show();
			jQuery('#fr_message').html('<div class="updated" style="padding:10px;"><img src="images/loading.gif" alt=""></div>');
			jQuery.ajax({
				type: "POST",
				url: "?fr_action=save_fr&fr_email="+flickrocket_user_email+"&fr_password="+flickrocket_user_password+"&fr_themeid="+flickrocket_theme_id+"&fr_type="+sandbox_active,
				dataType:'json',
				cache: false,
				success: function(data_return){	
					if(data_return < 0){
						jQuery('#fr_message').html('<div class="error" style="padding:10px;">Wrong FlickRocket username or password.</div>');
					}else{
						jQuery('#fr_message').html('<div class="updated" style="padding:10px;">FlickRocket username and password are valid and have been stored.</div>');
					}
				} 
			});
		}
	});
		
	jQuery(".flick_error").hide();
	jQuery("#open_pupupbox").find("a").trigger("click");
		
	jQuery('#flickrocket_license_area').hide();	
	
	if(jQuery('#product-type').val() == 'simple'){
		if(jQuery('#_product_license_id').val() != '' && jQuery('#flickrocket_project_id').val() != ''){
			jQuery('#_flickrocket').attr('checked', true);
		}
		if( jQuery('#_flickrocket').is(':checked') == true){
			jQuery('#flickrocket_license_area').show();	
			jQuery('#flickrocket_projectid').show();
		}
	}
		
	jQuery('#_flickrocket').click(function(){
		if(jQuery('#_flickrocket').is(':checked') == true){		
			jQuery('#flickrocket_license_area').show();	
			jQuery('#flickrocket_projectid').show();
		}else{
			jQuery('#flickrocket_license_area').hide();
			jQuery('#flickrocket_projectid').hide();
		}
	});
	
	jQuery('#product-type').change(function(){
		if((jQuery('#product-type').val() == 'simple') && (jQuery('#_flickrocket').is(':checked') == true)){
			jQuery('#flickrocket_license_area').show();	
			jQuery('#flickrocket_projectid').show();
		}else{
			jQuery('#flickrocket_license_area').hide();	
			jQuery('#flickrocket_projectid').hide();
		}
	});
	
	
	jQuery('.fr_settings_fields').change(function(){
		var emailAddress = jQuery('#flickrocket_user_email').val();
		var password = jQuery('#flickrocket_user_password').val();
		if(emailAddress != '' && password != ''){
			jQuery('#fr_message').show();
			jQuery('#fr_message').html('<div class="updated" style="padding:10px;"><img src="images/loading.gif" alt=""></div>');
			jQuery.ajax({
				type: "POST",
				url: "?fr_action=get_theme_id&fr_email="+emailAddress+"&fr_password="+password,
				dataType:'json',
				cache: false,
				success: function(data_return){	
					jQuery('#flickrocket_theme_id').html('');
					if(data_return.result == 'error'){
						jQuery('#fr_message').html('<div class="error" style="padding:10px;">Wrong FlickRocket username or password.</div>');
					}else{
						jQuery('#fr_message').hide();
						var optionHtml = '';
						var len = data_return.length;
                        for(var i=0; i<len; i++){
							optionHtml = optionHtml  + "<option value= '"+data_return[i].ID+"' > " + data_return[i].Name + " ( " + data_return[i].ID + " ) </option>";
						}

           				 var optionWithSelect = "<option value= '' > Selelct Theme ID </option>"+optionHtml;
			
						jQuery('#flickrocket_theme_id').html(optionWithSelect);
					}
				}
			});
		}
	});
	
	jQuery('.woocommerce_variation').each(function(index, value){
		if(jQuery('#checkBoxVMB_'+index).is(':checked') == true){
			flickRCB++;
			jQuery('#flickRocketVMB_'+index).show();
		}else{
			jQuery('#flickRocketVMB_'+index).hide();
		}
	});
	
	if(jQuery('#product-type').val() == 'variable'){
		setTimeout(function(){
			if(flickRCB > 0){
				jQuery('#flickrocket_projectid').show();
			}else{
				('#flickrocket_projectid').hide();
			}		
		}, 500)
	}
	
	jQuery('.checkBoxVMB').live('click', function(){
		var checkBoxIndex = this.alt;
		if(jQuery(this).is(':checked') == true){
			flickRCB++;
			jQuery('#flickRocketVMB_' + checkBoxIndex).show();
			jQuery('input[name="variable_is_virtual[' + checkBoxIndex + ']"]').attr('checked', true);
			jQuery('#flickRocketVMB_' + checkBoxIndex).parent().parent().parent().parent().parent().find('.hide_if_variation_virtual').hide();
		}else{
			flickRCB--;
			jQuery('#flickRocketVMB_' + checkBoxIndex).hide();
			jQuery('input[name="variable_is_virtual[' + checkBoxIndex + ']"]').attr('checked', false);			
			jQuery('#flickRocketVMB_' + checkBoxIndex).parent().parent().parent().parent().parent().find('.hide_if_variation_virtual').show();
		}
		
		if(flickRCB > 0){
			jQuery('#flickrocket_projectid').show();
		}else{
			jQuery('#flickrocket_projectid').hide();
		}		
	});

}); 
