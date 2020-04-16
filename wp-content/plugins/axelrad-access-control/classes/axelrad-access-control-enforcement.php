<?php

class AxAccessCtrlEnforce
{
  
  static $_access_denied = false;
  
  static function is_public($post_id)
  {
    if (AxelradAccessControl::page_is_always_visible(get_post_field('post_name', $post_id)))
      return true;
    
    if (AxelradAccessControl::$always_require_login)
      return false;
    
    return AxelradAccessControl::get_access_type($post_id) == AxelradAccessControl::ACCESS_TYPE_PUBLIC;
  }
  
  
  public static function enforce_access()
  {
    
    _ax_debug('AxelradUserMgmtAccessCtrl::enforce_access();');
    
    _ax_debug('is_user_logged_in '.is_user_logged_in());
    _ax_debug('is_admin '.is_admin());
    _ax_debug('page_is_always_visible: '.AxelradAccessControl::page_is_always_visible());
    
    $post_url = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
    
    //always hide the admin bar if not in the admin dashboard...
    show_admin_bar(is_admin() || current_user_can('administrator') || current_user_can('editor'));
    //handle a couple of special cases....
    if (_ax_req('loggedout') == 'true')
    {
      wp_redirect(AxelradAccessControl::$login_url.'?from='.urlencode($post_url));
      die;
    }

    
    if ($_GET['action'] == 'rp' && $_GET['key'] && $_GET['login']
       && strpos($post_url, 'wp-login.php') !== false)
    {
      //we gonna redirect now to the reset password page.
      wp_redirect(AxelradAccessControl::$set_new_pwd_url.'?key='.urlencode($_GET['key']).'&login='.urlencode($_GET['login']));
      die;
    }
    
    //handle some always visible cases
    
    if (AxelradAccessControl::page_is_always_visible()) return;

    _ax_debug('checking the post...');

    $post_id = url_to_postid($post_url);

    _ax_debug('post id is: '.$post_id);

    if (!is_user_logged_in())
    {
      _ax_debug('user is not logged in...');
      if (!self::is_public($post_id))
        self::deny_access($post_url, $post_id);
      else
        return; //nothing left to check. :-)
    }
    else //LOGGED IN
    {
      
      if (current_user_can('administrator') || current_user_can('editor'))
        return;
      else
      {
        if (is_admin()) //if logged in but non-admin or non-editor & tries to get to wp-admin... NOPE!
        {
          wp_redirect('/');
        }
        else
        {
          if (!AxelradAccessControl::user_can_access_post(AxelradUserMgmt::current_id(), $post_id))
            self::deny_access($post_url, $post_id); //it's a protected asset, and we're not logged in. DENIED!
        }
      }
    }
  }
  
  static function deny_access($requested_url, $denied_post_id)
  {
    wp_redirect(AxelradAccessControl::get_effective_access_denied_url($denied_post_id, $requested_url));
    die;
  }
  
  
  public static function is_access_denied() { return self::$_access_denied || _ax_req('aum') == 'access_denied'; }
  
  public static $external_securable_category_id = 0;
  public static $internal_securable_category_id = 0;

  public static function get_securables_external($category_id = 0)
  {
    _ax_debug('AxelradUserMgmtAccessCtrl::get_securables_external('.$category_id.')');
    _ax_debug('self::$external_securable_category_id = '.self::$external_securable_category_id);
    if (self::$external_securable_category_id == 0)
      return [];

    if ($category_id == 0)
      $category_id = self::$external_securable_category_id;
    
    //just grab the subcategories of the main securable category...

    $categories = get_categories(['parent' => $category_id]);
    _ax_debug('there are '.count($categories).' categories');
    
    if (count($categories) > 0)
    {
      $cats = [];
      foreach ($categories as $c)
      {
        _ax_debug(json_encode($c));
        $cats[] = ['id' => $c->cat_ID, 'name' => $c->name, 'slug' => $c->slug, 'type' => 'category'];
      }

      return $cats;
    }
    else 
    {
      _ax_debug('no categories, looking for posts in category '.$category_id);
      $posts = get_posts(['category' => $category_id, 'post_type' => ['page', 'post'], 'post_status' => ['publish', 'draft']]);
      _ax_debug_json($posts);
      $result = [];
      foreach ($posts as $pst)
      {
        $result[] = ['id' => $pst->ID, 'title' => $pst->post_title, 'type' => $pst->post_type];
      }

      return $result;

    }
  }
}