<?php

AxBin::load('axelrad-data-access');

include 'axelrad-user-mgmt-models.php';
include 'axelrad-user-mgmt-rest.php';
include 'axelrad-user-mgmt-hooks.php';
include 'axelrad-user-mgmt-access-control.php';
include 'axelrad-user-mgmt-commands.php';
include 'axelrad-user-mgmt-admin-ui.php';
include 'axelrad-user-mgmt-forms.php';
include 'axelrad-user-mgmt-remote-admin.php';


class AxelradUserMgmt
{


  public static $default_group_name = 'all-users';
  public static $default_group_display_name = 'All Users';
  public static $default_group_description = 'All users are automatically added to this group.';
  
  
  public const EVENT_AUTH_REQUESTED = 'user_system_auth_requested';
  public const EVENT_PASSWORD_CHANGED = 'user_system_pwd_changed';
  public const EVENT_ACCESS_DENIED = 'user_system_access_denied';
  public const EVENT_USER_GROUP_ADDED = 'user_system_user_group_added';
  public const EVENT_USER_GROUP_REMOVED = 'user_system_user_group_removed';
  public const EVENT_USER_CREATED = 'user_system_user_created';
  public const EVENT_USER_UPDATED = 'user_system_user_updated';
  public const EVENT_USER_DELETED = 'user_system_user_deleted';
  public const EVENT_PRE_WELCOME_EMAIL_SEND = 'user_system_pre_welcome_email_send';
  public const EVENT_WELCOME_EMAIL_SENT = 'user_system_welcome_email_sent';
  
  public static function init_routes()
  {
    //AxelradUserMgmtRest::init();
  }
  
  protected static function __($string)
  {
    return str_replace("'", "''", $string);
  }
  
  protected static function _flatten($value)
  {
    return AxData::_flatten($value);
  }
  
  static $_cached_token = null;

  public static function get_user_token()
  {
    if (self::$_cached_token == null)
    {
      if (self::current_id() == 0)
        self::$_cached_token = '';
      else
      {
        $tokens = R::find('usertoken', 'user_id = ?', [get_current_user_id()]);
        //echo json_encode($tokens);
        if (count($tokens) == 0)
        {
          $token = R::dispense('usertoken');
          $token->user_id = get_current_user_id();
          $token->token_value = uniqid('u', true);
          R::store($token);
          self::$_cached_token = $token->token_value;
        }
        else
        {
          self::$_cached_token = self::_flatten($tokens)[0]->token_value;
        }
        //echo 'found token for '.self::current_id().' = '.self::$_cached_token;
      }
    }
    return self::$_cached_token;
  }  

  public static function set_user_token($token)
  {
    if (!AxelradUtil::is_rest()) return;
    self::$_cached_token = $token;
  }

  static $_user_tokens = [];
  public static function get_user_id_from_token($token)
  {
      if (!$token) return 0;

      if (self::$_user_tokens[$token] === null)
      {
        $tokens = R::find('usertoken', 'token_value = ?', [$token]);
        if (count($tokens) == 0)
          self::$_user_tokens[$token] = 0;
        else 
          self::$_user_tokens[$token] = self::_flatten($tokens)[0]->user_id;
      }
      
      return self::$_user_tokens[$token];
  }

  public static function is_logged_in()
  {
    return self::current_id() > 0;
  }
  
  public const USER_TOKEN_QUERY_PARAM = 'tkx';

  public static function current_id()
  {
    //echo 'hey '.self::USER_TOKEN_QUERY_PARAM.' = '.$_GET[self::USER_TOKEN_QUERY_PARAM];
    if (get_current_user_id() == 0)
    {
      $token = $_GET[self::USER_TOKEN_QUERY_PARAM];

      if ($token)
        return self::get_user_id_from_token($token);
      else
        return 0;
    }
    else 
      return get_current_user_id();
  }
  
  public static function exists($user_id = 0)
  {
    return get_userdata( $user_id == 0 ? self::current_id() : $user_id) !== false;
  }
  
  public static function current()
  {
    if (!is_user_logged_in()) return null;
    
    return self::get_user(self::current_id());
  }
  
  static $cached_users = [];
  
  public static function get_user($id_or_email)
  {
    _ax_debug('get_user('.$id_or_email.')');
    
    if (!is_numeric($id_or_email))
      $id = self::find_user_id_by_email($id_or_email);
    else
      $id = $id_or_email;
    
    if (self::$cached_users[$id] == null)
    {
      require_once(ABSPATH.'wp-admin/includes/user.php');
      self::$cached_users[$id] = self::_get_user_info(get_user_by('id', $id));
    }

    return self::$cached_users[$id];
  }
  
  public static function generate_login_as_url($user_id, $requested_url = '')
  {
    if ($requested_url == '')
      $requested_url = 'https://'.$_SERVER['HTTP_HOST'];
    
    if (!_ax_util_str_starts_with($requested_url, 'https://'))
      throw new Exception('Sorry, you cannot request a login-as key for a non-secure url or a url without the server name specified.');
    
    if (!_ax_util_str_starts_with($requested_url, 'https://'.$_SERVER['HTTP_HOST']))
        throw new Exception('Sorry you can\'t request a login-as key for another domain.');
        
    $requested_url = trailingslashit($requested_url);
    
    $the_hash = self::get_user_login_as_hash($user_id);
    $q = 'axuh='.urlencode($the_hash).'&axu='.(intval($user_id)+35938);
    
    if (strpos($requested_url, '?') === false)
      return $requested_url.'?'.$q;
    else 
      return $requested_url.'&'.$q;
  }
  
  static function get_user_login_as_hash($user_id)
  {
    //creates a random string, hashes it and stores it in the database and returns the hash to check against the current pwd in verify_user_login_as_hash($id, $hash) 
    $pwd = uniqid();
    $hash = password_hash($pwd, PASSWORD_DEFAULT);
    update_user_meta($user_id, 'tmp_hash_pwd', $pwd);
    update_user_meta($user_id, 'tmp_hash_pwd_exp', time() + 120); //expire it 2 minutes after it is requested...
    return $hash;
  }
  
  public static function verify_user_login_as_hash($user_id, $hash)
  {
    $pwd = get_user_meta($user_id, 'tmp_hash_pwd', true);
    if (!$pwd)
      return 'not_valid';
    else
    {
      $expires = get_user_meta($user_id, 'tmp_hash_pwd_exp', true);
      if (time() > intval($expires))
      {
        //it's expired...
        update_user_meta($user_id, 'tmp_hash_pwd', '');
        return 'expired';
      }
      
      $is_valid = password_verify ($pwd, $hash);
      if ($is_valid)
      {
        //clear the temp password and return true
        update_user_meta($user_id, 'tmp_hash_pwd', '');
        return 'valid';
      }
      return 'not_valid';
    }
  }
  
  public static function find_user_by_email($email)
  {
    require_once(ABSPATH.'wp-admin/includes/user.php');
    $user_id = username_exists($email);
    
    if ($user_id > 0) return self::get_user($user_id);
    
    return null;
  }
  
  public static function find_user_id_by_email($email)
  {
    require_once(ABSPATH.'wp-admin/includes/user.php');
    $user_id = username_exists($email);
    if ($user_id === false)
      return 0;
    
    return $user_id;
  }
  
  public static function get_total_user_count()
  {
    $result = count_users();
    return $result['total_users'];  
  }
  
  public static function get_users($page_size = 100, $page_num = 1, $name_like = '')
  {
    
    $args = [];
    $args['number'] = $page_size;
    $args['paged'] = $page_num;
    if ($name_like)
    {
      $search_string = esc_attr( trim( $name_like ) );
      
      //have to do 2 searches to also search on user_email login and such...
      $query1 = new WP_User_Query(
        [
          'search' => "*{$search_string}*",
          'search_columns' =>
          [
            'user_login',
            'user_nicename',
            'user_email',
          ]
        ] 
      );
      
      $users1 = $query1->get_results();
      
      $query2 = new WP_User_Query(
        [
          'meta_query' => 
          [
            'relation' => 'OR',
            [
              'key'     => 'user_email',
              'value'   => $search_string,
              'compare' => 'LIKE'
            ],
            [
              'key'     => 'phone',
              'value'   => $search_string,
              'compare' => 'LIKE'
            ],
            [
              'key'     => 'first_name',
              'value'   => $search_string,
              'compare' => 'LIKE'
            ],
            [
              'key'     => 'last_name',
              'value'   => $search_string,
              'compare' => 'LIKE'
            ]
          ]
        ]
      );
      
      $users2 = $query2->get_results();
      $all = array_merge($users1,$users2);
      $users = array_unique($all, SORT_REGULAR);
      
      foreach ($users as $user)
      {
        $user = get_userdata($user->ID);
      }
    }
    else
    {
      $users = get_users($args);
    }
    
    _ax_debug('AxelradUserMgmt::get_users found '.count($users));
    
    $result = [];
    foreach ($users as $user)
    {
      $result[] = self::_get_user_info($user);
    }
    return $result;
  }
  
  static function _get_user_info($wp_user)
  {
    $user = 
      [
        'login' => $wp_user->user_login, 
        'email' => $wp_user->user_email, 
        'first_name' => $wp_user->first_name,
        'last_name' => $wp_user->last_name,
        'phone' => get_user_meta($wp_user->ID, 'phone', true),
        'display_name' => $wp_user->first_name.($wp_user->last_name == '' ? '' : ' '.$wp_user->last_name),
        'id' => $wp_user->ID
      ];
    
    $user['fqn'] = $user['display_name'].' ('.$user['email'].')';
    return $user;
  }
  
  
  public static function role_names_fetch($search_string = '')
  {
    //this ensures that editors can't make changes that affect administrators
    
    $roles = new WP_roles();
    $role_names = $roles->get_names();
    
    $role_names = array_filter($role_names, 
      function($value)
      {
        return $value != 'Administrator';
      }
    );

    if ($search_string)
    {
      $result = [];
      foreach ($role_names as $name)
      {
        if (strpos($name, $search_string) !== false)
          $result[] = $name;
      }

      return $result;
    }
    else
    {
      return $role_names;
    }
  }

  public static function groups_fetch($search_string = '')
  {
    if ($search_string)
      return AxData::_flatten(R::getAssoc('select * from usergroup where name LIKE ? ORDER BY display_name LIMIT 25', ['%'.$search_string.'%']));
    else 
      return AxData::_flatten(R::getAssoc('select * from usergroup ORDER BY display_name LIMIT 25'));
  }
  
  public static function groups_fetch_by_id($ids)
  {
    return AxData::_flatten(R::loadAll('usergroup', $ids));
  }
  
  public static function get_member_count($group_id)
  {
    return R::count('membership', 'usergroup_id = ? ', [$group_id]);
  }
  
  public static function get_membership_count($user_id)
  {
    return R::count('membership', 'user_id = ? ', [$user_id]);
  }
  
  public static function get_members($group_id, $page_size = 100, $page_num = 1, $name_like = '')
  {
    $memberships = R::find('membership', 'usergroup_id = ? ', [$group_id]);

    $result = [];
    
    foreach ($memberships as $row)
    {
      $user = self::get_user($row->user_id);
      
      if ($name_like == '')
        $result[] = $user;
      else if (strpos($user['first_name'], $name_like) !== false || 
          strpos($user['last_name'], $name_like) !== false || 
          strpos($user['email'], $name_like) !== false)
        $result[] = $user;
          
    
    }
    
    return $result;
  }
  
  static $get_group_cache = [];
  
  public static function get_group($id)
  {
    return R::load('usergroup', $id);
  }

  public static function group_create($name, $display_name, $description)
  {
    if (R::count('usergroup', 'name = ?', [$name])> 0)
      throw new Exception('EXISTS');
    
    $g = R::dispense('usergroup');
    $g->name = $name;
    $g->display_name = $display_name;
    $g->description = $description;

    return R::store($g);
  }
  
  
  public static function group_update($id, $display_name, $description)
  {
    $g = R::load('usergroup', $id);
    $g->display_name = $display_name;
    $g->description = $description;

    R::store($g);
    return $id;
  }
  
  
  public static function get_group_meta($group_id, $key)
  {
    $meta = R::find('groupmeta', 'usergroup_id = ? AND key = ?', [$group_id, $key]);
    if (count($meta) == 1)
      return $meta[0]->value;
    
    return null;
  }
  
  public static function set_group_meta($group_id, $key, $value)
  {
    $meta = R::find('groupmeta', 'usergroup_id = ? AND key = ?', [$group_id, $key]);
    if (count($meta) == 0)
    {
      $meta = R::dispense('groupmeta');
      $meta->usergroup_id = $group_id;
      $meta->key = $key;
    }

    $meta->value = $value;
    R::store($meta);
  }
  
  static $is_member_cache = [];
  
  public static function user_is_member($group_id, $user_id)
  {
    return R::count('membership', 'usergroup_id = ? AND user_id = ?', [$group_id, $user_id]) > 0;
  }
  
  public static function get_user_memberships($user_id)
  {
    return R::findAll( 'membership', 'user_id = ?', [$user_id] );
  }

  public static function get_user_groups($user_id)
  {
    return self::_flatten(
      R::getAssoc("select * from usergroup where 
       id in (select usergroup_id from membership where user_id = ".$user_id.")"));
  }

  
  public static function group_exists($group_id)
  {
    return R::count('usergroup', 'id = ?', [$group_id]) > 0;
  }

  public static function group_name_exists($name)
  {
    return R::count('usergroup', 'name = ?', [$name]) > 0;
  }
  
  public static function get_group_id_from_name($name)
  {
    $groups = self::_flatten(R::find('usergroup', 'name = ?', [$name]));
    
    if (count($groups) == 1)
      return $groups[0]->id;
    
    return 0;
  }
  
  public static function delete_group($id)
  {
     R::trash(R::load('usergroup', $id));
  }
  
  public static function user_exists($id)
  {
    if ($id < 1) return false;
    return self::exists($id);
  }
  
  public static function membership_exists($group_id, $user_id)
  {
    return self::user_is_member($group_id, $user_id);
  }
  
  public static function add_group_member($group_id, $user_id)
  {
    if (!self::group_exists($group_id))
      throw new Exception('NO_GROUP');
    
    if (!self::user_exists($user_id))
      throw new Exception('NO_USER');

    if (self::user_is_member($group_id, $user_id))
      return; //already a member!

    $m = R::dispense('membership');
    $m->usergroup_id = $group_id;
    $m->user_id = $user_id;
    R::store($m);
  }
  
  /*
  clears all groups and resets them
  */
  public static function set_user_groups($user_id, $group_ids)
  {
    $membs = self::_flatten(R::find('membership', 'user_id = ?', [$user_id]));
    if (count($membs) > 0)
    {
      R::trash($membs[0]);
    }

    foreach ($group_ids as $group_id)
    {
      self::add_group_member($group_id, $user_id);
    }

  }

  public static function rmv_group_member($group_id, $user_id)
  {
    

    if (!self::membership_exists($group_id, $user_id))
      return false;
    
    
    $membs = self::_flatten(R::find('membership', 'usergroup_id = ? AND user_id = ?', [$group_id, $user_id]));
    if (count($membs) > 0)
    {
      R::trash($membs[0]);
    }
    return true;
  }
  
  public static function generate_password( $length = 6 ) 
  {
    //do lower case b/c ppl get confused easily with case-sensitive passwords.
    return strtolower(wp_generate_password( $length, $include_standard_special_chars = false ));
  }
  
  public static function create_user($email, $first_name, $last_name, $built_in_group_id,  $initial_group_name = '')
  {
    require_once(ABSPATH.'wp-admin/includes/user.php');
    //store the user if doesn't exist...
    _ax_debug('entering create_user');
    
    $user_id = self::find_user_id_by_email($email);
    if ($user_id !== 0)
      throw new Exception('The email address "'.$email.'" is already registered.');
    
    $pwd = self::generate_password();
    $result = wp_create_user($email, $pwd, $email);

    if (is_wp_error( $result ) )
    {
      _ax_debug($result->get_error_message());
      throw new Exception('Error during user create: '.$result->get_error_message());
    }
    else
    {
      $user_id = $result;
      wp_update_user( [
        'ID' => $user_id, 
        'display_name' => $first_name.' '.$last_name, 
        'first_name' => $first_name, 
        'last_name' => $last_name
        ] );
      _ax_debug('user created with id: '.$user_id. ' and login '.$email);
    }
    
    _ax_debug('$built_in_group_id = '.$built_in_group_id);

    self::add_group_member($built_in_group_id, $user_id);
    
    if ($initial_group_name != '')
    {
      $group_id = self::get_group_id_from_name($initial_group_name);
      self::add_group_member($group_id, $user_id);
    }
    return $user_id;
  }
  
  static function get_cached_built_in_group_id($group_name)
  {
      if (!self::group_is_built_in($group_name))
        return null;

      $id = get_option($group_name.'-id');
      if (!$id)
      {
        self::ensure_built_in_groups();
        return get_option($group_name.'-id');
      }
      else
        return $id;
  }

  static function get_default_external_group_id()
  {
    return self::get_cached_built_in_group_id('all-users');
  }

  static function get_default_internal_group_id()
  {
    return self::get_cached_built_in_group_id('employees');
  }
  

  static function group_is_built_in($group_name)
  {
    return $group_name == 'all-users' || $group_name == 'sys-admins' || $group_name == 'employees';
  }

  static function set_cached_built_in_group_id($group_name, $id)
  {
      update_option($group_name.'-id', $id);
  }

  static function ensure_built_in_groups()
  {
    if (get_option('all-users-id'))
      return;

    $groups = [];

    $groups[] = $g = R::dispense('usergroup'); 
    $g->name = 'all-users';
    $g->display_name = 'All Users';
    $g->description = 'All users are in this group by default.';
    $g->built_in = 1;
    $g->is_default = 1;
    
    foreach ($groups as $group)
    {
      if (!self::group_name_exists($group->name))
      {
        $id = R::store($group);
        self::set_cached_built_in_group_id($group->name, $id);
      }
      else 
      {
        $id = self::get_group_id_from_name($group->name);
        self::set_cached_built_in_group_id($group->name, $id);
      }
    }
  }
  
  static function user_props_for_write($props)
  {
    $known = [];
    $meta = [];
    
    if ($props['id'] || $props['ID'])
      $known['ID'] = $props['id'] ? $props['id'] : $props['ID'];
    if ($props['first_name'])
      $known['first_name'] = $props['first_name'];
    if ($props['last_name'])
      $known['last_name'] = $props['last_name'];
    if ($props['email'])
      $known['user_email'] = $props['email'];
    if ($props['description'])
      $known['description'] = $props['description'];
    
    if ($props['phone'])
      $meta['phone'] = $props['phone'];
    
    return ['known' => $known, 'meta' => $meta];
  }
  
  public static function update_user($user_id, $props)
  {
    require_once(ABSPATH.'wp-admin/includes/user.php');
    $props['ID'] = $user_id;
    
    $user_props = self::user_props_for_write($props);
    
    wp_update_user($user_props['known']);
    
    foreach ($user_props['meta'] as $key => $value)
    {
      update_user_meta($user_id, $key, $value);
    }
    
    //clear out the cache so the new user info is pulled back out of the db
    self::$cached_users[$user_id] = null; 
    //self::fire(self::EVENT_USER_UPDATED, ['user_id' => $user_id]);
    
    return self::get_user($user_id);
  }
  
  public static function delete_user($id)
  {
    
    require_once(ABSPATH.'wp-admin/includes/user.php');
    wp_delete_user($id);
    
    R::trashAll(R::findAll('membership', 'user_id = ?', [$id]));
  }
  
  public static function login_count($user_id)
  {
    return get_user_meta($user_id, 'login_count', true);   
  }
  
  public static function user_logged_in($user_id)
  {
    $count = self::login_count($user_id);
    
    if (!is_numeric($count))
      $count = 0;
    else 
      $count = intval($count);
    
    if ($count == 0)
      update_user_meta($user_id, 'login_count', 1);   
    else 
      update_user_meta($user_id, 'login_count', $count + 1); 
  }
  
  
}


AxelradUserMgmt::ensure_built_in_groups();