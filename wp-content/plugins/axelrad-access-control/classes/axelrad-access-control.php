<?php
include 'axelrad-access-control-hooks.php';
include 'axelrad-access-control-ui.php';
include 'axelrad-access-control-forms.php';
include 'axelrad-access-control-enforcement.php';
include 'axelrad-access-control-admin.php';
include 'axelrad-access-control-data.php';

// the "front-end" code that uses the backend AxelradUserMgmt to provide UI to set permissions on stuff 

class AxelradAccessControl
{
    
  public const ACCESS_TYPE_PUBLIC = '';
  public const ACCESS_TYPE_ANY_LOGGED_IN = 'any';
  public const ACCESS_TYPE_GROUP = 'group';
  public const ACCESS_TYPE_ROLE = 'role';
  public const ACCESS_TYPE_ADMIN_ONLY = 'admin';
  
  public static $enabled = true;

  public static $login_url = '/login/';
  public static $logout_url = '/login/';
  public static $reset_pwd_url = '/reset-password/';
  public static $set_new_pwd_url = '/set-new-password/';

  public static $join_url = '/join/';
  public static $join_success_url = '/join-success/';
  public static $show_join_link_on_login = true;
  
  
  public static $no_auth_url = '/login/';
  public static $access_denied_msg = "Uh oh.... I'm not allowed to let you see that page unless you're logged in. 
          <p/>If you already have an account, log in below.";
  
  public static $terms_url = '/terms-of-use/';
  public static $privacy_policy_url = '/privacy-policy/';
  
  public static $remote_admin_url = '/a/';
  public static $remote_admin_key = 'x8fkjmv-sdkjfow-9fkbknjk';
  
  public static $email_from_name = 'Chris Axelrad';
  public static $email_from_addr = 'chris@chrisaxelrad.com';
  public static $email_support_address = 'support@chrisaxelrad.com';
  
  public static $login_form_intro = 'Welcome back! Simply log in below to access the awesomeness.';

  //retrieve this via the function below, not directly
  public static $welcome_email_cc = 'support@chrisaxelrad.com';
  


  public static $securables_category_slug = 'securable';

  public static $always_require_login = false;

  
  public static function page_is_always_visible($slug = '')
  {
    if (!$slug)
    {
      if (self::is_remote_admin_page() || 
      self::is_login_page() ||
      self::is_password_reset_page() || 
      self::is_join_page())
        return true;

      $slug = _ax_util_get_slug();
    }

    return do_action('ax_access_control_is_always_visible', $slug);
  }

  static function get_principal_meta_key($principal)
  {
    return 'ax-sec-p-'.$principal;
  }

  static function get_principal_from_meta_key($key)
  {
    return str_replace('ax-sec-p-', '', $key);
  }

  public static function get_access_types()
  {
    return 
    [
        self::ACCESS_TYPE_PUBLIC, 
        self::ACCESS_TYPE_ANY_LOGGED_IN, 
        self::ACCESS_TYPE_GROUP, 
        self::ACCESS_TYPE_ROLE, 
        self::ACCESS_TYPE_ADMIN_ONLY
    ] ;
  }
  
  public static function get_post_security_type_display_name($sec_type)
  {
    if ($sec_type == self::ACCESS_TYPE_ANY_LOGGED_IN) return 'Any logged in user';

    if ($sec_type == self::ACCESS_TYPE_GROUP) return 'The selected group(s)';

    if ($sec_type == self::ACCESS_TYPE_ROLE) return 'The selecdted role(s)';
    
    if ($sec_type == self::ACCESS_TYPE_ADMIN_ONLY) return 'Only wordpress administrators';
    
    return 'Any visitor to the site';
  }

  static $_login_logo = '';

  public static function login_logo_path($path = null)
  {
      if ($path)
          self::$_login_logo = $path;
      else
          return self::$_login_logo;
  }

  static $_login_form_intro = '';

  public static function login_form_intro($msg = null)
  {
    if ($msg)
      self::$_login_form_intro = $msg;
    else 
        return self::$_login_form_intro;
  }
  
  
  public static function get_securables($filter = null)
  {
    $cat_id = get_category_by_slug(self::$securables_category_slug)->term_id; 
    return _ax_util_get_posts_in_category($cat_id, 'page');
  }

  public static function set_access($access_type, $post_id, $principals = [])
  {
    AxAccessCtrlData::delete_access_entry($post_id);

    
    if ($access_type != self::ACCESS_TYPE_PUBLIC)
    {
      $parent_id = wp_get_post_parent_id($post_id);  
      _ax_debug('setting access to post '.$post_id.' with parent '.$parent_id.': '.$access_type);
      $entry_id = AxAccessCtrlData::save_access_entry($access_type, $post_id, $parent_id === false ? 0 : $parent_id);

      if (count($principals) > 0)
      {
        foreach ($principals as $principal)
        {
          AxAccessCtrlData::add_entry_principal($entry_id, $principal, 
            $access_type == self::ACCESS_TYPE_ROLE ? 'role' : 'group');
        }
      }

      AxAccessCtrlData::sync_to_parent($post_id); //sync all the children to the new access
    }
  }

  public static function post_saved($post_id)
  {
    $parent_id = wp_get_post_parent_id($post_id);
    if ($parent_id)
    {
      //if the parent has access specified but this does not, then we need to copy it over.
      $entry = AxAccessCtrlData::get_post_access_entry($parent_id);
      if ($entry != null)
      {
        if (!AxAccessCtrlData::post_has_access_entry($post_id)
          || $parent_id != $entry->parent_id) //the post doesn't have security OR the parent changed
        {
          AxAccessCtrlData::sync_to_parent($parent_id);
        }
      }   
    }
  }

  public static function post_deleted($post_id)
  {
    AxAccessCtrlData::delete_access_entry($post_id);
  }


  public static function get_raw_access_data($post_id)
  {
    return ['ax-access' => self::get_access_type($post_id), 'ax-access-principals' => self::get_access_principals($post_id)];
  }

  public static function get_access_type($post_id)
  {
    return self::get_post_access_type($post_id);
  }

  public static function get_access_principals($post_id)
  {
    $ps = AxAccessCtrlData::get_post_principals($post_id);
    $result = [];
    foreach ($ps as $p)
    {
      if ($p['principal_type'] == 'role')
        $result[] = $p->principal;
      else 
        $result[] = AxelradUserMgmt::get_group($p->principal);
    }

    return $result;
  }
  
  public static function get_principal_posts($principal)
  {
    $entries = AxAccessCtrlData::get_principal_access_entries($principal);

    if (count($entries) == 0)
      return [];
    else 
    {
      $p = [];
      foreach ($entries as $entry)
      {
        $p[] = get_post($entry['post_id']);
      }
      
      return $p;
    }
  }
  
  static $_post_access_type_cache = [];

  static function get_post_access_type($post_id)
  {
    if (self::$_post_access_type_cache[$post_id] == null)
    {
      $entry = AxAccessCtrlData::get_post_access_entry($post_id);
      if ($entry == null)
        self::$_post_access_type_cache[$post_id] = self::ACCESS_TYPE_PUBLIC;
      else 
        self::$_post_access_type_cache[$post_id] = $entry->access_type;
    }

    return self::$_post_access_type_cache[$post_id];
    
    
  }

  static $is_protected_post_cache = [];
  
  public static function is_protected_post($post_id)
  {
    return self::get_post_access_type($post_id) != self::ACCESS_TYPE_PUBLIC;
  }
  
  static $_post_principals_cache = null;

  static function get_post_principals($post_id)
  {
    if (self::$_post_principals_cache == null)
    {
      self::$_post_principals_cache = AxAccessCtrlData::get_post_principals($post_id);;
    }
    return self::$_post_principals_cache;
  }

  public static function group_can_access_post($group_id, $post_id)
  {
    $access_type = self::get_post_access_type($post_id);

    if ($access_type == self::ACCESS_TYPE_PUBLIC || $access_type == self::ACCESS_TYPE_ANY_LOGGED_IN)
      return true;
    else 
    {
      $principals = self::get_post_principals($post_id);
      return array_search($group_id, $principals) !== false;
    }

  }

  static $user_access_cache = [];
  
  public static function user_can_access_post($user_id, $post_id)
  {
    
    $post_key = 'post-'.$post_id.'-user-'.$user_id;

    if (self::$user_access_cache[$post_key] == null)
    {
      self::$user_access_cache[$post_key] = false;

      $access_type = self::get_post_access_type($post_id);

      if ($access_type == self::ACCESS_TYPE_PUBLIC ||
        ($access_type == self::ACCESS_TYPE_ANY_LOGGED_IN && $user_id != 0) || 
        ($access_type == self::ACCESS_TYPE_ADMIN_ONLY && current_user_can('administrator')))
      {
        self::$user_access_cache[$post_key] = true;
      }
      else if ($access_type == self::ACCESS_TYPE_GROUP)
      { 
        $memberships = AxelradUserMgmt::get_user_memberships($user_id);
        
        foreach ($memberships as $membership)
        {
          if (self::group_can_access_post($membership->usergroup_id, $post_id))
          {
            self::$user_access_cache[$post_key] = true;
            break;
          }
        }
      }
    }
    
    return self::$user_access_cache[$post_key];
  }

  public static function get_user_home_url()
  {
    try
    {
      //TODO: update this to let different groups have different home pages...      
      return '/';
    }
    catch (Exception $ex)
    {
      return '/'; //worst case just send them to the home page
    }
    
  }

  public static function format_login_url($params = '')
  {
    if ($params != '')
    {
      if (strpos($params, '?') != 0)
        $params = '?'.$params;
    }
    
    $login_url = self::$login_url;
    if (strrpos($login_url, '/') < strlen($login_url) - 1)
      return $login_url .'/'.$params;
    else 
      return $login_url.$params;
  }

  public static function get_full_login_url()
  {
    return AxelradUtil::get_full_url(self::$login_url);
  }
  
  public static function get_reset_pwd_url()
  {
    return self::$reset_pwd_url; //WP_SITEURL.'/wp-login.php?action=lostpassword';
    
  }
  
  public static function is_join_page()
  {
    return self::$join_url != '' && (_ax_util_current_url_has_slug(self::$join_url)
      || _ax_util_current_url_has_slug(self::$join_success_url));
  }
  
  public static function is_password_reset_page()
  {
    return _ax_util_current_url_has_slug(self::$reset_pwd_url);
  }
  
  public static function is_administrator()
  {
    return is_user_logged_in() && current_user_can('administrator');
  }
  
  public static function is_remote_admin_page()
  {
    return _ax_util_current_url_has_slug(self::$remote_admin_url);
  }
  
  public static function is_login_page()
  {
    return _ax_util_current_url_has_slug(self::$login_url)
      || _ax_util_current_url_has_slug('/wp-login.php');
  }

  public static function get_post_access_denied_url($post_id)
  {
    $entry = AxAccessCtrlData::get_post_access_entry($post_id);
    if ($entry)
      return $entry->access_denied_url;
    else 
      return '';
  }

  public static function set_post_access_denied_url($post_id, $url)
  {
    $entry = AxAccessCtrlData::get_post_access_entry($post_id);
    if ($entry)
    {
      $entry->access_denied_url = $url;
      R::store($entry);
    }
  }

  public static function get_effective_access_denied_url($denied_post_id, $requested_url)
  {
    $url = self::get_post_access_denied_url($denied_post_id);
    if (!$url)
      $url = self::$no_auth_url;

    $query = 'msg=access-denied&pid='.$denied_post_id.'&redirect_to='.rawurlencode($requested_url);

    if (strpos($url, '?') === false)
      return $url.'?'.$query;
    else
      return $url.'&'.$query;
  }
  
  public static function get_effective_access_denied_message($post_id)
  {
    $msg = self::get_post_access_denied_message($post_id);
    if (!$msg)
    {
      return self::$access_denied_msg;
    }
    else 
      return $msg;
  }

  public static function get_post_access_denied_message($post_id)
  {
    $entry = AxAccessCtrlData::get_post_access_entry($post_id);
    if ($entry)
      return $entry->access_denied_msg;
    else 
      return '';
  }

  public static function set_post_access_denied_message($post_id, $message)
  {
    $entry = AxAccessCtrlData::get_post_access_entry($post_id);
    if ($entry)
    {
      $entry->access_denied_msg = $message;
      R::store($entry);
    }
  }

  public static function get_effective_register_access_url($post_id)
  {
    $url = self::get_post_register_access_url($post_id);
    if ($url)
      return $url;
    else 
      return self::$join_url;
  }

  public static function get_post_register_access_url($post_id)
  {
    $entry = AxAccessCtrlData::get_post_access_entry($post_id);
    if ($entry)
      return $entry->registration_url;
    else 
      return '';
  }

  public static function set_post_register_access_url($post_id, $url)
  {
    $entry = AxAccessCtrlData::get_post_access_entry($post_id);
    if ($entry)
    {
      $entry->registration_url = $url;
      R::store($entry);
    }
  }
  
  public static function send_welcome_email($user_id_or_email, $props = [])
  {
    $user = AxelradUserMgmt::get_user($user_id_or_email);
    $group_name = $props['group_name'];
    $cc_email = $props['cc'];
    
    $body_content = $props['body_content'];
    $footer_content = $props['footer_content'];
    
    if (!$body_content)
      $body_content = 'Welcome to %SITE_NAME%.';
    
    if (!$footer_content)
      $footer_content = 'See you on the inside,<br/>'.PHP_EOL.
                        'The '.AxelradUtil::site_name().' team';
    
    if ($user == null)
      throw new Exception('Cannot send welcome email. User with that email does not exist.');
      
    $user_id = $user['id'];
    $login_url = self::get_password_reset_url($user_id);
    
    $body = '%FIRST_NAME%,'.PHP_EOL.
    '<p/>'.PHP_EOL.
    $body_content.PHP_EOL.
    '<p/>'.PHP_EOL.
    'Here\'s the details for you to log in:'.PHP_EOL.
    '<p/>'.PHP_EOL.
    'Your login is: %USER_LOGIN%<br/>'.PHP_EOL.
    '<p/>'.PHP_EOL.
    'To log in and set your passsword, simply click this link: <a href="'.$login_url.'">'.$login_url.'</a>'.PHP_EOL.
    '<p/>'.PHP_EOL.
    $footer_content;
    
    
    $subject = $user['first_name']. ', Welcome To '.AxelradUtil::site_name();
    
    $body = str_replace('%FIRST_NAME%', $user['first_name'], $body);
    $body = str_replace('%SITE_NAME%', AxelradUtil::site_name(), $body);
    $body = str_replace('%USER_LOGIN%', $user['login'], $body);
    
    $msg = new AxelradEmailMsg();
    $msg->to($user['email'], $user['first_name']);
    
    if (!$cc_email)
      $cc_email = self::welcome_email_cc();
    
    if ($cc_email)
      $msg->add_bcc($cc_email);
    
    $msg->subject = $subject;
    $msg->body = $body;
    AxelradMessaging::send_email($msg);
    
    /* self::fire(self::EVENT_WELCOME_EMAIL_SENT, 
      [
        'subject' => $subject, 
        'body' => $body, 
        'to' => $user['email'],
        'user_id' => $user_id,
        'group_name' => $group_name
      ]); */
  }
  
  

  
  public static function set_new_pwd($email, $key, $new_pwd)
  {
    $result = self::validate_pwd_reset_key($email, $key);
    if ($result == 'valid-key')
    {
      require_once(ABSPATH.'wp-admin/includes/user.php');
      $user_id = AxelradUserMgmt::find_user_id_by_email($email);
      wp_set_password($new_pwd, $user_id);
      update_user_meta($user_id, 'pwd_reset_key', '');
      update_user_meta($user_id, 'pwd_reset_expires', 0);
      return true;
    }
    
    return false;
  }
  
  public static function get_password_reset_url($user_id)
  {
    //set a meta key and an expiration...
    require_once(ABSPATH.'wp-admin/includes/user.php');
    
    _ax_debug('get_password_reset_url');

    $key = uniqid().'-'.uniqid();
    update_user_meta($user_id, 'pwd_reset_key', $key);
    update_user_meta($user_id, 'pwd_reset_expires', (time()+(60*24)));
    
    _ax_debug('getting user data');
    
    $user_data = AxelradUserMgmt::get_user($user_id);
    
    $url = self::$set_new_pwd_url;
    if (!_ax_util_str_ends_with($url, '/'))
      $url.='/';
    
    if (!_ax_util_str_starts_with($url, 'http'))
    {
      $slash = !_ax_util_str_starts_with($url, '/') ? '/' : '';
      $url = 'https://'.$_SERVER['SERVER_NAME'].$slash.$url;
    }
    _ax_debug('url retrieved');
    
    return $url.'?action=rp&key='.$key.'&login='.urlencode($user_data['login']).'&t='.time(); 
  }
  
  public static function validate_pwd_reset_key($email, $key)
  {
    $user_id = AxelradUserMgmt::find_user_id_by_email($email);
    if (!$user_id)
      return 'no-user';
    else
    {
      $stored_key = get_user_meta($user_id, 'pwd_reset_key', true);
      if (!$stored_key)
        return 'no-key';
      else
      {
        $expire_time = get_user_meta($user_id, 'pwd_reset_expires', true);
        if (!$expire_time)
          return 'no-time';
        else
        {
          if ($key !== $stored_key)
            return 'invalid-key';
          else if (time() > intval($expire_time))
            return 'expired-key';
          else
            return 'valid-key';
        } 
      }
    }
  }
  
  public static function set_temp_pwd($user_id)
  {
    wp_set_password(uniqid(), $user_id); 
  }
  
  
  public static function welcome_email_cc($val = null)
  {
    return self::$welcome_email_cc;
    
    // if ($val)
    //   AxSettings::put('users', 'welcome-email-cc', $val);
    // else 
    //   return AxSettings::get('users', 'welcome-email-cc');
  }
  

  public static function initiate_pwd_reset($email)
  {
    //find the user by the email...
    $id = AxelradUserMgmt::find_user_id_by_email($email);
    
    if ($id)
    {
      $url = self::get_password_reset_url($id);
      
      //send the email for resetting the password...
      $body = 'Hello,'.PHP_EOL.
      '<p/>'.PHP_EOL.
      'A password reset request was initiated for '.AxelradUtil::site_name().PHP_EOL.
      'for this email address ('.$email.')'.PHP_EOL.
      '<p/>'.PHP_EOL.
      '<a href="'.$url.'">Click here now</a> to complete the password reset process.'.PHP_EOL.
      '<p/>'.PHP_EOL.
      'If the above link does not work, you can copy and paste the following address into your browser\'s address bar:'.PHP_EOL.
      '<p/>'.PHP_EOL.
      $url.PHP_EOL.
      '<p/>'.PHP_EOL.
      'See you on the inside,<br/>'.PHP_EOL.
      self::$email_from_name.'<br/>'.PHP_EOL.
      self::$email_from_addr;
      
      _ax_debug('pwd reset email configured.');
      _ax_util_send_mail($email, 'Password reset request initiated', $body, self::welcome_email_cc());
      _ax_debug('pwd reset email sent.');
    }
  }
  
}