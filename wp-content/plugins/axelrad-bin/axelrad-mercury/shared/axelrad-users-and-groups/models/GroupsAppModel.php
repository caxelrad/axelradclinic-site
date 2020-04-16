<?php

AxelradOs::load('axelrad-user-mgmt');

class GroupsAppModel
{
  public static function groups_fetch($data)
  {
    $search = '';
    if (is_array($data))
      $search = $data['search'];
    
    return AxelradUserMgmt::get_groups($search);
  }
  
  public static function group_fetch($data)
  {
    $group = AxelradUserMgmt::get_group($data['name']);
    $group['member_count'] = AxelradUserMgmt::get_member_count($data['name']);
    return $group;
  }
  
  
  public static function group_create($data)
  {
    return AxelradUserMgmt::create_group($data['name'], $data['display_name'], $data['description'], $data['tag']);
  }
  
  public static function group_update($data)
  {
    return AxelradUserMgmt::update_group($data['name'], $data['display_name'], $data['description'], $data['tag']);
  }
  
  
  public static function group_delete($data)
  {
    return AxelradUserMgmt::delete_group($data['name']);
  }
}