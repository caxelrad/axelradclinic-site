<?php

class AxelradAccessControlForms
{
  
  
  static $_result_type = '';
  static $_result_message = '';
  
  public static function set_result($type, $message)
  {
    self::$_result_message = $message;
    self::$_result_type = $type;
  }
  
  static function get_result_type() { return self::$_result_type; }
  static function get_result_message() { return self::$_result_message; }
  

  public static function check_reset_code($code)
  {
    $parts = explode('-', $code);
    //last 3 digits are the sum of the other chars
    $total = 0;
    for ($i = 0; $i < 3; $i++)
    {
      $part = $parts[$i];
      for ($x = 0; $x < strlen($part); $x++)
      {
        $total += ord(substr($part, $x, 1));
      }
    }
    
    $sum = substr($code, strlen($code) - 3);
    
    //echo $sum.' --- '.$total;
    
    return intval($sum) === $total; //strlen($code);
  }
  
  
  
  public static function pwd_chg_form()
  {
    
    $form = '<div style="text-align:center; padding-top: 40px;">
        <div style="width: 100%; max-width: 450px; margin-left: auto; margin-right: auto;">';
    if (AxelradAccessControl::login_logo_path())
          $form.='<div style="padding:10px; text-align: center;">
            <a href="/"><img style="max-width: 300px;" src="'.AxelradAccessControl::login_logo_path().'" /></a>
          </div>';
    
    $show_form = true;
    if (_ax_req('action') == 'rp' && _ax_req('key') && _ax_req('login'))
    {
      $result = AxelradAccessControl::validate_pwd_reset_key($_GET['login'], $_GET['key']);
      if ($result != 'valid-key')
      {
        $form.= self::warning('The link used appears to be either invalid or expired. 
        If you still need a new password, <a href="'.AxelradAccessControl::get_reset_pwd_url().'">click here to restart the process now.</a>');
        $show_form = false;
      }
    }


    if ($_POST['upd_pwd'])
    {
      if (AxelradAccessControl::set_new_pwd(_ax_req('login'), _ax_req('key'), _ax_req('newpwd1')))
      {
        $form.= self::success('<strong>Great job!</strong> You have successfully updated your password.').'
        <p><center><a style="font-size: 1.3rem;" href="'.AxelradAccessControl::$login_url.'">Click here to log in now</a></center></p>.';
        $show_form = false;
      }
      else 
      {
        $form.= self::alert('Password reset failed. 
          <a href="'.AxelradAccessControl::get_reset_pwd_url().'">Click here to try again</a>.<p>If you continue to experience problems, send us an email at '.AxelradAccessControl::$email_support_address.'.');
        $show_form = false;
      }
    }
    
    if ($show_form)
    {
    $form.='<div style="padding-top: 20px;">
            <h4>Let\'s set your new password</h4>
            <p>Just enter a password with a minimum of 6 characters, no spaces. Make sure the password and confirmation password are the same 
            and click "Update Password" to complete the process.</p>
            <div style="text-align: left;">
              <form accept-charset="UTF-8" action="'.self::current_url_no_params().'?action=newpwd" id="chg-pwd-form" name="chg-pwd-form" method="POST">
              <input type="hidden" name="upd_pwd" value="1"/>
              <input type="hidden" name="action" value="rp"/>
              <input type="hidden" name="login" value="'._ax_req('login').'"/>
              <input type="hidden" name="key" value="'._ax_req('key').'"/>
              <div class="form-group">
                <input type="password" class="form-control" id="newpwd1" name="newpwd1" value="" placeholder="Enter your new password">
              </div>
              <div class="form-group">
                <input type="password" class="form-control" id="newpwd2" name="newpwd2" placeholder="Enter your new password again">
              </div>
          </div>
          <div class="form-group">  
            <input type="submit" value="Update password" class="btn btn-success btn-block" style="font-size: 20px; white-space: normal !important;"/>
          </div>
        </form>
        <script type="text/javascript">
        jQuery(function()
        {
          jQuery("#chg-pwd-form").submit(
            function(event)
            {
              if (jQuery("#newpwd1").val().length < 6)
              {
                alert("Make sure your password is at least 6 letters long.");
                event.preventDefault();
              }
              
              if (jQuery("#newpwd1").val() != jQuery("#newpwd2").val())
              {
                alert("The passwords do not match.");
                event.preventDefault();
              }
            }
          );
        });
        </script>';
    }
    return $form;


  }


  public static function pwd_reset_form()
  {
    $form = '<style>
    input.form-control { min-width: 300px; }
    div.input-group { width: 100% !important; }
    </style>';

    if (_ax_req('reset_pwd'))
    {

      $email = trim(_ax_req('email'));
      
      $success = false;
      if (!_ax_util_is_valid_email($email))
        self::alert('Please enter a valid email address.');
      else 
      {
        AxelradAccessControl::initiate_pwd_reset(_ax_req('email'));
        $success = true;
      }
    }

      $form .= 
      '<div style="text-align:center; padding-top: 40px;">
        <div style="width: 100%; max-width: 450px; margin-left: auto; margin-right: auto;">';
      if (AxelradAccessControl::login_logo_path())
          $form.='<div style="padding:10px; text-align: center;">
            <a href="/"><img style="max-width: 300px;" src="'.AxelradAccessControl::login_logo_path().'" /></a>
          </div>';
    
      
    if ($success)
    {
      $form .= self::success('<b>Success!</b> If an account with "'.$email.'" was found, a password reset email was just sent there. Follow instructions in that email to get yourself rolling again.<p/>
                  If the email doesn\'t arrive in the next 60 seconds, <a href="'.self::current_url_no_params().'">click here to try again</a>').
                  '<p/><center><a href="/">Back To Home</a></center>
                </div>
              </div>';
    }
    else
    {
      $form.='<center><h5>No worries, we\'ll send you a link to reset your password. :-)</h5></center>
          <p>Just enter your email address below and you\'ll receive instructions in your inbox shortly</p>
        <div style="text-align: left;">
          <div class="reset-form">
            <form method="POST" id="resetform" name="resetform" action="'.self::current_url_no_params().'">
            <input type="hidden" name="reset_pwd" value="1">
            <input type="hidden" name="redirect_to" value="'.(_ax_req('redirect_to') ? _ax_req('redirect_to') : '/').'"/>
            <div class="form-group">
              <div class="input-group">
                <input type="email" class="form-control" name="email" id="email" placeholder="Your email address">
              </div>
            </div>
            <div class="form-group">
              <button style="margin-top: 10px;" type="submit" id="reset_btn" class="btn btn-success btn-block" style="font-size: 20px;">Reset Password</button>
            </div>
            <p/>
            <center><a href="'.AxelradAccessControl::$login_url.'">Click here to return to the login page.</a></center>
            </form>
          </div>
        </div>
      </div>
    </div>
      <script type="text/javascript">

      jQuery(function()
      {
        jQuery("#email").on("change paste keyup", function()
        {
          _ax_reset_state_chk();
        });

        _ax_reset_state_chk();
      }
      );

      function _ax_reset_state_chk()
      {
        jQuery("#reset_btn").prop("disabled", 
          (jQuery("#email").val() == "" || jQuery("#email").val().indexOf("@") == -1)
          );
      }
      </script>';
    }
    
    return $form;

  }

  public static function update_acct_form()
  {
    if (_ax_req('upd') == '1')
    {
      AxelradUserMgmt::update_user(get_current_user_id(), array('first_name' => _ax_req('first'), 'last_name' => _ax_req('last')));

      $form .= self::success('<strong>Yep...</strong> We got your new information saved :-).');

    }

    $user = AxelradUserMgmt::current();
    
    $first_name = $user ? $user->first_name : '';
    $last_name = $user ? $user->last_name : '';
    $email = $user ? get_userdata($user->ID)->user_login : '';
    
    
    $form .= '<form accept-charset="UTF-8" action="'.self::current_url_no_params().'" id="acctform" name="acctform" method="POST">'.
      '<input type="hidden" name="upd" value="1"/>'.
      '<div class="form-group">'.
        '<label for="first">First name:</label>'.
        '<input type="text" class="form-control" id="first" name="first" value="'.$first_name.'">'.
      '</div>'.
      '<div class="form-group">'.
        '<label for="last">Last name (optional):</label>'.
        '<input type="text" class="form-control" id="last" name="last" value="'.$last_name.'">'.
      '</div>'.
      '<div class="form-group">'.
      '<label for="email">Your current email address:</label>
      <div><b>'.$email.'</b></div>
      </div>'.
      '<div class="form-group">'.
      '<b>PLEASE NOTE:</b> To change your email address, please send us an email at support@chrisaxelrad.com from the new address you wish you use with the subject "'.get_bloginfo( 'name' ).' Email Change" and we will be happy to update it for you.'.
      '</div>'.
      '<div class="form-group">'.
        '<button type="submit" id="update_btn" class="btn btn-success btn-block" style="font-size: 20px; white-space: normal !important;">Update</button>'.
      '</div>'.
    '</form>

      <script type="text/javascript">

      jQuery(function()
      {
        jQuery("#first").on("change paste keyup", function()
        {
          _ax_reset_state_chk();
        });

        jQuery("#last").on("change paste keyup", function()
        {
          _ax_reset_state_chk();
        });

        _ax_reset_state_chk();
      }
      );

      function _ax_reset_state_chk()
      {
        var first = jQuery("#first").val();
        var last = jQuery("#last").val();

        jQuery("#update_btn").prop("disabled", (first == "" || last == ""));
      }
      </script>';


    // '<div class="form-group">'.
    // '<label for="email">Change email address:</label>'.
    // '<input type="text" class="form-control" id="email" name="email" value="">'.
    // '</div>'.
    // '<div class="form-group">'.
    // '<label for="email">Confirm new email address:</label>'.
    // '<input type="text" class="form-control" id="email2" name="email2" value="">'.
    // '</div>'.

    return $form;

  }

  public static function login_form()
  {
   

    //login post is handled in wp_init handler in hooks.php
    $form='
      <div class="login-form" style="text-align: left;">';
    
    if (AxelradAccessControl::login_logo_path())
          $form.='<div style="padding:10px; text-align: center;">
            <a href="/"><img style="max-width: 300px;" src="'.AxelradAccessControl::login_logo_path().'" /></a>
          </div>';
    
    if (_ax_req('msg'))
    {
      if (_ax_req('msg') == 'access-denied')
      {
        $msg = AxelradAccessControl::get_effective_access_denied_message($_GET['pid']);
        $join_url = AxelradAccessControl::get_effective_register_access_url($_GET['pid']);

        if ($join_url)
          $msg."<p/>You can also register an account by <a href=\"".$join_url."\">clicking here</a>";  

        $form .= self::alert($msg);  


      }
      else
        $form .= self::success(_ax_req('msg'));
    }
    if (_ax_req('login') == 'failed')
      $form .= self::alert('<strong>Hmmmm....</strong> Looks like you may have entered the wrong email address or password. If you need to retrieve your password click the link below the form.');

    if (_ax_req('didlogout'))
      $form .= self::success('You\'re now logged out.');

    
    
      if (AxelradAccessControl::login_form_intro() != '')
        $form .= '
        <div style="padding:10px; text-align: center;">
           '.AxelradAccessControl::login_form_intro().'
        </div>';
      
    // <script type="text/javascript" src="'.AxelradOs::$plugin_root . '/os-bin/axelrad-user-system/js/login-form-validator.js?'.time().'"></script>
     
      $form.='
      <div class="login-form" style="text-align: left;">
        <form method="POST" id="loginform" name="loginform" action="/wp-login.php">
        <input type="hidden" name="login" value="1">
        <input type="hidden" name="redirect_to" value="'.(_ax_req('redirect_to') ? _ax_req('redirect_to') : '').'"/>
        <div class="form-group">
          <div class="input-group">
          <input type="email" class="form-control" name="log" id="log" placeholder="Your email address">
          </div>
        </div>
        <div class="form-group">
          <div class="input-group">
            <input type="password" class="form-control" name="pwd" id="pwd" placeholder="Your password">
          </div>
        </div>
        <div class="form-group" style="font-size: 0.8rem !important;">
          <input name="rememberme" type="checkbox" checked id="rememberme" value="forever"> <label for="rememberme">Remember me</label>
        </div>
        <div class="form-group">
          <button type="submit" id="login_btn" class="btn btn-success btn-block" style="font-size: 20px;">Log in</button>
          <div style="text-align: center; margin: 10px auto 10px auto;">Forgot your password? <a href="'.AxelradAccessControl::get_reset_pwd_url().'">Click here to get a new one.</a></div>
        </div>
        </form>
      </div>';

      $join_url = AxelradAccessControl::$join_url;
      if ($_GET['pid'])
        $join_url = AxelradAccessControl::get_effective_register_access_url($_GET['pid']);

      if ($join_url)
          $form.='<div style="text-align: center;"><strong>Don\'t have an account yet? <a href="'.$join_url.'">Click here to create one now.</a></strong></div>';
    
      $form.='<div style="padding-top: 10px; text-align: center; font-size: 0.8rem !important;">By logging into '.get_bloginfo( 'name' ).', 
      you agree to be bound by the <a href="/terms-and-conditions/">terms and conditions</a> of membership.</div>
    
      
      <script type="text/javascript">

      jQuery(function()
      {
        jQuery("#log").on("change paste keyup", function()
        {
          _ax_login_state_chk();
        });

        jQuery("#pwd").on("change paste keyup", function()
        {
          _ax_login_state_chk();
        });

        //_ax_login_state_chk();
      }
      );

      function _ax_login_state_chk()
      {
        console.log("_ax_login_state_chk");
        jQuery("#login_btn").prop("disabled", 
          (jQuery("#log").val() == "" || jQuery("#pwd").val() == "" || jQuery("#pwd").val().length < 6 || jQuery("#log").val().indexOf("@") == -1)
          );
        console.log(jQuery("#login_btn").prop("disabled"));
      }
      </script>';

    return $form.'</div>';

  }

  public static function join_form_submitting()
  {
    return _ax_req('join');
  }
  
  public static function join_form($atts) //$button_text, $initial_role_or_group = 'default', $toc_link = '')
  {

    $button_text = $atts['button_text'] ? $atts['button_text'] : 'Submit';
    $initial_role_or_group = $atts['group'] ? $atts['group'] : 'default';
  
    $first_name_placeholder = $atts['first_name_placeholder'] ? $atts['first_name_placeholder'] : 'Your first name';
    $last_name_placeholder = $atts['last_name_placeholder'] ? $atts['last_name_placeholder'] : 'Your last name';
    $email_placeholder = $atts['email_placeholder'] ? $atts['email_placeholder'] : 'Your email address';
    
    axdebug::enter('AxelradAccessControlForms::join_form');
    axdebug::write_v('$button_text', $button_text);
    axdebug::write_v('$initial_role_or_group', $initial_role_or_group);
    
    _ax_join_form_save($atts);
    
    $form = self::show_result();
    
    $form .= '<form accept-charset="UTF-8" action="'._ax_util_get_current_url_with_params().'" id="joinform" name="joinform" method="POST">
    <input type="hidden" name="join" value="1">
    <input type="hidden" name="c" value="'._ax_req('c').'">
    <input type="hidden" name="initial_role" value="'.(_ax_req('initial_role') ? _ax_req('initial_role') : $initial_role_or_group).'">
    <input type="hidden" name="debug" value="'.(_ax_req('debug') ? _ax_req('debug') : _ax_req('debug')).'">';
    if ($atts['show_first_name'])
      $form.='<div class="form-group">
        <input type="text" class="form-control" id="first" name="first" value="'._ax_req('first').'" placeholder="'.$first_name_placeholder.'">
      </div>';
    
    if ($atts['show_last_name'])
        $form.='<div class="form-group">
        <input type="text" class="form-control" id="last" name="last" value="'._ax_req('last').'"  placeholder="'.$last_name_placeholder.'">
      </div>';
    
    $form.='<div class="form-group">
      <input type="email" class="form-control" id="email" name="email" value="'._ax_req('email').'"  placeholder="'.$email_placeholder.'">
    </div>';

    if (AxelradAccessControl::$terms_url)
    {
      $form .='<div class="form-group">
                <div class="checkbox"><label><input type="checkbox" id="toc" name="toc" value="1"> I agree to the <a target="blank" href="'.AxelradAccessControl::$terms_url.'">terms and conditions of membership</a>.</label></div>
              </div>';
    }

    $login_url = AxelradAccessControl::$login_url;
    if (strrpos($login_url, '/') < strlen($login_url) - 1)
      $login_url.='/';

    $form .= '<div class="form-group">
      <button type="submit" class="btn btn-success btn-block" style="font-size: 20px; white-space: normal !important;">'.$button_text.'</button>
      <center>** We will NEVER share your information with anyone. Period. **</center>
    </div>
    <div class="form-group" style="font-size: 14px;">
    <center>Already a member? <a href="'.$login_url.'?redirect_to='.rawurlencode(_ax_req('redirect_to')).'">Click to login now.</a></center>
    </div>
    </form>';

    return $form;
  }
  
  static function current_url_no_params()
  {
    return strtok(self::current_url_with_params(),'?');
  }

  static function current_url_with_params()
  {
    return $_SERVER["REQUEST_URI"];
  }
  
  static function warning($msg)
  {
    return '<div style="margin-bottom: 15px; background-color: #F5DDDD; color: #B34746; padding: 10px; border: solid 1px #B34746;">'.
          $msg.
          '</div>';
  }

  static function alert($msg)
  {
    return '<div style="margin-bottom: 15px; background-color: #F5DDDD; color: #B34746; padding: 10px; border: solid 1px #B34746; margin: 10px auto 10px auto;">'.
          $msg.
          '</div>';
  }

  static function success($msg)
  {
    return '<div style="margin-bottom: 15px; background-color: #DAF0D7; color: #288A98; padding: 10px; border: solid 1px #288A98;">'.
          $msg.
          '</div>';
  }
  
  static function show_result()
  {
    if (self::get_result_type() == 'error')
      return self::alert(self::get_result_message());
    else if (self::get_result_type() == 'warning')
      return self::warning(self::get_result_message());
    else if (self::get_result_message())
      return self::success(self::get_result_message());
    else
      return '';
  }
  
  public static function create_pages()
  {
    /*
    $join_url = '/join/';
    $join_success_url = '/join-success/';
    $login_url = '/member-login/';
    $logout_url = '/member-login/';
    $reset_pwd_url = '/reset-password/';
    $set_new_pwd_url = '/reset-password-2/';
    $no_auth_url = '/member-login/';
    $join_email_cc = '';
    $remote_admin_url = '/a/';
  */
    
    echo '<br>Going to create standard pages for user system.';
    if (AxelradAccessControl::$join_url)
    {
      self::create_standard_page(_ax_util_get_slug(AxelradAccessControl::$join_url), 'Join', '[_ax_users_join_form]');
    }

    if (AxelradAccessControl::$join_success_url)
    {
      self::create_standard_page(_ax_util_get_slug(AxelradAccessControl::$join_success_url), 'Join Success', '<center><h2>Success</h2><p>Congratulations. Your account has been created!</p><p>Check your inbox now for instructions
      on how to finish setting up your account.</p><p>(We send this email to make sure you\'re an actual human and not a robot creating the account. ;-) )</p>');
    }

    if (AxelradAccessControl::$login_url)
    {
      self::create_standard_page(_ax_util_get_slug(AxelradAccessControl::$login_url), 'Log in', '[_ax_users_login_form]');
    }

    //_ax_users_pwd_chg_form

    if (AxelradAccessControl::get_reset_pwd_url())
    {
      self::create_standard_page(AxelradAccessControl::get_reset_pwd_url(), 'Reset Your Password', '[_ax_users_pwd_reset_form]');
    }

//     if (AxelradAccessControl::$set_new_pwd_url)
//     {
//       self::create_standard_page(_ax_util_get_slug(AxelradAccessControl::$set_new_pwd_url), 'Change Your Password', '[_ax_users_pwd_chg_form]');
//     }

    if (AxelradAccessControl::$no_auth_url != AxelradAccessControl::$login_url)
    {
      $pgcontent = "<center><h2>Well, that didn't work</h2><p>Looks like you don't have access to this page.</p>".
      '<p>If you have an account <a href="'.AxelradAccessControl::$login_url.'">click here to log in now</a>.</p>';

      if (!empty(AxelradAccessControl::$join_url))
        $pgcontent .="<p>If you DON'T have an account,".' <a href="'.AxelradAccessControl::$join_url.'">you can create one here</a>.</p>';

      $pgcontent .='<p><a href="/">Click to return to the home page</a>.</p>';


      self::create_standard_page(_ax_util_get_slug(AxelradAccessControl::$no_auth_url), "Well, That Didn't Work", $pgcontent);
    }
  }
  
  static function create_standard_page($slug, $title, $content)
  {
    if (!_ax_util_post_exists($slug, 'page'))
      {
        $new_post = array(
         'post_title' => $title,
          'post_name' => $slug,
         'post_content' => $content,
         'post_status' => 'publish',
         'post_date' => $timeStamp = date('Y-m-d H:i:s', time()),
         'post_author' => AxelradUserMgmt::current_id(),
         'post_type' => 'page'
         ); 
      
        wp_insert_post($new_post);
        echo '<br>Page '.$title.' created successfully.';
      }
  }
}


function _ax_users_pwd_reset_form_func($atts)
{
  return AxelradAccessControlForms::pwd_reset_form();
}

add_shortcode('_ax_users_pwd_reset_form', '_ax_users_pwd_reset_form_func');
add_shortcode('_ax_portal_pwd_reset_form', '_ax_users_pwd_reset_form_func');

function _ax_users_pwd_chg_form_func($atts)
{
  return AxelradAccessControlForms::pwd_chg_form();
}

add_shortcode('_ax_users_pwd_chg_form', '_ax_users_pwd_chg_form_func');
add_shortcode('_ax_portal_pwd_chg_form', '_ax_users_pwd_chg_form_func');

function _ax_users_acct_form_func($atts)
{
  return AxelradAccessControlForms::update_acct_form();
}

add_shortcode('_ax_users_acct_form', '_ax_users_acct_form_func');
add_shortcode('_ax_portal_acct_form', '_ax_users_acct_form_func');

function _ax_users_login_form_func($atts)
{
  return AxelradAccessControlForms::login_form();
}

add_shortcode('_ax_users_login_form', '_ax_users_login_form_func');
add_shortcode('_ax_portal_login_form', '_ax_users_login_form_func');

function _ax_users_join_form_func($atts)
{
  $atts = shortcode_atts(
		array(
      'button_text' => 'Create Account', 
      'role' => '',
      'group' => '',
      'first_name_required' => '1',
      'last_name_required' => '',
      'show_first_name' => '1',
      'show_last_name' => '',
      'last_name_placeholder' => '',
      'first_name_placeholder' => '',
      'email_placeholder' => ''
		), $atts);
  
  return AxelradAccessControlForms::join_form($atts);
}

add_shortcode('_ax_users_join_form', '_ax_users_join_form_func');
add_shortcode('_ax_portal_join_form', '_ax_users_join_form_func');

function _ax_user_func($atts)
{
  $atts = shortcode_atts(
		array(
      'property' => ''
		), $atts);
  
  if ($atts['property'] == '')
    return '';
  else 
    return AxelradUserMgmt::current()->$atts['property'];
}

add_shortcode('_ax_user', '_ax_user_func');


add_action('init', '_ax_user_forms_init');

//AxelradOs::on(AxelradOs::EVENT_INIT_COMPLETE, '_ax_user_forms_check');

function _ax_join_form_save($atts)
{
  if (AxelradAccessControlForms::join_form_submitting())
  {
    if (
      ($atts['first_name_required'] && _ax_req('first') == '') || 
      ($atts['last_name_required'] && _ax_req('last') == '')
      || _ax_req('email') == ''
      )
    {
      AxelradAccessControlForms::set_result('warning', 'Please make sure you fill out all the required fields so we can get your account set up correctly.');
    }
    else
    {
      //AxelradDebug::_echo('find_user_id_by_email');
      //create the user here in wordpress first...
      $user_id = AxelradUserMgmt::find_user_id_by_email(_ax_req('email'));
      _ax_debug('user_id with this email = '.$user_id);

      if ($user_id > 0)
      {
        $message = '<strong>There is already an account with that email address. You can either:</strong> 
        <ol>
          <li><a href="/login">Click here to log in now</a></li>
          <li><a href="'.AxelradAccessControl::get_reset_pwd_url().'">Click here to request a new password for that email address</a>.</li>
          <li>Enter another email address below to create a new account.</li>
        </ol>';
        AxelradAccessControlForms::set_result('warning', $message);
      }
      else 
      {
        _ax_debug('creating the user with email = '._ax_req('email'));

        //AxelradDebug::_echo('write_user');
        
        $user_id = AxelradUserMgmt::create_user(_ax_req('email'), _ax_req('first'), _ax_req('last'), _ax_req('initial_role') ? _ax_req('initial_role') : 'default');
        if ($user_id == 0)
        {
          AxelradAccessControlForms::set_result('error', 
            'There was an error saving your information. 
            <a href="#" onclick="document.refresh();">Click here</a> to try again 
            and if you still see this message, send us an email at '.AxelradAccessControl::$email_support_address.' and we\'ll get you all set up.');
        }
        else 
        {
          //AxelradDebug::_echo('done!');
          $redirect_to = _ax_req('redirect_to') ? _ax_req('redirect_to') : AxelradAccessControl::$join_success_url;
          if ($redirect_to)
          {
            $params = 'c='.urlencode(_ax_req('c'));
            
            if (strpos($redirect_to, '?') !== false)
              $redirect_to.='&'.$params;
            else 
            {
              if (!_ax_util_str_ends_with($redirect_to, '/'))
                $redirect_to.='/';
              
              $redirect_to.='?'.$params;
            }
            
            wp_redirect($redirect_to);
            die;
          }
          else
          {
            AxelradAccessControlForms::set_result('success', 
            'Awesome! Your account was successfully set up. Check your inbox for a confirmation email and once you follow those instructions, you\'ll be all good to go.');
          }
        }      
      }  
    }
  }


}
