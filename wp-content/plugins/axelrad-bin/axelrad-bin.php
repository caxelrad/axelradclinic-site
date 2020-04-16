<?php 

/*
Plugin Name: Axelrad Common Repo
Plugin URI:
Version: 1.1
Author: Chris Axelrad
Description: Common code used by my plugins
*/


define('AX_BIN_REPO', 'd:\\xampp\\_webroot\\_axelrad-bin'); //this tells it where to grab source from 
//define('AX_BIN_DEV_MODE', false);  //tells the bin to grab the latest code from repo - turn this off in production mode

require_once('axelrad-debug/axelrad-debug.php');
require_once('axelrad-util/axelrad-util.php');

class AxBin
{
    static $_repo_imported = false;

    static $_build = [];

    static $_build_all = false;

    public const BUILD_ALL = 'all';

    //if AX_BIN_DEV_MODE is true, this allows turning off the copying of some stuff to speed things up / optimize dev time
    public static function build_module($module_name)
    {
        if ($module_name == self::BUILD_ALL)
            self::$_build_all = true;
        else
            self::$_build[$module_name] = true;
    }

    
    public static function load($name)
    {
        self::check_repo();
        require_once(__DIR__.'/'.$name.'/'.$name.'.php');
    }

    static function check_repo()
    {
        if (AxelradUtil::is_rest()) return; //do not do the build with rest requests..
        if (is_admin()) return;
        

        if (self::$_repo_imported == false)
        {
            self::$_repo_imported = true;

            $items = scandir(AX_BIN_REPO);
            foreach ($items as $item)
            {
                if (self::$_build_all === true || self::$_build[$item] === true)
                {
                    if ($item != '.' && $item != '..')
                    {
                        $src_path = AX_BIN_REPO.'/'.$item;
                        if (is_dir($src_path))
                        {
                            $dest_path = __DIR__.'/'.$item;
                            self::import_repo($src_path, $dest_path);
                        }
                    }
                }
            }
        }
    }

    static function import_repo($src, $dst) 
    {
        $dir = opendir($src);
        @mkdir($dst);
        while(( $file = readdir($dir)) ) 
        {
            if (( $file != '.' ) && ( $file != '..' )) 
            {
                if ( is_dir($src . '/' . $file) ) 
                {
                    AxBin::import_repo($src .'/'. $file, $dst .'/'. $file);
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
}

