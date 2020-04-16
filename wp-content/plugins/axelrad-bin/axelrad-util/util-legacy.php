<?php

/*
Plugin Name: Axelrad Clinic Utilities
Plugin URI:
Version: 1.3
Author: Chris Axelrad
Description: Stuff needed by the other plugins. it's important. for reals.
*/

$_utility_settings_file = '';

$_site_logo_path = '';

function _ax_util_set_site_logo_path($path)
{
  global $_site_logo_path;
  $_site_logo_path = $path;
}

function _ax_util_has_site_logo_path() 
{
  global $_site_logo_path;
  return $_site_logo_path != '';
}

function _ax_util_get_site_logo_path($width = '250')
{
  
  if (_ax_util_has_site_logo_path())
  {
    global $_site_logo_path;
    return '<img src="'.$_site_logo_path.'" width="'.$width.'" />';
  }
  else
    return '<h2>'.get_bloginfo( 'name' ).'</h2>';
}


function _ax_util_get_settings_json()
{
  global $_utility_settings_file;
	return _ax_util_get_json($_utility_settings_file);
}

function _ax_util_get_plugin_root_path()
{
	return str_replace('/common' , '/', __DIR__);
}

function _ax_util_get_json($filePath)
{
	_ax_debug('_ax_util_get_json('.$filePath.')');
	return json_decode(_ax_util_get_file_contents($filePath), true); // decode the JSON into an associative array
}

function _ax_util_str_contains_one_of($string, $values_to_check)
{
  $values = str_split($values_to_check);

  foreach ($values as $value)
  {
    if (strpos($string, $value) !== false)
      return true; //the string contains something in the other string
  }
  return false;
}

function _ax_util_str_contains_none_of($string, $values_to_check)
{
  $values = str_split($values_to_check);

  foreach ($values as $value)
  {
    if (strpos($string, $value) !== false)
      return false; //the string contains something in the other string
  }
  return true;
}

function _ax_util_str_contains_only($string, $values_to_check)
{
  $string_values = str_split($string);
  
  foreach ($string_values as $value)
  {
    //_ax_debug('checking '.$values_to_check.' for '.$value);
    if (strpos($values_to_check, $value) === false)
      return false; //the string is missing something in the other string
  }
  return true;
}

function _ax_util_str_starts_with($haystack, $needle)
{
     $length = strlen($needle);
     return (substr($haystack, 0, $length) === $needle);
}

function _ax_util_str_ends_with($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}

function _ax_util_join_path($part1, $part2, $part3 = null, $part4 = null)
{
  $parts = array($part1, $part2, $part3, $part4);
  
  $path = '';
  foreach ($parts as $part)
  {
    if ($part)
    {
      if ($path != '' && !_ax_util_str_ends_with($path, '/'))
        $path = $path.'/';
      
      $path .= $part;
    }
  }
  
  return $path;
}

function _ax_util_get_file_contents($filePath)
{
	return file_get_contents($filePath);
}

function _ax_util_set_file_contents($filePath, $contents)
{
	return file_put_contents($filePath, $contents);
}

function _ax_util_send_mail($to, $subject, $body, $bcc = '')
{
	_ax_debug('sending '.$subject.' with message "'.$body.'" to '.$to);
	
  $headers = array('Content-Type: text/html; charset=UTF-8');
  
  if ($bcc != '')
    array_push($headers, 'Bcc: '.$bcc);
  
	wp_mail( $to, $subject, $body, $headers);
}

function _ax_util_send_sms($phone, $msg)
{
  $twilio_fn = 'twl_send_sms';
  if (function_exists($twilio_fn))
  {
    $args = array( 
      'number_to' => '+1'._ax_util_prep_phone_value($phone),
      'message' => $msg
    ); 
    $twilio_fn( $args );
  }
  else 
  {
    $email = _ax_util_get_sms_email($phone);
    //echo '<br>'.$email;
	  _ax_util_send_mail( $email, '', $msg);
  }
}


function _ax_util_get_sms_email($phone)
{
	$phone = _ax_util_prep_phone_value($phone);
  echo '<br/>will validate '.$phone;
  if (_ax_util_is_valid_phone($phone))
  {
    if (!_ax_util_str_starts_with($phone, '1')) $phone = '1'.$phone;
    //echo '<br/>will send to '.$phone;
	  return $phone.'@textmagic.com';
  }
  else
    throw new Exception('not a valid phone number.');
}

//removes leading 1 and returns just the 10 digits with area-code
function _ax_util_prep_phone_value($phone)
{
  if (_ax_util_str_starts_with($phone, '+1'))
    $phone = substr($phone, 2);
  else if (_ax_util_str_starts_with($phone, '1'))
    $phone = substr($phone, 1);
  
  return str_replace(' ', '', str_replace(')', '', str_replace('(', '', str_replace('.', '', str_replace('-', '', $phone)))));
}

function _ax_util_get_reset_pwd_link($user)
{
	return network_site_url('wp-login.php?action=rp&key='.get_password_reset_key( $user ).'&login='.rawurlencode($user->user_login));
}


function _ax_util_get_category_name($post)
{
  if (is_object($post))
    $id = $post->ID;
  else 
    $id = $post; 
  
  $cats = wp_get_post_categories( $id, array( 'fields' => 'all' ) );
  
  if (count($cats) == 1)
    return get_category($cats[0])->name;
  else 
    return 'Stuff';
}

function _ax_util_get_categories($parent_slug, $exclude_slug = '')
{
  
  $cat_id = get_category_by_slug($parent_slug)->term_id;
  if ($exclude_slug != '')
    $ex_id =  get_category_by_slug($exclude_slug)->term_id;
  else 
    $ex_id = 0;
  
  return get_terms( array(
    'taxonomy' => 'category',
    'hide_empty' => true,
    'parent' => $cat_id,
    'exclude' => $ex_id
    ) );
}

function _ax_util_get_root_category($post)
{
  $cats = wp_get_post_categories( $post->ID, array( 'fields' => 'all' ) );
  
  if (count($cats) == 1)
  {
    $parents = get_category_parents($cats[0]);
    if (strpos($parents, '/') !== false)
    {
      return get_category(get_cat_ID(substr($parents, 0, strpos($parents, '/'))));
    }
  }
  
  return null;
}

function _ax_util_get_posts_in_category($cat, $type = 'post')
{
  global $wpdb;
  
  $cat_id = is_numeric($cat) ? $cat : get_cat_ID($cat);
  
  $querystr = "SELECT DISTINCT wposts.* 
  FROM $wpdb->posts wposts
	LEFT JOIN $wpdb->postmeta ON(wposts.ID = $wpdb->postmeta.post_id)
	LEFT JOIN $wpdb->term_relationships ON(wposts.ID = $wpdb->term_relationships.object_id)
	LEFT JOIN $wpdb->term_taxonomy ON($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id)
	LEFT JOIN $wpdb->terms ON($wpdb->term_taxonomy.term_id = $wpdb->terms.term_id)
	WHERE $wpdb->terms.term_id = '$cat_id'
	AND $wpdb->term_taxonomy.taxonomy = 'category'
	AND wposts.post_status = 'publish'
	AND wposts.post_type = '$type'";
	//AND $wpdb->postmeta.meta_key = 'order'
	//ORDER BY $wpdb->postmeta.meta_value ASC";
  
  _ax_debug($querystr);
  
  return $wpdb->get_results($querystr, OBJECT);
}


function _ax_util_get_posts_in_same_category_as($post, $type = 'post')
{
  global $wpdb;
  
  _ax_debug('_ax_util_get_posts_in_category_as');
  
  $cat_ids = wp_get_post_categories( $post->ID, array( 'fields' => 'ids' ) );
  
  $term_query = "$wpdb->terms.term_id IN (".implode(',', $cat_ids).") AND $wpdb->term_taxonomy.taxonomy = 'category'";
  
  $querystr = "SELECT DISTINCT wposts.* 
  FROM $wpdb->posts wposts
	LEFT JOIN $wpdb->postmeta ON (wposts.ID = $wpdb->postmeta.post_id)
	LEFT JOIN $wpdb->term_relationships ON (wposts.ID = $wpdb->term_relationships.object_id)
	LEFT JOIN $wpdb->term_taxonomy ON ($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id)
	LEFT JOIN $wpdb->terms ON ($wpdb->term_taxonomy.term_id = $wpdb->terms.term_id)
	WHERE ";
  if ($term_query != '()')
    $querystr .= $term_query;
  
	$querystr .= " AND wposts.post_status = 'publish'
	AND wposts.post_type = '$type'
  AND wposts.ID <> ".$post->ID;
	//AND $wpdb->postmeta.meta_key = 'order'
	//ORDER BY $wpdb->postmeta.meta_value ASC";
  
  _ax_debug($querystr);
  
  $results = $wpdb->get_results($querystr, OBJECT);
  
  return $results;
}

function _ax_util_page_is_child_of($page_id, $parent_id)
{
  $ancestors = get_ancestors($page_id, 'page');
  //echo '<br/>ancestors are: '.implode(' - ', $ancestors).'<br/>';
  return array_search($parent_id, $ancestors) !== false;
}

function _ax_util_get_sibling_pages($page_id, $exclude_page = false)
{
  $page = get_post($page_id);
  
  $params = array(
      'child_of' => $page->post_parent,
      'depth' => 1
  );
  
  //if ($exclude_page)
  //  $params['exclude'] = $page->ID;
  
  return get_posts($params);
}
function _ax_util_get_page_id_from_path($path)
{
  $result = get_page_by_path($path);
  if ($result)
    return $result->ID;
  else
    return 0;
}

function _ax_util_post_exists($slug, $post_type = 'post')
{
  
  global $wpdb;

  $query = $wpdb->prepare(
      'SELECT ID FROM ' . $wpdb->posts . '
      WHERE post_name = %s
      AND post_type = \''.$post_type.'\'',
      $slug
  );
  
  $wpdb->query( $query );

  return $wpdb->num_rows > 0;
}
  
function _ax_util_get_post_from_slug($slug, $type = 'post')
{
  $args = array(
  'name'   => $slug,
  'post_type'   => $type,
  'post_status' => 'publish',
  'numberposts' => 1
  );
  
  $my_posts = get_posts($args);
  
  foreach ($my_posts as $post)
  {
    if ($post->post_name == $slug)
      return $post;
  }
  return false;
}

function _ax_util_get_child_pages_of($page_slug_or_id, $statuses = ['publish'])
{
  global $wpdb;

  $page_slug = '';
  $page_id = 0;
  
  if (is_numeric($page_slug_or_id))
    $page_id = $page_slug_or_id;
  else
  {
    $page_slug = $page_slug_or_id;
    
    if (!empty($page_slug))
    {
      $page_id =_ax_util_get_page_id_from_slug($page_slug);
    }
    else 
      return [];
  }
  
  $status_query = '';
  foreach ($statuses as $status)
  {
    if ($status_query != '') $status_query.=' OR ';
    $status_query .= "wposts.post_status = '".$status."'";
  }
  
  $querystr = "SELECT DISTINCT wposts.* 
  FROM $wpdb->posts wposts
	WHERE wposts.post_parent = $page_id
	AND (".$status_query.")
	AND wposts.post_type = 'page'
	ORDER BY wposts.menu_order ASC";
  
  return $wpdb->get_results($querystr, OBJECT);
}

function _ax_util_get_page_id_from_slug($slug)
{
  return _ax_util_get_id_from_slug($slug, 'page');
}

function _ax_util_get_post_id_from_slug($slug)
{
  return _ax_util_get_id_from_slug($slug, 'post');
}

function _ax_util_get_id_from_slug($slug, $type = 'post')
{
  global $wpdb;
  
  $querystr = "SELECT ID
  FROM $wpdb->posts 
  WHERE post_name = '$slug'
  AND post_status = 'publish'
  AND post_type = '$type'";

  $result = $wpdb->get_results($querystr, OBJECT);

  if (count($result) == 0)
    return 0;
  
  return $result[0]->ID;
}

function _ax_util_get_child_page_count($page_slug_or_id)
{
  global $wpdb;

  $page_slug = '';
  $page_id = 0;
  
  if (is_numeric($page_slug_or_id))
    $page_id = $page_slug_or_id;
  else
  {
    $page_slug = $page_slug_or_id;
    
    if (!empty($page_slug))
    {
      $page_id =_ax_util_get_page_id_from_slug($page_slug);
    }
    else 
      return 0;
  }
  
  $querystr = "SELECT COUNT * 
  FROM $wpdb->posts wposts
	WHERE wposts.post_parent = $page_id
	AND wposts.post_status = 'publish'
	AND wposts.post_type = 'page'
	ORDER BY wposts.menu_order ASC";
  
  return $wpdb->get_var($querystr);
}

function _ax_util_has_child_pages($page_slug_or_id)
{
  return _ax_util_get_child_page_count($page_slug_or_id) > 0;
}


function _ax_util_get_current_url_no_params()
{
	return strtok(_ax_util_get_current_url_with_params(),'?');
}

function _ax_util_get_current_url_with_params()
{
	return $_SERVER["REQUEST_URI"];
}

function _ax_util_format_url_add_params($url, $paramName, $paramValue)
{
	if (strpos($url, '?') > -1)
		return $url.'&'.$paramName.'='.urlencode($paramValue);
	else 
		return $url.'?'.$paramName.'='.urlencode($paramValue);
}

function _ax_util_format_url_remove_param($url, $paramName)
{
	if (strpos($url, '?') == -1)
		return $url;
	else 
	{
		$parts = explode('?', $url);
		$base = $parts[0];
		
		_ax_debug('$base = '.$base);
		
		$params = '?'.$parts[1];
		
		_ax_debug('$params= '.$params);
		
		$value = $_GET[$paramName];
		
		_ax_debug('$value= '.$value);
		
		$search = '&'.$paramName.'='.$value;
		$index = strpos($params, $search);
		if ($index == -1)
		{
			$search = '?'.$paramName.'='.$value;
			$index = strpos($params, $search);
		}
		
		if ($index == -1)
			return $url; //it isn't in there
		else
		{			
			return $base.str_replace($search, '', $params);
		}
	}
}

function _ax_util_current_url_has_slug($slug)
{
  $url = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
  $url_slug = _ax_util_get_slug($url);
  $slug = _ax_util_get_slug($slug);
  //echo '<br/>'.$url_slug.' = '.$slug;
  return $slug == $url_slug;
}

$_ax_util_cur_slug = '';

function _ax_util_get_slug($url = '')
{
  if (!$url)
  {
    global $_ax_util_cur_slug;
    if ($_ax_util_cur_slug)
      return $_ax_util_cur_slug;

    $slug = strtolower(trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
    $_ax_util_cur_slug = $slug;
    return $slug;
  }
  
  return strtolower(trim(parse_url($url, PHP_URL_PATH), '/'));


}

function _ax_util_is_valid_email($email)
{
   return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/*Validates if $phone is a valid U.S. phone number in the 202-555-1234 format. 
Note that the first didgit cannot be a 1 as no area code in the U.S. starts with a 1.
*/
function _ax_util_is_valid_phone($phone) 
{
  $phone = _ax_util_prep_phone_value($phone);
  if(preg_match("/^([1]-)?[0-9]{10}$/i", $phone))
     return true;
  else
     return false;
}

function _ax_util_to_camel_case($string_with_dashes)
{
  $parts = explode('-', $string_with_dashes);
  
  $val = '';
  
  foreach ($parts as $part)
  {
    if ($val == '')
      $val = $part;
    else
    {
      $val.=ucfirst($part);
    }
  }
  
  return $val;
}

function _ax_req($key, $arr = null)
{
  if ($arr)
    return array_key_exists($key, $arr) ? $arr[$key] : '';
  
  if (array_key_exists($key, $_GET))
    return $_GET[$key];
  else if (array_key_exists($key, $_POST))
    return $_POST[$key];
  else 
    return '';
}

function _ax_util_set_default_timezone($timezone)
{
  date_default_timezone_set($timezone);
}

function _ax_util_date_to_local($date, $date_timezone)
{
  if ($date_timezone == date_default_timezone_get())
    return $date;
  
  //the date presented is in timezone A and we want to convert it to the timezone B...
  $cur_date = new DateTime($date, new DateTimeZone($date_timezone)); //this is the date with the timezone set...
  $cur_date->setTimezone(new DateTimeZone(date_default_timezone_get())); //we'll let php change the timezone for us...
	return date("Y-m-d H:i:s", strtotime($cur_date));
}

function _ax_slash_r($str)
{
  if (_ax_util_str_ends_with($str, '/'))
    return substr($str, 0, strlen($str) - 1);
  else
    return $str;
}

//from https://gist.github.com/gserrano/4c9648ec9eb293b9377b
function _ax_util_copy_dir_recursive($src, $dst) 
{
	$dir = opendir($src);
	@mkdir($dst);
	while(( $file = readdir($dir)) ) 
  {
		if (( $file != '.' ) && ( $file != '..' )) 
    {
			if ( is_dir($src . '/' . $file) ) 
      {
				_ax_util_copy_dir_recursive($src .'/'. $file, $dst .'/'. $file);
			}
			else 
      {
        //echo '<br>copy '.$src .'/'. $file.' to '.$dst .'/'. $file;
				copy($src .'/'. $file,$dst .'/'. $file);
			}
		}
	}
	closedir($dir);
}

function _ax_util_rename_dir($dir, $new_name)
{
  if ($dir == '/')
    return;
  
  $parts = explode('/', $dir);
  $parent = implode('/', array_slice($parts, 0, count($parts) - 1));
  
  echo '<br>will rename '.$dir.' to '.$parent.'/'.$new_name;
}

function _ax_util_delete_dir_recursive($dir) 
{
  
  if (is_dir($dir) && $dir != '/') 
  {
    $objects = scandir($dir);
    foreach ($objects as $object) 
    {
      if ($object != "." && $object != "..") 
      {
        if (filetype($dir."/".$object) == "dir") 
           _ax_util_delete_dir_recursive($dir."/".$object); 
        else 
          unlink($dir."/".$object);
      }
    }
    reset($objects);
    rmdir($dir);
  }
 }