<?php

include 'legacy.php';
include 'AxDebugOutputStream.php';

class AxLogger
{
  public static $enabled = false;
  private static $_scopes = [];
  private static $_lines = [];
  
  private static $_stream = null; //default stream is comments....
  
  //called immediately b/c logging can't wait until initialization
  public static function load()
  {
    global $_debug;
    self::set_enabled(
      $_debug || $_GET['debug'] || $_POST['debug'], 
      self::$_stream ? null : new AxDebugInlineEchoStream()
    ); //default stream is comments....
  }
  
  
  public static function init()
  {  
    add_action('wp_footer', 'AxelradDebug::_wp_write_all');
  }
  
  public static function set_stream($stream)
  {
    self::$_stream = $stream;
    if (self::$enabled)
      self::$_stream->open();
  }
  
  public static function set_enabled($enabled, $stream = null)
  {
    self::$enabled = $enabled;
    if ($stream)
      self::set_stream($stream);
    else
      self::set_stream(new AxDebugInlineEchoStream());
    
  }
  
  public static function _echo($value)
  {
    if (!self::$enabled) return;
    echo '<br>'.$value;
  }
  public static function _wp_write_all()
  {
    if (self::$enabled)
      self::$_stream->close();
  }
  
  public static function set_scope($name, $is_enabled)
  {
    if ($is_enabled)
    {
      if (array_search($name, self::$_scopes) === false)
        array_push(self::$_scopes, $name);
    }
    else 
    {
      $index = array_search($name, self::$_scopes);
      if ($index !== false)
        array_splice(self::$_scopes, $index, 1);
    }
  }

  public static function is_in_scope($scope_name)
  {
    if ($scope_name == null || empty($scope_name)) return true;

    return array_search($scope_name, self::$_scopes) !== false;
  }

  public static function write($value, $scope = null)
  {
    if (self::$enabled && self::is_in_scope($scope))
    {
      self::$_stream->write($value);
    }
  }
  
  public static function write_json($value, $scope = null)
  {
    if (self::$enabled && self::is_in_scope($scope))
    {
      self::$_stream->write(json_encode($value));
    }
  }

  public static function write_v($name, $value, $scope = null)
  {
    self::write($name.' = '.$value, $scope);
  }

  public static function enter($value, $scope = null)
  {
    self::write('ENTERING: '.$value, $scope);
  }

  public static function leave($value, $scope = null)
  {
    self::write('EXITING: '.$value, $scope);
  }

  public static function write_error($script, $fn_or_msg, $msg = '')
  {
    if ($msg)
      self::write('<!-- ERROR: '.$msg.' ('.$script.' :: '.$fn_or_msg.') -->');
    else
      self::write('<!-- ERROR: '.$fn_or_msg.' ('.$script.') -->');
  }
}

class axdebug
{
  public static function enabled() { return AxLogger::$enabled; }
  
  public static function set_stream($stream)
  {
    AxLogger::set_stream($stream);
  }
  
  public static function set_enabled($enabled)
  {
    AxLogger::set_enabled($enabled);
  }
  
  public static function set_scope($name, $is_enabled)
  {
    AxLogger::set_scope($name, $is_enabled);
  }

  public static function is_in_scope($scope_name)
  {
    return AxLogger::is_in_scope($scope_name);
  }

  public static function write($value, $scope = null)
  {
    AxLogger::write($value, $scope);
  }

  public static function write_v($name, $value, $scope = null)
  {
    AxLogger::write_v($name, $value, $scope);
  }

  public static function enter($value, $scope = null)
  {
    AxLogger::enter($value, $scope);
  }

  public static function leave($value, $scope = null)
  {
    AxLogger::leave($value, $scope);
  }

  public static function write_error($script, $fn_or_msg, $msg = '')
  {
    AxLogger::write_error($script, $fn_or_msg, $msg);
  }

}
