<?php

add_action('delete_post', 'ax_access_ctrl_post_delete');
add_action('save_post', 'ax_access_ctrl_save_post');

function ax_access_ctrl_post_delete($post_id)
{
  AxelradAccessControl::post_deleted($post_id);
}

function ax_access_ctrl_save_post($post_id)
{
  AxelradAccessControl::post_saved($post_id);
}

function ax_access_ctrl_wp_init()
{
  
  if (!AxelradAccessControl::$enabled) return;
  
  if (_ax_req('action') == 'logout')
  {
    wp_logout();
    wp_redirect(AxelradAccessControl::$logout_url.'?loggedout=true');
    die;
  }

  _ax_debug('calling: AxelradUserMgmtAccessCtrl::enforce_access();');
  AxAccessCtrlEnforce::enforce_access();
}

add_action('init', 'ax_access_ctrl_wp_init');


function ax_access_ctrl_handle_login($user_login, $user)
{
  //see if this is the first time they're logging in or resetting password. If so, show the new password form
  $user_id = $user->ID;
  AxelradUserMgmt::user_logged_in($user_id);
  //just a normal login
  if ($_POST['redirect_to'] != '')
  {
    wp_redirect($_POST['redirect_to']);
    die;
  }
  else
  {
    wp_redirect(AxelradAccessControl::get_user_home_url());
    die;
  }
}

add_action('wp_login', 'ax_access_ctrl_handle_login', 10, 2);

function _ax_security_login_failed() 
{
  wp_redirect(AxelradAccessControl::format_login_url('?login=failed&redirect_to='.rawurlencode(_ax_req('redirect_to'))));
  exit;
}

add_action( 'wp_login_failed', '_ax_security_login_failed' );


function ax_access_ctrl_display_security_column( $column, $post_id ) 
{
    if ($column == 'security')
    {
        echo _ax_post_get_security_type_display_name(_ax_post_get_security_type($post_id));
    }
}
add_action( 'manage_posts_custom_column' , 'ax_access_ctrl_display_security_column', 10, 2 );



/*this will add column in user list table*/

function ax_access_ctrl_add_column( $column ) 
{
    $column['num_logins'] = 'Login count';
    return $column;
}
add_filter( 'manage_users_columns', 'ax_access_ctrl_add_column' );

/*this will add column value in user list table*/
function ax_access_ctrl_add_column_value( $val, $column_name, $user_id ) 
{
    switch($column_name) {

        case 'num_logins' :
            return AxelradUserMgmt::login_count($user_id);
            break;

           default:
    }
}
add_filter( 'manage_users_custom_column', 'ax_access_ctrl_add_column_value', 10, 3 );
