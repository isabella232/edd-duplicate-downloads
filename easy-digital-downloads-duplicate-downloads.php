<?php
/*
Plugin Name: Easy Digital Downloads Duplicate Downloads
Plugin URI: http://easydigitaldownloads.com/chris-is-amazing
Description: Adds the ability to duplicate downloads
Author: Chris Christoff
Author URI: http://www.chriscct7.com
Version: 1.0
*/
if ( class_exists( 'Easy_Digital_Downloads' ) ) {
    
    function edd_dde_updater() {
        define( 'EDD_DDE_STORE_URL', 'https://easydigitaldownloads.com' );
        define( 'EDD_DDE', 'Duplicate Downloads' );
        define( 'EDD_DDE_VERSION', '1.0' );
        
        if ( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {
            include( dirname( __FILE__ ) . '/EDD_SL_Plugin_Updater.php' );
        }
        
        // retrieve our license key from the DB
        $license_key = trim( get_option( 'edd_dde_license_key' ) );
        
        // setup the updater
        $edd_updater = new EDD_SL_Plugin_Updater( EDD_DDE_STORE_URL, __FILE__, array(
             'version' => EDD_DDE_VERSION, // current version number
            'license' => $license_key, // license key (used get_option above to retrieve from DB)
            'item_name' => EDD_DDE, // name of this plugin
            'author' => 'Chris Christoff' // author of this plugin
        ) );
    }
    add_action( 'admin_init', 'edd_dde_updater' );
    add_action( 'admin_menu', 'edd_dde_license_menu' );
    add_action( 'admin_init', 'edd_dde_register_option' );
    add_action( 'admin_init', 'edd_dde_deactivate_license' );
    add_action( 'admin_init', 'edd_dde_activate_license' );
    
    register_activation_hook( __FILE__, 'dpactivation' );
    function dpactivation() {
        // checks if the EDD plugin is running and disables this plugin if it's not (and displays a message)
        if ( !( is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' ) ) ) {
            deactivate_plugins( plugin_basename( __FILE__ ) );
            wp_die( sprintf( _x( 'The Easy Digital Downloads Duplicate Product plugin requires %s to be activated in order to work. Please activate %s first.', 'A link to Easy Digital Downloads is provided in the placeholders', 'edd_duplicate_downloads' ), '<a href="http://www.easy-digital-downloads.com" target="_blank">edd</a>', '<a href="http://edd.com" target="_blank">Easy Digital Downloads</a>' ) . '<a href="' . admin_url( 'plugins.php' ) . '"> <br> &laquo; ' . _x( 'Go Back', 'Activation failed, so go back to the plugins page', 'edd_duplicate_downloads' ) . '</a>' );
        }
    }
    require_once( dirname( __FILE__ ) . '/duplicate.php' );
    require_once( dirname( __FILE__ ) . '/admin.php' );
    
    
    
    function edd_dde_license_menu() {
        add_plugins_page( 'EDD DDE', 'EDD DDE', 'manage_options', 'edd-dde-license', 'edd_dde_license_page' );
    }
    
    function edd_dde_license_page() {
        $license = get_option( 'edd_dde_license_key' );
        $status  = get_option( 'edd_dde_license_status' );
?>
			<div class="wrap">
				<h2><?php
        _e( 'EDD DDE License Options' );
?></h2>
				<form method="post" action="options.php">
				
				<?php
        settings_fields( 'edd_dde_license' );
?>
			
				<table class="form-table">
					<tbody>
						<tr valign="top">	
							<th scope="row" valign="top">
								<?php
        _e( 'License Key' );
?>
							</th>
							<td>
								<input id="edd_dde_license_key" name="edd_dde_license_key" type="text" class="regular-text" value="<?php
        esc_attr_e( $license );
?>" />
								<label class="description" for="edd_dde_license_key"><?php
        _e( 'Enter your license key' );
?></label>
							</td>
						</tr>
						<?php
        if ( false !== $license ) {
?>
						<tr valign="top">	
							<th scope="row" valign="top">
								<?php
            _e( 'Activate License' );
?>
							</th>
							<td>
								<?php
            if ( $status !== false && $status == 'valid' ) {
?>
									<span style="color:green;"><?php
                _e( 'active' );
?></span>
									<?php
                wp_nonce_field( 'edd_dde_nonce', 'edd_dde_nonce' );
?>
									<input type="submit" class="button-secondary" name="edd_license_deactivate" value="<?php
                _e( 'Deactivate License' );
?>"/>
								<?php
            } else {
                wp_nonce_field( 'edd_dde_nonce', 'edd_dde_nonce' );
?>
									<input type="submit" class="button-secondary" name="edd_license_activate" value="<?php
                _e( 'Activate License' );
?>"/>
								<?php
            }
?>
							</td>
						</tr>
					<?php
        }
?>
				</tbody>
				</table>	
				<?php
        submit_button();
?>
		
			</form>
		<?php
    }
    
    function edd_dde_register_option() {
        // creates our settings in the options table
        register_setting( 'edd_dde_license', 'edd_dde_license_key', 'edd_dde_sanitize_license' );
    }
    
    function edd_dde_sanitize_license( $new ) {
        $old = get_option( 'edd_dde_license_key' );
        if ( $old && $old != $new ) {
            delete_option( 'edd_dde_license_status' ); // new license has been entered, so must reactivate
        }
        return $new;
    }
    
    function edd_dde_activate_license() {
        
        // listen for our activate button to be clicked
        if ( isset( $_POST[ 'edd_license_activate' ] ) ) {
            
            // run a quick security check 
            if ( !check_admin_referer( 'edd_dde_nonce', 'edd_dde_nonce' ) )
                return; // get out if we didn't click the Activate button
            
            // retrieve the license from the database
            $license = trim( get_option( 'edd_dde_license_key' ) );
            
            
            // data to send in our API request
            $api_params = array(
                 'edd_action' => 'activate_license',
                'license' => $license,
                'item_name' => urlencode( EDD_DDE ) // the name of our product in EDD
            );
            
            // Call the custom API.
            $response = wp_remote_get( add_query_arg( $api_params, EDD_DDE_STORE_URL ), array(
                 'timeout' => 15,
                'sslverify' => false 
            ) );
            
            // make sure the response came back okay
            if ( is_wp_error( $response ) )
                return false;
            
            // decode the license data
            $license_data = json_decode( wp_remote_retrieve_body( $response ) );
            
            // $license_data->license will be either "active" or "inactive"
            
            update_option( 'edd_dde_license_status', $license_data->license );
            
        }
    }
    
    function edd_dde_deactivate_license() {
        
        // listen for our activate button to be clicked
        if ( isset( $_POST[ 'edd_license_deactivate' ] ) ) {
            
            // run a quick security check 
            if ( !check_admin_referer( 'edd_dde_nonce', 'edd_dde_nonce' ) )
                return; // get out if we didn't click the Activate button
            
            // retrieve the license from the database
            $license = trim( get_option( 'edd_dde_license_key' ) );
            
            
            // data to send in our API request
            $api_params = array(
                 'edd_action' => 'deactivate_license',
                'license' => $license,
                'item_name' => urlencode( EDD_DDE ) // the name of our product in EDD
            );
            
            // Call the custom API.
            $response = wp_remote_get( add_query_arg( $api_params, EDD_DDE_STORE_URL ), array(
                 'timeout' => 15,
                'sslverify' => false 
            ) );
            
            // make sure the response came back okay
            if ( is_wp_error( $response ) )
                return false;
            
            // decode the license data
            $license_data = json_decode( wp_remote_retrieve_body( $response ) );
            
            // $license_data->license will be either "deactivated" or "failed"
            if ( $license_data->license == 'deactivated' )
                delete_option( 'edd_dde_license_status' );
            
        }
    }
    
    
    function edd_dde_check_license() {
        
        global $wp_version;
        
        $license = trim( get_option( 'edd_dde_license_key' ) );
        
        $api_params = array(
             'edd_action' => 'check_license',
            'license' => $license,
            'item_name' => urlencode( EDD_DDE ) 
        );
        
        // Call the custom API.
        $response = wp_remote_get( add_query_arg( $api_params, EDD_DDE_STORE_URL ), array(
             'timeout' => 15,
            'sslverify' => false 
        ) );
        
        
        if ( is_wp_error( $response ) )
            return false;
        
        $license_data = json_decode( wp_remote_retrieve_body( $response ) );
        
        if ( $license_data->license == 'valid' ) {
            return true;
            // this license is still valid
        } else {
            return false;
            // this license is no longer valid
        }
    }
}