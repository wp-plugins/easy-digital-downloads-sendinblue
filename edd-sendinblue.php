<?php
/*
Plugin Name: Easy Digital Downloads - Sendinblue
Plugin URL: http://easydigitaldownloads.com/extension/sendinblue
Description: Include a Sendinblue signup option with your Easy Digital Downloads checkout
Version: 1.0
Author: neeraj_slit,SendinBlue
Author URI: https://www.sendinblue.com
Contributors: SendinBlue
*/

// adds the settings to the Misc section
function eddsendinblue_add_settings($settings) {
  
  $eddsendinblue_settings = array(
		array(
			'id' => 'eddsendinblue_settings',
			'name' => '<strong>' . __('Sendinblue Settings', 'eddsendinblue') . '</strong>',
			'desc' => __('Configure sendinblue Integration Settings', 'eddsendinblue'),
			'type' => 'header'
		),
        array(
			'id' => 'eddsendinblue_access_key',
			'name' => __('Acces Key', 'eddsendinblue'),
			'desc' => __('Enter your sendinblue access Key. It is located in the API & Integration area of your sendinblue account.', 'eddsendinblue'),
			'type' => 'text',
			'size' => 'regular'
		),
		array(
			'id' => 'eddsendinblue_secret_key',
			'name' => __('Secret Key', 'eddsendinblue'),
			'desc' => __('Enter your sendinblue Secret Key. It is located in the API & Integration area of your sendinblue account.', 'eddsendinblue'),
			'type' => 'text',
			'size' => 'regular'
		),
		array(
			'id' => 'eddsendinblue_list_id',
			'name' => __('List ID', 'eddsendinblue'),
			'desc' => __('Select your List ID. Note: this option appears only after you have entered and saved your Access Key in the field above.', 'eddsendinblue'),
			'type' => 'select',
			'options' => eddsendinblue_get_sendinblue_lists()
		),
		array(
			'id' => 'eddsendinblue_label',
			'name' => __('Checkout Label', 'eddsendinblue'),
			'desc' => __('This is the text shown next to the signup option', 'eddsendinblue'),
			'type' => 'text',
			'size' => 'regular'
		)
	);
	
	return array_merge($settings, $eddsendinblue_settings);
}
add_filter('edd_settings_misc', 'eddsendinblue_add_settings');

// get an array of all sendinblue subscription lists
function eddsendinblue_get_sendinblue_lists() {
	
	global $edd_options;
	
	if( isset( $edd_options['eddsendinblue_access_key'] ) && strlen( trim( $edd_options['eddsendinblue_access_key'] ) ) > 0 ) {
		
		$lists = array();
		if( !class_exists( 'Mailin' ) )
            require_once('inc/sendinblue.class.php');
		$api = new Mailin('https://api.sendinblue.com/v1.0',$edd_options['eddsendinblue_access_key'],$edd_options['eddsendinblue_secret_key']);
		$retval = $api->get_lists();
		$retval = $retval['data'];
		if($retval) :
			foreach($retval as $list) :
				$lists[$list['id']] = $list['name'];
			endforeach;
		endif;
		return $lists;
	}
	return array();
}
    
// adds an email to the sendinblue subscription list
function eddsendinblue_subscribe_email($email, $first_name = 'xx', $last_name = '' ) {
	global $edd_options;
	
	if( isset( $edd_options['eddsendinblue_access_key'] ) && strlen( trim( $edd_options['eddsendinblue_access_key'] ) ) > 0 ) {

		if( ! isset( $edd_options['eddsendinblue_list_id'] ) || strlen( trim( $edd_options['eddsendinblue_list_id'] ) ) <= 0 )
			return false;
        
        require_once('inc/sendinblue.class.php');

        $api = new Mailin('https://api.sendinblue.com/v1.0',$edd_options['eddsendinblue_access_key'],$edd_options['eddsendinblue_secret_key']);
        
        $id = array($edd_options['eddsendinblue_list_id']);

		$attributes = array("NAME"=>$first_name, "SURNAME"=>$last_name); // name of attribute should be present in your acount
		$blacklisted = 0;
		$listid_unlink = array();
		$retval=$api->create_update_user($email,$attributes,$blacklisted,$id,$listid_unlink);
      
        
	}

	return false;
}

// displays the sendinblue checkbox
function eddsendinblue_sendinblue_fields() {
	global $edd_options;
	ob_start(); 
		if( isset( $edd_options['eddsendinblue_access_key'] ) && strlen( trim( $edd_options['eddsendinblue_access_key'] ) ) > 0 ) { ?>
		<p>
			<input name="eddsendinblue_sendinblue_signup" id="eddsendinblue_sendinblue_signup" type="checkbox" checked="checked"/>
			<label for="eddsendinblue_sendinblue_signup"><?php echo isset($edd_options['eddsendinblue_label']) ? $edd_options['eddsendinblue_label'] : __('Sign up for our mailing list', 'eddsendinblue'); ?></label>
		</p>
		<?php
	}
	echo ob_get_clean();
}
add_action('edd_purchase_form_before_submit', 'eddsendinblue_sendinblue_fields', 100);

// checks whether a user should be signed up for the sendinblue list
function eddsendinblue_check_for_email_signup($posted, $user_info) {
	if($posted['eddsendinblue_sendinblue_signup']) {

		$email = $user_info['email'];
		eddsendinblue_subscribe_email($email, $user_info['first_name'], $user_info['last_name'] );
	}
}
add_action('edd_checkout_before_gateway', 'eddsendinblue_check_for_email_signup', 10, 2);
