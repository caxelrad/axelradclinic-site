<?php

class UsersAppModel extends MercuryRestModel
{
  
  public static function email_exists($data)
  {
    return AxelradUserMgmt::find_user_by_email($data['email']) > 0;
  }
  
  public static function get_user_login_as_url($user_id)
  {
    return AxelradUserMgmt::generate_login_as_url($user_id); //generates a url that is good for 2 mins
  }
  
  public static function users_fetch_page($data, $page_num, $page_size)
  {
    $search = $data['search'];
    $group_name = $data['group_name'];
    if ($group_name && $group_name != AxelradUserMgmt::$default_group_name)
    {
      $rows = AxelradUserMgmt::get_members($group_name, $page_size, $page_num, $search);
      $count = AxelradUserMgmt::get_member_count($group_name);
    }
    else
    {
      $rows = AxelradUserMgmt::get_users($page_size, $page_num, $search);
      $count = AxelradUserMgmt::get_total_user_count();
    }
    
    return ['rows' => $rows, 'total_count' => $count, 'group_name' => $group_name, 'search' => $search];
  }
  
  public static function user_fetch($data)
  {
    $user = AxelradUserMgmt::get_user($data['id']);
    $user['membership_count'] = AxelradUserMgmt::get_membership_count($data['id']);
    return $user;
  }
  
  public static function groups_fetch($data)
  {
    return AxelradUserMgmt::groups_fetch_internal();
  }
  
  public static function memberships_fetch($data)
  {
    return AxelradUserMgmt::get_user_groups($data['user_id']);
  }
  
  public static function membership_delete($data)
  {
    return AxelradUserMgmt::rmv_group_member($data['group_id'], $data['user_id']);
  }
  
  public static function membership_create($data)
  {
    return AxelradUserMgmt::add_group_member($data['group_id'], $data['user_id']);
  }
  
  
  public static function user_create($data)
  {
    $user_id = AxelradUserMgmt::create_user($data['email'], $data['first_name'], $data['last_name'], AxelradUserMgmt::$default_group_name);
    return self::user_fetch(['id' => $user_id]);
  }
  
  public static function user_update($data)
  {
    $props = [];
    
    self::copy_prop($data, $props, 'first_name');
    self::copy_prop($data, $props, 'last_name');
    self::copy_prop($data, $props, 'email');
    
    return AxelradUserMgmt::update_user($data['id'], $props);
  }
  
  static function copy_prop($source, $dest, $name)
  {
    if (array_key_exists($name, $source))
      $dest[$name] = $source[$name];
  }
  
  public static function user_delete($data)
  {
    return AxelradUserMgmt::delete_user($data['id']);
  }
  
  
}