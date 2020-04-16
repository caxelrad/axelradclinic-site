<?php

AxelradOs::load('axelrad-user-mgmt');

class UserRegisterAppModel extends MercuryRestModel
{
  
  public static function email_exists($data)
  {
    return AxelradUserMgmt::find_user_by_email($data['email']) > 0;
  }
  
  public static function user_fetch($data)
  {
    $user = AxelradUserMgmt::get_user($data['id']);
    $user['membership_count'] = AxelradUserMgmt::get_membership_count($data['id']);
    return $user;
  }
  
  
  public static function user_create($data)
  {
    //in this case... we do NOT assume the user doesn't exist (i.e. they could be registering for a different group / app, etc...
    //soo..... we see if they exist, if so create them, if not update them and add them to the group specified
   
   
    $no_welcome_email = $data['no_welcome_email'];
    $group_name = $data['group_name'];
    
    if ($group_name && !AxelradUserMgmt::group_exists($group_name))
    {
      throw new Exception('The group "'.$group_name.'" does not exist.');
    }
    
    $user_id = 0;
    $user = AxelradUserMgmt::find_user_by_email($data['email']);
    
    if ($user == null)
    {
      $user_id = AxelradUserMgmt::create_user($data['email'], $data['first_name'], $data['last_name'], $group_name);
      $user = self::user_fetch(['id' => $user_id]);
      _ax_debug('user retrieved: '.json_encode($user));
    }
    else
    {
      $user_id = $user['id'];
      $props = [];
      
      _ax_debug('user retrieved: '.json_encode($user));
      AxelradUserMgmt::add_group_member($group_name, $user_id);
        
      if ($data['first_name_sent'])
        $props['first_name'] = $data['first_name'];
      if ($data['last_name_sent'])
        $props['first_name'] = $data['last_name'];
      if ($data['phone_sent'])
        $props['phone'] = $data['phone'];
      
      if (count(array_keys($props)) > 0) //update the user...
      {
        _ax_debug('going to update user...');
        $user = AxelradUserMgmt::update_user($user_id, $props);
      }
      
      _ax_debug('user retrieved: '.json_encode($user));
    }
    
    if ($no_welcome_email)
    {
      $user['welcome_email_sent'] = false;
      return $user;
    }
    else
    {
      try
      {
        AxelradUserMgmt::send_welcome_email($user_id, $group_name);
        $user['welcome_email_sent'] = true;
      }
      catch (Exception $ex)
      {
        $user['welcome_email_sent'] = false;
        $user['welcome_email_error'] = $ex->getMessage();
      }
      
      return $user;
    }
  }
  
}