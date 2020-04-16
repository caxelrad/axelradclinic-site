<?php
class AxelradCommands
{
  static $_commands = [];

  public static function register($cmd_name, $target)
  {
    _ax_debug('registering command: '.$cmd_name);
    self::$_commands[$cmd_name] = $target;
  }

  public static function run()
  {
    _ax_debug('running command engine');
    if ($_GET['cmd'])
    {
      if (!is_user_logged_in()) return;
      if (!current_user_can('administrator')) return;

      if (self::$_commands[$_GET['cmd']])
      {
        AxLogger::set_enabled(true, new AxDebugInlineEchoStream());

        _ax_debug('the command '.$_GET['cmd'].' is registered...');
        
        $target = self::$_commands[$_GET['cmd']];
        if (strpos($target, '::') > 0)
        {
          $parts = explode('::', $target);
          $class_name = $parts[0];
          $method_name = $parts[1];
          _ax_debug('going to call '.$class_name.'::'.$method_name);
          AxelradUtil::try_method($class_name, $method_name);
        }
        else
        {
          _ax_debug('going to call the function '.$target);
          $target(); //just a function.
        }
      }
    }
  }

  public static function get_cmd_url($cmd_name, $args = [])
  {
    if (count($args))
    {
      $q = '';
      foreach ($args as $key => $value)
      {
        $q = $q.'&'.$key.'='.urlencode($value);
      }
    }

    return strtok($_SERVER['REQUEST_URI'], '?').'?cmd='.urlencode($cmd_name).$q.'&mtx='.time();
  }
}

add_action('plugins_loaded', 'AxelradCommands::run');