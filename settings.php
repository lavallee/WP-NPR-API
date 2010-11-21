<?php

/*
 * NPR API Settings Page and related control methods.
 */
function npr_add_options_page() {
    add_options_page( 'NPR API', 'NPR API', 'manage_options',
                      'npr_api', 'npr_api_options_page' );
}
add_action( 'admin_menu', 'npr_add_options_page' );


function npr_api_options_page() {
?>
    <div>
        <h2>NPR API settings</h2>
        <form action="options.php" method="post">
            <p>If you don't have an API key, go <a href="http://www.npr.org/api/index">here</a> to get one.</p>

            <?php settings_fields( 'npr_api' ); ?>
            <?php do_settings_sections( 'npr_api' ); ?>

            <input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
        </form>
    </div>
<?php
}


function npr_settings_init() {
    add_settings_section( 'npr_api_settings', 'NPR API settings', 'npr_api_settings_callback', 'npr_api' );

    add_settings_field( 'npr_api_key', 'API KEY', 'npr_api_key_callback', 'npr_api', 'npr_api_settings' );
    register_setting( 'npr_api', 'npr_api_key' );

}
add_action( 'admin_init', 'npr_settings_init' );

function npr_api_settings_callback() { }

function npr_api_key_callback() {
    $option = get_option( 'npr_api_key' );
    echo "<input type='text' value='$option' name='npr_api_key' style='width: 300px;' />"; 
}


?>
