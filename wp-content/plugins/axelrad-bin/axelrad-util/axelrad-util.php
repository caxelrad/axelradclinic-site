<?php

class AxelradUtil
{
    public static $load_bootstrap = false;

    static $_is_rest = null;
    public static function is_rest()
    {
        if (self::$_is_rest == null)
        {
            $url = $_SERVER['REQUEST_URI'];
            self::$_is_rest = strpos($url, '/wp-json/') !== false;
        }
        return self::$_is_rest;
    }

    public static function try_method($class_name, $method_name)
    {
        try
        {
            if (class_exists($class_name)
            && method_exists($class_name, $method_name))
            $class_name::$method_name();
        
            AxLogger::write($class_name.' method '.$method_name.' succeeded');
        }
        catch (Exception $ex)
        {
            AxLogger::write_error(__FILE__, $class_name.'::'.$method_name, $ex->getMessage());
        }
    }

    public static function site_name()
    {
        return get_bloginfo('name');
    }
    
    public static function get_full_url($slug_or_url)
    {
        if (strpos($slug_or_url, 'https://') === -1)
        return get_home_url().$slug_or_url;
        else 
        return $slug_or_url;
    }

    static $_virtual_paths = [];
    public static function to_virtual_path($full_path)
    {
        if (self::$_virtual_paths[$full_path] == null)
        {
            $the_path = str_replace('\\', '/', $full_path);
            
            _ax_debug('to_virtual_path '.$the_path);

            $index = strpos($the_path, '/wp-content');
            $v_path = substr($the_path, $index);
            self::$_virtual_paths[$full_path] = $v_path;
            return $v_path;
        }

        return self::$_virtual_paths[$full_path];
    }

    static $_scripts = [];
    static $_css = [];
    
    public static function get_class_name($component_name)
    {
        $name_parts = explode('-', $component_name);
        $class_name = '';
        foreach ($name_parts as $part)
        {
        $class_name.=ucfirst($part);
        }
        return $class_name;
    }
    
    static $_script_code = '';
    static $_script_src = [];
    static $_css_src = [];
    
    public static function append_script_code($code)
    {
        self::$_script_code.=$code;
    }
    
    public static function append_script_src($src, $attrs = [])
    {
        self::$_script_src[$src] = $attrs;
    }
    
    public static function append_css($src, $attrs = [])
    {
        self::$_css_src[$src] = $attrs;
    }
    
    public static function add_script($path, $dep = [], $footer = false)
    {
        
        $name = substr($path, strrpos($path, '/') + 1);
        self::$_scripts[] = ['name' => $name, 'path' => $path, 'dep' => $dep, 'footer' => $footer];
    }
    
    public static function add_css($path, $dep = [])
    {
        $name = substr($path, strrpos($path, '/') + 1);
        self::$_css[$name] = ['path' => $path, 'dep' => $dep];
    }

    static $_dereg_scripts = [];
    static $_dereg_css = [];
    
    public static function remove_script($name)
    {
        self::$_dereg_scripts[] = $name;
    }
    
    public static function remove_css($name)
    {
        self::$_dereg_css[] = $name;
    }
    
    public static function ensure_scripts($app_name, $overwrite = false)
    {
        $source_dir = __DIR__.'/../'.$app_name.'/scripts';
        
        if (!file_exists($source_dir)) return; //no scripts for this app


        $files = scandir($source_dir);
        foreach ($files as $file_name)
        {
            if (_ax_util_str_ends_with($file_name, '.js'))
            {
                self::add_script(self::to_virtual_path($source_dir.'/'.$file_name));
            }
        }
    }
    
    static function get_script_handle($path)
    {
        //pull the file name off the path
        $filename = substr($path, strrpos($path, '/') + 1);
        return str_replace('.', '-', $filename);
        
    }
    
    public static function enqueue_scripts()
    {
        foreach (self::$_dereg_scripts as $name)
        {
            wp_deregister_script($name);
        }
        
        foreach (self::$_dereg_css as $name)
        {
            wp_dequeue_style($name);
        }
        
        foreach (self::$_css as $name => $value)
        {
            self::append_css($value['path']);
        }
        
        foreach (self::$_scripts as $value)
        {
            self::append_script_src($value['path'].'?t='.time());
        }
    }

    static function attrs_to_string($attrs, $all = false)
    {
        $a = '';
        foreach ($attrs as $name => $value)
        {
        if (!$all && $name != 'src' && $name == 'location')
            $a = $a.' '.$name.'="'.$value.'"';
        }
        
        return $a;
    }
    
    public static function footer()
    {
        $f = '';
        
        foreach (self::$_css_src as $src => $attrs)
        {
        if ($attrs['location'] == '' || $attrs['location'] == 'footer')
        {
            $a = self::attrs_to_string($attrs);
            $f.= PHP_EOL.'<link rel="stylesheet" href="'.$src.'" type="text/css" media="all"'.$a.'/>';
        }
        }
        
        foreach (self::$_script_src as $src => $attrs)
        {
        if ($attrs['location'] == '' || $attrs['location'] == 'footer')
        {
            $a = self::attrs_to_string($attrs);
            $f.= PHP_EOL.'<script type="text/javascript" src="'.$src.'"'.$a.'></script>';
        }
        }
        
        if (self::$_script_code)
        $f.= PHP_EOL.'<script type="text/javascript">'.self::$_script_code.'</script>';
        
        return $f;
    }

    public static function email_is_valid($email)
    {
        if ($email == '') return false;
        
        // Validate e-mail
        return filter_var(filter_var($email, FILTER_SANITIZE_EMAIL), 
            FILTER_VALIDATE_EMAIL);
        
    }
}


class AxSettings
{
    static function get_bean($domain, $key)
    {
        _ax_debug('AxSettings::get_bean '.$domain.' '.$key);

        $settings = R::find('settings', 'domain_name = ? AND key_name = ?', [$domain, $key]);
        if (count($settings) == 1)
            return $settings[0];
        
        return null;
    }

    public static function put($domain, $key, $value)
    {
        $setting = self::get_bean($domain, $key);
        if ($setting)
        {
            $setting->value = $value;
            R::store($setting);
        }
        else 
        {
            $setting = R::dispense('settings');
            $setting->domain_name = $domain;
            $setting->key_name = $key;
            $setting->value = $value;
            R::store($setting);
        }

        unset(self::$_cached[$domain.'-'.$key]);
    }

    static $_cached = [];

    public static function get($domain, $key, $default_if_not_found = null)
    {
        if (!self::$_cached[$domain.'-'.$key])
        {
            $setting = self::get_bean($domain, $key);
            if ($setting)
                self::$_cached[$domain.'-'.$key] = $setting->value;
            else
                self::$_cached[$domain.'-'.$key] = $default_if_not_found;
        }
        return self::$_cached[$domain.'-'.$key];
    }

    public static function page_is_child_of($page_id, $parent_id)
    {
        $ancestors = get_ancestors($page_id, 'page');
        _ax_debug('ancestors are: '.implode(' - ', $ancestors));
        return array_search($parent_id, $ancestors) !== false;
    }
}

include 'util-legacy.php';
include 'util-ui-shortcodes.php';
include 'axelrad-util-hooks.php';