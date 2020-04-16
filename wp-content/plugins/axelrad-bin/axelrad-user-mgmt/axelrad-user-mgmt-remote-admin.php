<?php

function _ax_user_remote_edit_func($atts)
{
  
  _ax_debug('entering _ax_user_remote_edit_func');
  
  $key = _ax_req('x');
  
  _ax_debug_v('$key', $key);
  
  if ($key != AxUsers::$remote_admin_key)
    return '';
  
  $action = _ax_req('action');
 _ax_debug_v('$action', $action);

  if ($action == 'create_forms')
  {
    AxUserForms::create_pages();
  }
  else if ($action == 'create' || $action == 'update')
  {	
    
    $email = _ax_req('email');
    $first_name = _ax_req('first_name');
    $last_name = _ax_req('last_name');
    
    $external_id = _ax_req('external_id');
    $role_name = _ax_req('role_name');
    $groups = _ax_req('groups'); // | separated group names

    _ax_debug_v('$email', $email);
    _ax_debug_v('$first_name', $first_name);
    _ax_debug_v('$last_name', $last_name);
    _ax_debug_v('$external_id', $external_id);
    _ax_debug_v('$role_name', $role_name);
    _ax_debug_v('$groups', $groups);

    
    $user_id = AxUsers::write_user($email, $first_name, $last_name, $role_name, $groups);
    
    if ($external_id)
      AxUsers::set_external_id($user_id, $external_id);
    
    _ax_debug_v('$user_id', $user_id);
    return $user_id;
  }
  else if ($action == 'add_to_group')
  {
    $email = _ax_req('email');
    $group_name = _ax_req('group_name');
    _ax_debug('email '.$email);
    _ax_debug('group_name '.$group_name);
    
    if ($email == '' || $group_name == '') return; //can't do it without the info
    
    $user_id = AxUsers::find_user_id_by_email($email);
    if ($user_id > 0)
      AxUsers::add_to_group($group_name, $user_id);
    
  }
  else if ($action == 'rmv_from_group')
  {
    $email = _ax_req('email');
    $group_name = _ax_req('group_name');
    
    if ($email == '' || $group_name == '') return; //can't do it without the info
    
    $user_id = AxUsers::find_user_id_by_email($email);
    if ($user_id > 0)
      AxUsers::rmv_from_group($group_name, $user_id);
    
  }
  else if ($action == 'delete')
  {
    _ax_debug('the user is being removed from the system');
    $user_id = AxUsers::username_exists($email);
    if ($user_id > 0)
      AxUsers::delete_user($user_id);
    return $user_id;
  }
}

add_shortcode('_ax_user_remote_edit', '_ax_user_remote_edit_func');

function _ax_user_get_value($key)
{
  return $_POST[$key] != '' ? $_POST[$key] : $_GET[$key];
}
