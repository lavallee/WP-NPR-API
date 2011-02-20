<?php
    // XXX: this may not always work
    define( 'WP_ADMIN', false );
    require( '../../../wp-admin/admin.php' );
    $load_story_url_base = add_query_arg( array( 'create_draft' => 'true', 'page' => 'get-npr-stories' ), get_admin_url() . 'edit.php' ) . '&story_id=';

?>

// Get properties from page
var story_hostname = window.location.hostname;
var story_url = window.location;
var story_title = escape( document.title );

if (story_hostname != 'www.npr.org') {
    alert( "This bookmarklet is intended to be used in conjunction with content on NPR.org" );
} else {
    var story_id = NPR.community.storyId;
    var new_url = '<?php echo $load_story_url_base; ?>' + story_id;
    location.replace( new_url );
}
