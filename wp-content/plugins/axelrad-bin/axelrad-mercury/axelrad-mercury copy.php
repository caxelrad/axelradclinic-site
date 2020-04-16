<?php

require_once(__DIR__.'/../_common/simple_html_dom.php');

include 'axelrad-mercury-hooks.php';
include 'axelrad-mercury-shortcodes.php';
include 'axelrad-mercury-rest.php';

class AxelradMercury extends AxelradOsPlugin
{
  
  static $_dev_mode = true;
  static $_script = [];
  static $_ref_srcs = [];
  static $_instance_script = '';
  
  static $imported_namespaces = [];
  
  static $_runtime_path = '';
  static $_namespace = '';
  static $_app_name = '';
  static $_app = null;
  
  static $_child_elements = [];
  
  static $_model_name = '';
    
  public static $src_location = '';
  
  
  
  public static function init()
  {
  }
  
  //public static function get_model() { return self::$_model; }
  
  public static function app_ns() { return self::$_namespace; }
  
  public static function app_name() { return self::$_app_name; }
  
  static $_import_path = '';
  
  public static function set_import_source($path)
  {
    self::$_import_path = str_replace('\\', '/', $path);
  }
  
  static function import_source() { return self::$_import_path; }
  static function import_target() { return self::$_runtime_path.'/shared'; }
  
  //sets the root dir where app and dependencies are stored at runtime
  public static function set_app_path($path)
  {
    
    if (!file_exists($path))
      throw new Exception('cannot load app. '.$path.' does not exist.');
      
    self::$_runtime_path = _ax_slash_r(str_replace('\\', '/', $path));
    
    //if (!file_exists(self::$_runtime_path))
    //  mkdir(self::$_runtime_path, 0755);
    
    if (!file_exists(self::import_target()))
        mkdir(self::import_target(), 0755);
    
    $name = array_pop(explode('/', self::$_runtime_path));
    self::import_dependencies($path.'/'.$name.'.html');
  }
  
  static function runtime_target() { return self::$_runtime_path; }
    
  static function import_dependencies($ns_file_path)
  {
    //echo '<br>'.$ns_file_path;
    
    $ns_dom = str_get_html(file_get_contents($ns_file_path));
    $ns_name = str_replace('.html', '', array_pop(explode('/', $ns_file_path)));
    
    self::parse_references($ns_dom->firstChild(), $ns_name);
    
    $imports = $ns_dom->find('import');
    foreach ($imports as $import)
    { 
      $name = $import->getAttribute('namespace');
      
      //import the other dependencies... ignoring stuff that's already imported...
      if (!self::namespace_is_imported($name))
      {
        self::import_namespace($name);
        $child_file_path = self::import_target().'/'.$name.'/'.$name.'.html';
        self::import_dependencies($child_file_path);
      }
    }
  }
  
  
  public static function import_namespace($name)
  {
    $source_dir = self::import_source().'/'.$name;
    $target_dir = self::import_target().'/'.$name;
    
    if (!file_exists($source_dir))
      throw new Exception('The imported source directory '.$source_dir.' does not exist.');
    
    if (self::namespace_is_imported($name))
      throw new Exception('A directory named "'.$name.'" has already been imported.');
    
    self::$imported_namespaces[] = $name;
    
    self::_do_import($source_dir, $target_dir);
    self::load_namespace($name);
  }
  
  public static function namespace_is_imported($name)
  {
    return array_search($name, self::$imported_namespaces) !== false;
  }
  
  static function _do_import($source_dir, $target_dir)
  {
    if (file_exists($target_dir))
    {
      //rename the target dir - it's assumed we are making a fresh copy...      
      $archive_root = self::import_target().'/zzz';
      if (!file_exists($archive_root))
        mkdir($archive_root, 0755);
      
      $dir_name = array_pop(explode('/', $target_dir));
      $archive_dir = $archive_root.'/'.$dir_name;
      
      //delete the old rename dir if it exists
      if (file_exists($archive_dir))
        _ax_util_delete_dir_recursive($archive_dir);
      
      _ax_util_copy_dir_recursive($target_dir, $archive_dir);
    }
    
    _ax_util_copy_dir_recursive($source_dir, $target_dir);
  }
  
  
  public static function get_namespace_root($namespace)
  {
    if (self::namespace_is_imported($namespace))
      return self::import_target().'/'.$namespace;
    else 
      return self::runtime_target(); //.'/'.$namespace;
  }
  
  public static function get_namespace_virtual_root($namespace)
  {
    $root = self::get_namespace_root($namespace);
    //echo 'namespace root: '.$root;

    return AxelradOs::to_virtual_path($root);
  }
  
  public static function is_dev_mode() { return self::$_dev_mode; }
  
  static $component_constructors = [];
  
  public static function register_component_script($component_type, $component_name, $component_id)
  {
    $key = $component_name.'-'.$component_id;
    
    if (self::$_child_elements[$key]) return;
    self::$_child_elements[$key] = $key;

    self::$component_constructors[] = 
      'window.'.$component_id.' = 
        window.mercury.addComponent("'.$component_type.'", new '.$component_name.'("'.$component_id.'"));';
  }
  
  static $controller_constructors = [];
  
  public static function register_controller_script($component_name, $component_id)
  {
    $key = $component_name.'Controller-'.$component_id;
    
    if (self::$_child_elements[$key]) return;
    self::$_child_elements[$key] = $key;
    self::$controller_constructors[] = 'window.'.$component_id.'_controller = window.mercury.addController(
      new '.$component_name.'Controller("'.$component_id.'_controller"), "'.$component_id.'");';    
  }
  
  static $child_element_script = [];
  public static function register_child_element($parent_id, $child_id)
  {
    if ($parent_id == $child_id) return;
    
    if (self::$_child_elements[$child_id]) return;
    
    self::$_child_elements[$child_id] = $parent_id;
    self::$child_element_script[] = 'window.'.$parent_id.'.addChild("'.$child_id.'", window.'.$child_id.');';
  }
  
  static $component_source = [];
  public static function append_component_script($name, $code) 
  {
    if (!self::$_script[$name])
    {
      self::$component_source[] = $code.PHP_EOL;
      self::$_script[$name] = '1'; 
    }
  }
  
  static $controller_source = [];
  public static function append_controller_script($name, $code) 
  {
    if (!self::$_script[$name.'-controller'])
    {
      self::$controller_source[] = $code.PHP_EOL;
      self::$_script[$name.'-controller'] = '1'; 
    }
  }
  
  public static function append_instance_script($script)
  {
    self::$_instance_script .= $script.PHP_EOL;
  }
  
  public static function add_reference($src, $other_atts = []) 
  { 
    if (self::$_ref_srcs[$src]) return;
    
    self::$_ref_srcs[$src] = $other_atts;
  }
  
  
  static function set_references()
  {
    foreach (self::$_ref_srcs as $src => $attrs)
    {
      
      $q = '';
      if ($attrs['no-cache'])
        $q = '?t='.time();
      
      if (_ax_util_str_ends_with($src, '.css'))
        AxelradOs::append_css($src.$q, $attrs);
      else
        AxelradOs::append_script_src($src.$q, $attrs); 
    }
  }
  
   public static function set_current_namespace($namespace)
   {
     self::$_namespace = $namespace;
   }
  
  public static function load_namespace($namespace = '')
  {
    _ax_debug('loading namespace: '.$namespace);

    $file_path = AxelradMercuryFiles::get_namespace_file_path($namespace);
    _ax_debug($file_path);
    $ns_contents = file_get_contents($file_path);
    if ($ns_contents)
    {
      _ax_debug('loading namespace '.$namespace);
      $ns_dom = str_get_html($ns_contents);
      self::parse_references($ns_dom->firstChild(), $namespace);
    }
    else
      _ax_debug('namespace skipped no file contents.');
  }
  
  static $source_doc = null;
  
  public static function load_app($atts)
  {
    $name = $atts['name'];
    if (strpos($name, '/') === false)
      throw new Exception('no namespace specified for app '.$name.'.');
    
    $parts = explode('/', $name);
    self::set_current_namespace($parts[0]);
    self::$_app_name = $parts[1];
    
    _ax_debug('self::$_app_name = '.self::$_app_name);
    //load the global stuff...
    self::load_namespace();
    
    _ax_debug('namespace loaded.');

    $attr_string = '';
    foreach ($atts as $key => $value)
    {
      if ($key != 'id' && $key != 'name')
      {
        $attr_string.=' '.str_replace('_', '-', $key).'="'.$value.'"';
      }
    }
    
    //$app_html = self::get_component_contents('apps', self::$_app_name);
    $app_html = '<app name="'.self::$_app_name.'" id="'.self::$_app_name.'"'.$attr_string.'></app>';
    
    _ax_debug(htmlentities($app_html));

    self::$source_doc = str_get_html($app_html);
    self::parse(self::$source_doc->firstChild(), self::app_ns());
    
    self::set_references();

    _ax_debug('references set.');
    
    //self::load_operation_scripts();
    
    self::append_scripts(self::$component_source);
    self::append_scripts(self::$controller_source);
    //self::append_scripts(self::$operation_source);
    
    _ax_debug('scripts appended for components and controllers.');

    //first append the constructors
    self::append_scripts(self::$component_constructors);
    
    _ax_debug('constructors appended for components.');

    //then the child references
    self::append_scripts(self::$child_element_script);
    
    _ax_debug('child elements appended.');

    //then the controllers
    self::append_scripts(self::$controller_constructors);
    
    _ax_debug('controller constructors appended.');

    //then the operations
    //self::append_scripts(self::$operation_construtors);
    
    //then the other stuff
    AxelradOs::append_script_code(self::$_instance_script);
    
    $html = self::$source_doc->save();
    self::$source_doc->clear();
    self::$source_doc = null;
    return $html;
  }
  
  static function append_scripts($arr)
  {
    $scripts = array_reverse($arr);
    foreach ($scripts as $code)
    {
      AxelradOs::append_script_code($code.PHP_EOL);
    }
  }
  
  static function get_dir_name($node)
  {
    if ($node->tag == 'app')
      return 'apps';
    else if ($node->tag == 'view')
      return 'views';
    else if ($node->tag == 'component')
      return 'components';
    else
      return '';
  }
  
  static $_app_overrides = [];
  
  public static function parse($source_node, $source_ns = '')
  {
    //here's what we need to do...
    //1. generate all the component and view html and store in a hash
    //2. loop back thru and replace all the components and views in the html with tokens like {component_id}
    //3. Do a string replace on all the tokens out of the hash...
    
    
    $node_tag = $source_node->tag;
    $node_id = $source_node->getAttribute('id');
    $node_name = $source_node->getAttribute('name');
    $node_ns = $source_node->getAttribute('ns') ? $source_node->getAttribute('ns') : $source_ns;
    
    if ($node_ns == '')
      $node_ns = self::app_ns(); //always fall back on the app namespace
    
    
    $dir_name = self::get_dir_name($source_node);
    $component_name = $node_name;
    
    
    $component_html = self::get_component_contents($node_ns, $dir_name, $component_name);
    if ($component_html == 'no_contents')
      return; //means the file wasn't found or is empty
    
    $component_dom = str_get_html($component_html);
    
    if ($node_tag == 'app')
    {
      $overrides = $component_dom->firstChild()->find('override');
      if (count($overrides) > 0)
      {
        foreach ($overrides as $override)
        {
          //echo '<br>adding override: '.$override->getAttribute('name').' with '.$override->getAttribute('with');
          self::$_app_overrides[$override->getAttribute('name')] = $override->getAttribute('with');
        }
      }
    }
    
    
    if ($node_tag == 'view' && self::$_app_overrides[$node_ns.'/'.$node_name])
    {
      //overrides are in the app and always use local views
      $node_name = self::$_app_overrides[$node_ns.'/'.$node_name];
      $component_html = self::get_component_contents(self::app_ns(), 'views', $node_name);
      $component_dom = str_get_html($component_html);
      $node_ns = self::app_ns(); //switch to the proper ns so we can find the controller.
    }
    
    //load the needed references
    self::parse_references($component_dom, $node_ns);
    
    $component_content_dom = $component_dom->find('content', 0)->firstChild();

    
    //first things first... get the id, style, and class copied over.
    $node_class = $source_node->hasAttribute('class') ? $source_node->getAttribute('class') : '';
    $node_style = $source_node->hasAttribute('style') ? $source_node->getAttribute('style') : '';
    
    self::map_key_attrs($component_content_dom, $node_id, $node_style, $node_class);
    
    $pre_defined_component_attrs = self::attrlist_to_array($component_dom->firstChild()->find('attr'));
    
    $source_node_attr_values = self::node_attrs_to_array($source_node, ['id', 'class', 'style', 'name']);
    
    $merged_attrs = self::merge_attrs($source_node_attr_values, $pre_defined_component_attrs); //this overrides any default attrs with those defined in the source view/app html
    
    self::apply_attrs($merged_attrs, $component_content_dom);
    
    $all_component_attrs = $component_content_dom->attr;
    
    //handle some special cases....
    
    //here we will pull templates OR child nodes of the component and substitute them in before we start
    // parsing the children...
    $templates = $source_node->firstChild();
    if ($templates != null && $templates->tag == 'templates')
    {
      foreach ($templates->children() as $template)
      {
        $name = $template->getAttribute('name');
        if (!$name) $name = 'default'; 
        
        $template_content_dom = $template->firstChild();
        $template_content_dom->setAttribute('list-id', $node_id);
        
        
        $template_content_dom = self::replace_attr_tokens($all_component_attrs, $template_content_dom);
    
        $t_html = $template_content_dom->outertext; //self::apply_attrs($source_node, $component_dom, $template->innertext, 'component');
        
        AxelradMercury::append_instance_script('window.'.$node_id.'.addTemplate("'.$name.'", \''.$t_html.'\');');
      }
      
      $templates->outertext = ''; //remove it from the source
    }
    else
    {
      $children = $source_node->children();
      //any direct child nodes like... <buttons> or <items>... we need to see if they contain any components
      // and if so, pop their content into the dom, and refresh the dom
      if (count($children) > 0)
      {
        $child_html = $component_content_dom->outertext;
        foreach ($children as $child)
        {
          $child_name = $child->tag;
          $child_contents = $child->innertext;
          $child_html = str_replace('{'.$child_name.'}', $child_contents, $child_html);
        }
        
        //$component_content_dom->outertext = $child_html; //got it!
        
        //refresh the dom so added stuff can be selected
        $component_content_dom = str_get_html($child_html);
      }
    }
    
    
    $script = $component_dom->find('script', 0)->innertext;
    self::append_component_script($node_name, $script);  
    self::register_component_script($node_tag, $node_name, $node_id);
   
    $views = $component_content_dom->find('view');
    $components = $component_content_dom->find('component');
    
    
    foreach ($views as $view_node)
    {
      $ns = $view_node->getAttribute('ns');
      $id = str_replace('{id}', $node_id, $view_node->getAttribute('id')); 
      $view_node->setAttribute('id', $id);
      self::parse($view_node, $ns ? $ns : $node_ns);
      self::register_child_element($node_id, $view_node->getAttribute('id'));
    }
    
    foreach ($components as $cmp_node)
    {
      $id = str_replace('{id}', $node_id, $cmp_node->getAttribute('id')); 
      $ns = $cmp_node->getAttribute('ns');
      $cmp_node->setAttribute('id', $id);
      self::parse($cmp_node, $ns ? $ns : $node_ns);
      self::register_child_element($node_id, $cmp_node->getAttribute('id'));
    }
    
    $controller = self::get_controller_contents($node_ns, $node_name);
    if ($controller != '')
    {
      self::register_controller_script($node_name, $node_id); 
      self::append_controller_script($node_name, $controller);
    }
    
    if ($source_node->tag == 'app')
    {
      self::append_instance_script('mercury.namespace = "'.self::app_ns().'";');
      
      $model_name = $component_dom->firstChild()->getAttribute('model');
      if ($model_name)
      {
        self::$_model_name = $model_name;
        self::add_reference('https://'.$_SERVER['HTTP_HOST'].AxelradMercuryFiles::get_namespace_virtual_root().'/models/'.$model_name.'.js?t='.time());
        self::append_instance_script('mercury.model = new '.$model_name.'("'.$model_name.'");');
        self::append_instance_script('mercury.model.baseUrl = "https://'.$_SERVER['HTTP_HOST'].'";');
      }
    }
    
    //$attrs = $source_node->attr;
    
    //self::apply_styles($source_node, $component_content_dom->firstChild());
    $component_content_dom = self::replace_attr_tokens($all_component_attrs, $component_content_dom);
    
    self::map_component_properties($node_id, $component_content_dom);
    $source_node->outertext = $component_content_dom->outertext; //$html;
    
  }
  
  static function map_key_attrs($target, $id, $style, $class)
  {
    
    if($target->hasAttribute('style'))
      $style = $target->getAttribute('style').' '.$style;
    
    if($target->hasAttribute('class'))
      $class = $target->getAttribute('class').' '.$class;
    
    $target->setAttribute('id', $id);
    $target->setAttribute('style', $style);
    $target->setAttribute('class', $class);
  }
  static function append_attr_values($source, $target)
  {
    //used for class and style only
    foreach ($source as $name => $value)
    {
      if ($target[$name])
      {
        $target[$name] = $target[$name].' '.$value;
      }
    }
  }
  
  static function merge_attrs($source, $target)
  {
    //override any attrs in the target with values from the source...
    foreach ($source as $name => $value)
    {
      $target[$name] = $value;
    }
    
    return $target;
  }
  
  static function node_attrs_to_array($node, $exclude_names = [])
  {
    $attr_array = [];
    foreach ($node->attr as $name => $value)
    {
      if (array_search($name, $exclude_names) === false)
        $attr_array[$name] = $value;
    }
    return $attr_array;
  }
  
  static function attrlist_to_array($source, $exclude_names = [])
  {
    $attr_array = [];
    foreach ($source as $node)
    {
      $name = $node->getAttribute('name');
      if (array_search($name, $exclude_names) === false)
        $attr_array[$name] = $node->getAttribute('value');
    }
    return $attr_array;
  }
  
  static $_mapped_props = []; //this is a hack 
  
  static function map_component_properties($component_id, $component_dom)
  {
    if (self::$_mapped_props[$component_id] == true)
      return; //THIS IS A TOTAL HACK BUT FOR NOW IT SHOULD WORK
    
    self::$_mapped_props[$component_id] = true;
    
    $prop_nodes = $component_dom->find('[prop-name]');
        
    foreach ($prop_nodes as $prop_node)
    {
      $node_id = $prop_node->getAttribute('id');
      $prop_name = $prop_node->getAttribute('prop-name');
      
      $tag = $prop_node->tag;
      $method = 'html';
      if ($tag == 'input' || $tag == 'select' || $tag == 'textarea')
        $method = 'val';
      
      self::$registered_script_attrs[$node_id.'-'.$prop_name] = true;
      
      self::append_instance_script('mercury.setMappedProp("'.$component_id.'", "'.$tag.'", "'.$node_id.'", "'.$prop_name.'");');      
      $prop_node->removeAttribute('prop-name');
    }
  }
  
  static $registered_script_attrs = [];
  
  static function set_attribute_script_property($component_client_id, $attr_name, $attr_value)
  {
//     if ($attr_name == 'id' || $attr_name == 'class' || $attr_name == 'style' || $attr_name == 'name')
//       return;
    
//     if (self::$registered_script_attrs[$component_client_id.'-'.$attr_name]) return;
    
//     self::$registered_script_attrs[$component_client_id.'-'.$attr_name] = true;
    
//     $name = _ax_util_to_camel_case($attr_name);
//     self::append_instance_script('mercury.setAttrProp("'.$component_client_id.'", "'.$name.'", "'.$attr_value.'");');
  }
  
  static function get_document($node)
  {
    $parent = $node->parent();
    
    while ($parent != null)
    {
      $node = $parent;
      $parent = $node->parent();
    }
    return $node;
  }
  
  static function apply_attrs($source_attrs, $target)
  {
    foreach ($source_attrs as $name => $value)
    {
      //echo '<br>setting attr '.$name.' = '.$value.' on '.$target->getAttribute('id');
      $target->setAttribute($name, $value);
    }
  }
  
  /* static function get_script()
  {
    $s = '';
    
    foreach (self::$_script_srcs as $key => $value)
    {
      $s.='<script type="text/javascript" src="'.$value.'"></script>';
    }
    return $s;
  } */
  
  static $_component_scripts = [];
  static $_component_html = [];
  
  
  public static function get_component_contents($namespace, $dir_name, $name)
  {
    $file_path = AxelradMercuryFiles::get_component_file_path($namespace, $dir_name, $name);
    if (!file_exists($file_path))
    {
      return 'no_contents';
      //throw new Exception('The html file for component '.$name.' does not exist in '.$file_path.'.');
    }

    return file_get_contents($file_path);
  }
  
  public static function get_controller_contents($namespace, $name)
  {
    $file_path = AxelradMercuryFiles::get_component_file_path($namespace, 'controllers', $name.'Controller', 'js');
    if (!file_exists($file_path))
    {
      //throw new Exception('The js controller file for component '.$name.' does not exist in '.$file_path.'.');
      return '';
    }

    return file_get_contents($file_path);
  }
  
//   static function get_custom_parser_path($dir_name, $name)
//   {
//     $file_path = AxelradMercuryFiles::get_component_file_path($dir_name, $name);
//     $file_path = str_replace('.html', 'Parser.php', $file_path);
//     if (file_exists($file_path))
//       return $file_path;
    
//     return '';
//   }
 
  static function dom_attr_to_array($attr)
  {
    
  }
  
  static function parse_references($dom_node, $namespace = '')
  {
    $reference_nodes = $dom_node->find('ref');
    if (!$reference_nodes) return;
    
    $namespace = $namespace ? $namespace : self::app_ns();
    
    foreach ($reference_nodes as $ref)
    {
      //echo 'adding ref: '.$ref->getAttribute('src');
      $additional_attrs = [];
      if ($ref->attr)
      {
        $attrs = $ref->attr;
        foreach ($attrs as $attr_name => $attr_value)
        {
          if ($attr_name != 'ns' && $attr_name != 'name')
            $additional_attrs[$attr_name] = $attr_value;
        }
      }
      
      $src = $ref->getAttribute('src');
      if ($ref->getAttribute('name'))
      {
        $ns = $ref->getAttribute('ns') ? $ref->getAttribute('ns') : $namespace;
        $src = self::get_namespace_virtual_root($ns).'/assets/'.$ref->getAttribute('name');
      }
      
      self::add_reference($src, $additional_attrs);
    }
  }
  
  public static function map_attr_values($attrs, $target)
  {
    $id = $attrs['id'];
    $class = $attrs['class'];
    $style = $attrs['style'];
    
    if ($class)
    {
      if ($target->hasAttribute('class'))
        $class = $target->getAttribute('class').' '.$class;
      
      $target->setAttribute('class', $class);
    }
    
    if ($style)
    {
      if ($target->hasAttribute('style'))
        $style = $target->getAttribute('style').' '.$style;
      
      $target->setAttribute('style', $style);
    }
    
    foreach ($attrs as $name => $value)
    {
      $value = str_replace('{id}', $id, $value);
      $value = str_replace('{class}', $class, $value);
      $value = str_replace('{style}', $style, $value);
      $target->setAttribute($name, $value);
    }
  }
  
  public static function replace_attr_tokens($attrs, $target)
  {
    $html = $target->outertext;
    foreach ($attrs as $name => $value)
    {
      $html = str_replace('{'.$name.'}', $value, $html);
    }
    
    //$target->innertext = $html;
    
    return str_get_html($html);
  }
}

class AxelradMercuryFiles
{ 
  
  public static function get_namespace_root($namespace = '')
  {
    return AxelradMercury::get_namespace_root($namespace ? $namespace : AxelradMercury::app_ns());
  }
  
  public static function get_namespace_virtual_root($namespace = '')
  {
    return AxelradMercury::get_namespace_virtual_root($namespace ? $namespace : AxelradMercury::app_ns());
  }
  
  
  public static function get_namespace_file_path($namespace = '')
  {
    $namespace = $namespace ? $namespace : AxelradMercury::app_ns();
    return self::get_namespace_root($namespace).'/'.$namespace.'.html';
  }
  
  public static function get_component_file_path($namespace, $dir_name, $name, $ext = 'html')
  {
    return self::get_namespace_root($namespace).'/'.$dir_name.'/'.$name.'.'.$ext;
  }
  
  /* static function to_virtual_path($file_path)
  {
    return str_replace(APP_DIR, '', $file_path);
  } */
}
