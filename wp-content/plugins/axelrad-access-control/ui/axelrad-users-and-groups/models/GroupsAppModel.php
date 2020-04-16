<?php

class GroupsAppModel
{
  public static function groups_fetch($data)
  {
    $search = '';
    if (is_array($data))
      $search = $data['search'];
    
    _ax_debug('fetching groups');
    return AxelradUserMgmt::groups_fetch_internal($search);
  }
  
  public static function group_fetch($data)
  {
    $group = AxelradUserMgmt::get_group($data['id']);
    $group['member_count'] = AxelradUserMgmt::get_member_count($data['id']);
    return $group;
  }
  
  
  public static function group_create($data)
  {
    return AxelradUserMgmt::group_create_internal($data['name'], $data['display_name'], $data['description']);
  }
  
  public static function group_update($data)
  {
    return AxelradUserMgmt::group_update_internal($data['id'], $data['display_name'], $data['description']);
  }
  
  
  public static function group_delete($data)
  {
    return AxelradUserMgmt::delete_group($data['name']);
  }
}