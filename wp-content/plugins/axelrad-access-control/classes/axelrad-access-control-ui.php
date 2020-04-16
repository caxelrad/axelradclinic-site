<?php


function _ax_user_profile_stuff($user)
{
  
  if ($user->ID == 0) return;

  $groups = AxelradUserMgmt::groups_fetch();
  $user_groups = AxelradUserMgmt::get_user_memberships($user->ID);
  
  $form = '';

  if (count($groups) > 0)
  {
    $form = '<div id="groups_input_box" style="width: 500px; column-count: 3;  padding-top: 10px;">'
      ._ax_get_post_groups_form_input($groups, $user_groups).'</div>';
  }

  $form.= '<p/><input type="submit" name="send_welcome" value="Send Welcome Email">';

  echo '<h2>Access Control</h2>'.$form;

}

add_action('show_user_profile', '_ax_user_profile_stuff', 10, 1); // editing your own profile
add_action('edit_user_profile', '_ax_user_profile_stuff', 10, 1); // editing another user
add_action('user_new_form', '_ax_user_profile_stuff', 10, 1); // creating a new user

function _ax_user_profile_save($userId) 
{

  if (!current_user_can('edit_user', $userId)) return;
  
  if ($_REQUEST['send_welcome'])
  {
    AxelradAccessControl::send_welcome_email($userId);
    return;
  }

  if (isset($_POST['user_group_id']))
  {
    $group_ids = $_POST['user_group_id'];
    
    AxelradUserMgmt::set_user_groups($userId, $group_ids);
  }
  
}


add_action('personal_options_update', '_ax_user_profile_save', 10, 1);
add_action('edit_user_profile_update', '_ax_user_profile_save', 20, 1);
add_action('user_register', '_ax_user_profile_save', 10, 1);


function _ax_get_user_groups_form_input($all_groups, $memberships)
{
  $input = '<input type="hidden" name="add_group" id="add_group" value="">
  <input type="hidden" name="rmv_group" id="rmv_group" value="">
  <table border="0" cellpadding="0" cellspacing="0">';

  $btn_style= 'border: solid 1px transparent; margin: 1px; 
  border-radius: 3px; font-size: 18px; font-weight: 600; cursor: pointer; color: white;';
  
  $i = 0;
  foreach ($all_groups as $group)
  {
    $in_group = false;
    foreach ($memberships as $membership)
    {
      if ($group['name'] == $membership['group_name'])
      {
        $in_group = true;
        break;
      }
    }
    
    $buttons = '';
  
    if ($in_group)
    {
      $buttons = '<input class="grp-rmv" 
      data-name="'.$group['name'].'" type="button" style="'.$btn_style.' background-color: maroon;" 
      value=" X " />';  
    }
    else
    {
      $buttons = '<input class="grp-add" data-name="'.$group['name'].'" 
      type="button" style="'.$btn_style.' background-color: darkgreen;" value=" + " />';
    }

    $input .= '<tr>
    <td valign="top" style="padding: 2px;">'.$buttons.'</td>
    <td valign="top" style="padding: 2px; font-size: 17px;">'.$group['display_name'].'</td>
    </tr>';

    $i++;
  }

  $input.='</table>
  <script type="text/javascript">
  
  jQuery(
    function()
    {
      jQuery(".grp-add").click(
        function(event)
        {
          jQuery("#add_group").val(jQuery(this).attr("data-name"));
          jQuery("#submit").click();
        }
      );
      
      jQuery(".grp-rmv").click(
        function(event)
        {
          jQuery("#rmv_group").val(jQuery(this).attr("data-name"));
          jQuery("#submit").click();
        }
      );
    }
  );
  
  </script>';
  
  return $input;
}

function _ax_get_post_groups_form_input($all_groups, $selected_groups)
{
  $input = '';

  $i = 0;
  foreach ($all_groups as $group)
  {
    $selected = '';
    foreach ($selected_groups as $sel_group)
    {
      if ($group['id'] == $sel_group['id'])
      {
        $selected = 'checked ';
        break;
      }
    }
    
    $input .= '<div style="margin-bottom: 5px;">
        <input class="groupbox" type="checkbox" id="group_'.$i.'" name="user_group_id[]" '.$selected.'value="'.$group['id'].'">
        <label for="group_'.$i.'">'.$group['display_name'].'</label>
      </div>';
    $i++;
  }

  return $input;
}

function _ax_get_post_roles_form_input($all_role_names, $selected_role_names)
{
  $input = '';

  $i = 0;
  foreach ($all_role_names as $role_name)
  {
    $selected = '';
    foreach ($selected_role_names as $sel_role_name)
    {
      if ($role_name == $sel_role_name)
      {
        $selected = 'checked ';
        break;
      }
    }
    
    $input .= '<div style="margin-bottom: 5px;">
        <input class="groupbox" type="checkbox" id="role_'.$i.'" name="user_role_name[]" '.$selected.'value="'.$role_name.'">
        <label for="role_'.$i.'">'.$role_name.'</label>
      </div>';
    $i++;
  }

  
  return $input;
}

function _ax_register_meta_boxes() 
{
  add_meta_box( 'ax-users-post-security', __( 'Who Can Access This', 'axelrad' ), '_ax_display_post_security_box', 'post');
  add_meta_box( 'ax-users-post-security', __( 'Who Can Access This', 'axelrad' ), '_ax_display_post_security_box', 'page');
}

add_action( 'add_meta_boxes', '_ax_register_meta_boxes' );

function _ax_display_post_security_box($post)
{
  echo _ax_get_post_security_box_html($post->ID);
}


function _ax_get_post_security_box_html($post_id)
{

  $form = '';
  $sec_type_disabled = '';
  $post_sec_type = _ax_post_get_security_type( $post_id);

  // Add a nonce field so we can check for it later.
  wp_nonce_field( '_ax_security_box', '_ax_security_box' );

 
    
    //echo 'saved security type = '.$value;
  $form = '<select name="_ax_sec_type" id="_ax_sec_type">';
  $types = AxelradAccessControl::get_access_types();
  foreach ($types as $type)
  {
      $form.='<option value="'.$type.'"'.($post_sec_type == $type ? ' selected="true"' : '').'>'._ax_post_get_security_type_display_name($type).'</option>';
  }
  $form .= '</select>';
  
  $group_disp = $post_sec_type == AxelradAccessControl::ACCESS_TYPE_GROUP ? '' : 'none';

  $groups = AxelradUserMgmt::groups_fetch();
  $groups_with_access = AxelradUserMgmt::groups_fetch_by_id(AxelradAccessControl::get_access_principals($post_id));
  
  if (count($groups) > 0)
  {
    $form.='<div id="groups_input_box" style="column-count: 3;  padding-top: 10px; display:'.$group_disp.';">'
      ._ax_get_post_groups_form_input($groups, $groups_with_access).'</div>'; //$selected_role_names
  }
  

  $role_disp = $post_sec_type == AxelradAccessControl::ACCESS_TYPE_ROLE ? '' : 'none';

  $roles = AxelradUserMgmt::role_names_fetch();
  $roles_with_access = AxelradAccessControl::get_access_principals($post_id);
  
  if (count($roles) > 0)
  {
    $form.='<div id="roles_input_box" style="column-count: 3; padding-top: 10px; display:'.$role_disp.';">'
      ._ax_get_post_roles_form_input($roles, $roles_with_access).'</div>'; //$selected_role_names
  }

  $def_url = '';
  $no_auth_url = AxelradAccessControl::get_post_access_denied_url($post_id);
  if (!$no_auth_url)
    $def_url = 'Will use the default: '.AxelradAccessControl::$no_auth_url;

  $form.='<p/><strong>URL of page to show when unauthorized user tries to access this page:</strong><br>
    <input placeholder="'.$def_url.'" type="text" id="no_access_url" name="no_access_url" maxlength="255" value="'.$no_auth_url.'" size="120">';
  
  $def_msg = '';
  $no_auth_msg = AxelradAccessControl::get_post_access_denied_message($post_id);
  if (!$no_auth_msg)
    $def_msg = 'Will use the default: '.AxelradAccessControl::$access_denied_msg;

  $form.='<p/><strong>Message to show on login screen when unauthorized user tries to access this page:</strong><br>
    <textarea placeholder="'.htmlentities($def_msg).'" type="text" id="no_access_msg" name="no_access_msg" 
      rows="5" cols="75">'.$no_auth_msg.'</textarea>';
  
  
  $def_url = '';
  $no_auth_url = AxelradAccessControl::get_post_register_access_url($post_id);
  if (!$no_auth_url)
    $def_url = 'Will use the default: '.AxelradAccessControl::$join_url;

  $form.='<p/><strong>URL of page where unauthorized user can create an account:</strong><br>
    <input placeholder="'.$def_url.'" type="text" id="register_url" name="register_url" maxlength="255" value="'.$no_auth_url.'" size="120">';
  
  
  //$form.='<p>'.json_encode(AxelradAccessControl::get_raw_access_data($secure_post_id)).'</p>';

  $form.='<script type="text/javascript">
  
  jQuery(
    function()
    {
      jQuery("#_ax_sec_type").change(
        function(event)
        {
          var val = jQuery(this).val();
          if (val == "'.AxelradAccessControl::ACCESS_TYPE_ROLE.'")
          {
            jQuery("#groups_input_box").hide();
            jQuery("#roles_input_box").show();
          }
          else if (val == "'.AxelradAccessControl::ACCESS_TYPE_GROUP.'")
          {
            jQuery("#roles_input_box").hide();
            jQuery("#groups_input_box").show();
          }
          else
          {
            jQuery("#roles_input_box").hide();
            jQuery("#groups_input_box").hide();
          }
        }
      );
    }
  );
  
  </script>';

   echo $form;
}


function _ax_save_post_security( $post_id ) {

    // Check if our nonce is set.
    if ( ! isset( $_POST['_ax_security_box'] ) ) {
        return;
    }

    // Verify that the nonce is valid.
    if ( ! wp_verify_nonce( $_POST['_ax_security_box'], '_ax_security_box' ) ) {
        return;
    }

    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Check the user's permissions.
    if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {

        if ( ! current_user_can( 'edit_page', $post_id ) ) {
            return;
        }

    }
    else {

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
    }

    // OK, it's safe for us to save the data now.

    // Make sure that it is set.
    if ( ! isset( $_POST['_ax_sec_type'] ) ) {
        return;
    }

    // Sanitize user input.
    $sec_type = $_POST['_ax_sec_type'];
    echo '$sec_type = '.$sec_type;
    // Update the meta field in the database.
    
    if ($sec_type == AxelradAccessControl::ACCESS_TYPE_PUBLIC || 
      $sec_type == AxelradAccessControl::ACCESS_TYPE_ANY_LOGGED_IN ||
      $sec_type == AxelradAccessControl::ACCESS_TYPE_ADMIN_ONLY)
    {
      AxelradAccessControl::set_access($sec_type, $post_id);
    }
    else if ($sec_type == AxelradAccessControl::ACCESS_TYPE_GROUP)
    {
      if (isset($_POST['user_group_id']))
      {
        $group_ids = $_POST['user_group_id'];
        AxelradAccessControl::set_access($sec_type, $post_id, $group_ids);
      }
    }
    else if ($sec_type == AxelradAccessControl::ACCESS_TYPE_ROLE)
    {
      if (isset($_POST['user_role_name']))
      {
        $role_names = $_POST['user_role_name'];
        AxelradAccessControl::set_access($sec_type, $post_id, $role_names);
      }
    }

    if (isset($_POST['no_access_msg']))
      AxelradAccessControl::set_post_access_denied_message($post_id, $_POST['no_access_msg']);

    if (isset($_POST['no_access_url']))
      AxelradAccessControl::set_post_access_denied_url($post_id, $_POST['no_access_url']);

    if (isset($_POST['register_url']))
      AxelradAccessControl::set_post_register_access_url($post_id, $_POST['register_url']);
    
}

add_action( 'save_post', '_ax_save_post_security' );


function _ax_post_get_security_type($post_id)
{
  return AxelradAccessControl::get_post_access_type($post_id);
}

function _ax_post_get_security_type_display_name($sec_type)
{
  return AxelradAccessControl::get_post_security_type_display_name($sec_type);
}


