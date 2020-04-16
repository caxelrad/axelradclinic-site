<?php

require_once(__DIR__.'/../_common/simple_html_dom.php');

include 'axelrad-mercury-app.php';
include 'axelrad-mercury-hooks.php';
include 'axelrad-mercury-shortcodes.php';
include 'axelrad-mercury-rest.php';

AxBin::load('axelrad-util');
AxBin::load('axelrad-user-mgmt');

AxelradUtil::$load_bootstrap = true;

class AxelradMercuryLoader
{
  static $_apps = [];
  public static $repository = ''; //set this to the folder that contains the common "imported" stuff for the app

  public static function register_app($src_dir)
  {
    $dir = _ax_slash_r(str_replace('\\', '/', $src_dir));

    //set the default repository if it's not already set
    if (!self::$repository)
      self::$repository = __DIR__.'/../_common/ui';

    //namespace is the foldername
    $parts = explode('/', $dir);
    $namespace = array_pop($parts);
    $root = implode('/', array_slice($parts, 0, count($parts)));
    //echo '$root = '.$root;

    if (self::$_apps[$namespace])
      throw new Exception('Namespace '.$namespace.' is already registered.');

    self::$_apps[$namespace] = $root;

    AxelradUtil::add_script(
      AxelradUtil::to_virtual_path(__DIR__.'/scripts/axelrad-mercury.js')
    );
  }

  public static function get_namespace_root_dir($namespace)
  {
    return self::$_apps[$namespace];
  }

  //this is what receives the 'namespace/app-name' command to load the app.
  public static function run_app($name, $attrs)
  {
    $parts = explode('/', $name);
    $namespace = $parts[0];
    $app_name = $parts[1];


    if (!self::$_apps[$namespace])
      throw new Exception('Cannot run app. Namespace'.$namespace.' is not registered.');
    
    $app = new AxelradMercuryApp($namespace, $app_name, self::get_namespace_root_dir($namespace));
    $app->user_token(AxelradUserMgmt::get_user_token());
    $app->initialize();
    return $app->render($attrs);
  }
}

