<?php 
/*
add_action('init', 'ax_user_mgmt_check_login_as');

function ax_user_mgmt_check_login_as()
{
  if ($_GET['axuh'])
  {
    $hash = $_GET['axuh'];
    $user_id = $_GET['axu'];
    
    //resopnses are 'valid', 'invalid', 'expired' - expired is returned if the hash is sent more than 2 minutes after it is generated.
    
    $response = AxelradUserMgmt::verify_user_login_as_hash($user_id, $hash);
    if ($response == 'valid')
    {
      $user = get_user_by( 'id', $user_id ); 
      if( $user ) 
      {
          wp_set_current_user( $user_id, $user->user_login );
          wp_set_auth_cookie( $user_id );
          do_action( 'wp_login', $user->user_login, $user );
      }
    }
  }
}
*/