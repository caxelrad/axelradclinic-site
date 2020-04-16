<?php
class AxelradMercuryApp
{
  
  private $_dev_mode = true;
  private $_script = [];
  private $_ref_srcs = [];
  private $_instance_script = '';
  
  private $imported_namespaces = [];
  
  private $_runtime_path = '';
  private $_namespace = '';
  private $_app_name = '';
  //private $_app = null;
  
  private $_child_elements = [];
  
  //private $_namespace_file_path = '';

  private $_model_name = '';
    
  private $_root_dir = '';
  
  public function app_ns() { return $this->_namespace; }
  
  public function app_name() { return $this->_app_name; }
  
  private $_placeholder_views = []; //this is the stuff that gets plugged into placeholders in various places in apps / views

  private $_import_path = '';

  private $_user_token = '';

  function __construct($namespace, $app_name, $src_dir)
  {
    _ax_debug('loading app '.$namespace.'/'.$app_name.' inside dir '.$src_dir);
    $this->_namespace = $namespace;
    $this->_app_name = $app_name;
    $this->_root_dir = $src_dir; //the PARENT of the application directory (i.e. '/my-plugin/ui')
  }

  public function initialize()
  {
    if (!file_exists(self::import_target()))
        mkdir(self::import_target(), 0755);
    
    $this->import_dependencies($this->_namespace);
  }

  public function user_token($token)
  {
    $this->_user_token = $token;
  }

  function runtime_dir() { return $this->_root_dir.'/'.$this->_namespace; }
  
  function get_namespace_file_path($namespace = '') 
  { 
    
    $ns = $namespace == '' ? $this->_namespace : $namespace;
    _ax_debug('get_namespace_file_path for '.$ns);
    _ax_debug('$this->_root_dir = '.$this->_root_dir);

    $root = $this->get_namespace_root($ns);
    return $root.'/'.$ns.'.html'; 
  }
  
  public function get_namespace_root($namespace = '')
  {
    if ($namespace == '' || $namespace == $this->_namespace)
        return $this->runtime_dir();
    else 
        return $this->import_target().'/'.$namespace;
  }
  
  public function get_namespace_virtual_root($namespace = '')
  {
    $ns = $namespace == '' ? $this->_namespace : $namespace;
    $root = $this->get_namespace_root($ns);
    return AxelradUtil::to_virtual_path($root);
  }

  function import_source() { return AxelradMercuryLoader::$repository; }
  function import_target() { return $this->runtime_dir().'/imported'; }

  function import_dependencies($namespace)
  {
    
    $file_path = $this->get_namespace_file_path($namespace);
    $ns_dom = str_get_html(file_get_contents($file_path));
   
    $this->parse_references($ns_dom->firstChild(), $namespace);
    
    $imports = $ns_dom->find('import');
    foreach ($imports as $import)
    { 
      $imported_namespace = $import->getAttribute('namespace');
      
      //import the other dependencies... ignoring stuff that's already imported...
      if (!$this->namespace_is_imported($imported_namespace))
      {
        $this->import_namespace($imported_namespace);
        $child_file_path = $this->import_target().'/'.$imported_namespace.'/'.$imported_namespace.'.html';
        $this->import_dependencies($imported_namespace);
      }
    }

    $exports = $ns_dom->find('export');
    foreach ($exports as $export)
    { 
      $target_placholder_id = $export->getAttribute('target');
      if (!$this->_placeholder_views[$target_placholder_id])
        $this->_placeholder_views[$target_placholder_id] = [];
      
      $this->_placeholder_views[$target_placholder_id][] = 
        [
          'ns' => $namespace,
          'view' => $export->getAttribute('source')
        ];
    }
  }
  
  function get_placeholder_content($placeholder_id)
  {
    $sources = $this->_placeholder_views[$placeholder_id];

    if ($sources == null)
      return [];
    
    return $sources;
    
  }

  public function import_namespace($namespace)
  {
    $source_dir = $this->import_source().'/'.$namespace;
    $target_dir = $this->import_target().'/'.$namespace;
    
    if (!file_exists($source_dir))
      throw new Exception('The imported source directory '.$source_dir.' does not exist.');
    
    if ($this->namespace_is_imported($namespace))
      throw new Exception('A directory named "'.$namespace.'" has already been imported.');
    
    $this->imported_namespaces[] = $namespace;
    
    $this->_do_import($source_dir, $target_dir);
    $this->load_namespace($namespace);
  }
  
  public function namespace_is_imported($namespace)
  {
    return array_search($namespace, $this->imported_namespaces) !== false;
  }
  
  function _do_import($source_dir, $target_dir)
  {
    if (file_exists($target_dir))
    {
      _ax_util_delete_dir_recursive($target_dir);
    }
    
    _ax_util_copy_dir_recursive($source_dir, $target_dir);
  }
  
  
  public function is_dev_mode() { return $this->_dev_mode; }
  
  private $component_constructors = [];
  
  public function register_component_script($component_type, $component_name, $component_id)
  {
    //echo '<br>register_component_script: '.$component_type.', '.$component_name.'. '.$component_id;
    $key = $component_name.'-'.$component_id;
    
    if ($this->_child_elements[$key]) return;
    $this->_child_elements[$key] = $key;

    $this->component_constructors[] = 'window.mercury.addComponent("'.$component_type.'", "'.$component_name.'", "'.$component_id.'");';
  }
  
  private $controller_constructors = [];
  
  public function register_controller_script($component_name, $component_id)
  {
    $key = $component_name.'Controller-'.$component_id;
    
    if ($this->_child_elements[$key]) return;
    $this->_child_elements[$key] = $key;
    $this->controller_constructors[] = 'window.mercury.addController("'.$component_name.'", "'.$component_id.'");';
  }
  
  private $child_element_script = [];
  public function register_child_element($parent_id, $child_id)
  {
    if ($parent_id == $child_id) return;
    
    if ($this->_child_elements[$child_id]) return;
    
    $this->_child_elements[$child_id] = $parent_id;
    $this->child_element_script[] = 'window.mercury.addChild("'.$parent_id.'", "'.$child_id.'");';
  }
  
  private $component_source = [];
  public function append_component_script($name, $code) 
  {
    if (!$this->_script[$name])
    {
      $this->component_source[] = $code.PHP_EOL;
      $this->_script[$name] = '1'; 
    }
  }
  
  private $controller_source = [];
  public function append_controller_script($name, $code) 
  {
    if (!$this->_script[$name.'-controller'])
    {
      $this->controller_source[] = $code.PHP_EOL;
      $this->_script[$name.'-controller'] = '1'; 
    }
  }
  
  public function append_instance_script($script)
  {
    $this->_instance_script .= $script.PHP_EOL;
  }
  
  public function add_reference($src, $other_atts = []) 
  { 
    if ($this->_ref_srcs[$src]) return;
    
    $this->_ref_srcs[$src] = $other_atts;
  }
  
  
  function set_references()
  {
    foreach ($this->_ref_srcs as $src => $attrs)
    {
      
      $q = '';
      if ($attrs['no-cache'])
        $q = '?t='.time();
      
      if (_ax_util_str_ends_with($src, '.css'))
        AxelradUtil::append_css($src.$q, $attrs);
      else
        AxelradUtil::append_script_src($src.$q, $attrs); 
    }
  }

  public function get_component_file_path($namespace, $dir_name, $name, $ext = 'html')
  {
    return $this->get_namespace_root($namespace).'/'.$dir_name.'/'.$name.'.'.$ext;
  }
  
  
  public function load_namespace($namespace = '')
  {
    if ($namespace == '')
      $namespace = $this->_namespace;

    _ax_debug('loading namespace: '.$namespace);

    $file_path = $this->get_namespace_file_path($namespace);
    _ax_debug('file_path = '.$file_path);
    $ns_contents = file_get_contents($file_path);
    if ($ns_contents)
    {
      _ax_debug('loading namespace '.$namespace);
      $ns_dom = str_get_html($ns_contents);
      $this->parse_references($ns_dom->firstChild(), $namespace);
    }
    else
      _ax_debug('namespace skipped no file contents.');
  }
  
  private $source_doc = null;
  
  public function render($attrs = [])
  {
    
    
    _ax_debug('$this->_app_name = '.$this->_app_name);
    //load the global stuff...
    $this->load_namespace();
    
    _ax_debug('namespace loaded.');

    $attr_string = '';
    foreach ($attrs as $key => $value)
    {
      if ($key != 'id' && $key != 'name')
      {
        $attr_string.=' '.str_replace('_', '-', $key).'="'.$value.'"';
      }
    }
    
    //$app_html = $this->get_component_file_contents('apps', $this->_app_name);
    $app_html = '<app name="'.$this->_app_name.'" id="'.$this->_app_name.'"'.$attr_string.'></app>';
    
    _ax_debug(htmlentities($app_html));

    $this->source_doc = str_get_html($app_html);
    $this->parse($this->source_doc->firstChild(), $this->app_ns());
    
    $this->set_references();

    _ax_debug('references set.');
    
    $this->append_scripts($this->component_source);
    $this->append_scripts($this->controller_source);
    
    _ax_debug('scripts appended for components and controllers.');

    //first append the constructors
    $this->append_scripts($this->component_constructors);
    
    _ax_debug('constructors appended for components.');

    //then the child references
    $this->append_scripts($this->child_element_script);
    
    _ax_debug('child elements appended.');

    //then the controllers
    $this->append_scripts($this->controller_constructors);
    
    _ax_debug('controller constructors appended.');

    //then the operations
    //$this->append_scripts($this->operation_construtors);
    
    //then the other stuff
    AxelradUtil::append_script_code($this->_instance_script);
    
    $html = $this->source_doc->save();
    $this->source_doc->clear();
    $this->source_doc = null;
    return $html;
  }
  
  function append_scripts($arr)
  {
    $scripts = array_reverse($arr);
    foreach ($scripts as $code)
    {
      AxelradUtil::append_script_code($code.PHP_EOL);
    }
  }
  
  function get_dir_name($node)
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
  
  private $_app_overrides = [];
  
  public function parse($source_node, $source_ns = '')
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
      $node_ns = $this->app_ns(); //always fall back on the app namespace
    
    
    $dir_name = $this->get_dir_name($source_node);
    $component_name = $node_name;
    
    
    $component_html = $this->get_component_file_contents($node_ns, $dir_name, $component_name);
    if ($component_html == 'no_contents')
      return; //means the file wasn't found or is empty
    
    $component_dom = str_get_html($component_html);
    
    if ($node_tag == 'app')
    {
      //$node_id = $node_name.'_app';
      $overrides = $component_dom->firstChild()->find('override');
      if (count($overrides) > 0)
      {
        foreach ($overrides as $override)
        {
          //echo '<br>adding override: '.$override->getAttribute('name').' with '.$override->getAttribute('with');
          $this->_app_overrides[$override->getAttribute('name')] = $override->getAttribute('with');
        }
      }
    }
    
    
    if ($node_tag == 'view' && $this->_app_overrides[$node_ns.'/'.$node_name])
    {
      //overrides are in the app and always use local views
      $node_name = $this->_app_overrides[$node_ns.'/'.$node_name];
      $component_html = $this->get_component_file_contents($this->app_ns(), 'views', $node_name);
      $component_dom = str_get_html($component_html);
      $node_ns = $this->app_ns(); //switch to the proper ns so we can find the controller.
    }
    
    
    //load the needed references
    $this->parse_references($component_dom, $node_ns);
    
    $component_content_dom = $component_dom->find('content', 0)->firstChild();

    
    //first things first... get the id, style, and class copied over.
    $node_class = $source_node->hasAttribute('class') ? $source_node->getAttribute('class') : '';
    $node_style = $source_node->hasAttribute('style') ? $source_node->getAttribute('style') : '';
    
    $this->map_key_attrs($component_content_dom, $node_id, $node_style, $node_class);
    
    $pre_defined_component_attrs = $this->attrlist_to_array($component_dom->firstChild()->find('attr'));
    
    $source_node_attr_values = $this->node_attrs_to_array($source_node, ['id', 'class', 'style', 'name']);
    
    $merged_attrs = $this->merge_attrs($source_node_attr_values, $pre_defined_component_attrs); //this overrides any default attrs with those defined in the source view/app html
    
    $this->apply_attrs($merged_attrs, $component_content_dom);
    
    $all_component_attrs = $component_content_dom->attr;
    
    //handle some special cases....
    
    //here we will pull templates OR child nodes of the component and substitute them in before we start
    // parsing the children...
    $next_child = $source_node->firstChild();
    if ($next_child != null && $next_child->tag == 'templates')
    {
      foreach ($next_child->children() as $template)
      {
        $name = $template->getAttribute('name');
        if (!$name) $name = 'default'; 
        
        $template_content_dom = $template->firstChild();
        $template_content_dom->setAttribute('list-id', $node_id);
        
        
        $template_content_dom = $this->replace_attr_tokens($all_component_attrs, $template_content_dom);
    
        $t_html = $template_content_dom->outertext; //$this->apply_attrs($source_node, $component_dom, $template->innertext, 'component');
        
        $this->append_instance_script('window.'.$node_id.'.addTemplate("'.$name.'", \''.$t_html.'\');');
      }
      
      $next_child->outertext = ''; //remove it from the source
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
    
    $placeholders = $component_content_dom->find('placeholder');

    if (count($placeholders) > 0)
    {
      foreach ($placeholders as $ph)
      {
        $source_html = '';
        $sources = $this->get_placeholder_content($ph->getAttribute('id'));
        foreach ($sources as $source)
        {
          $source_html .= '<view name="'.$source['view'].'" ns="'.$source['ns'].'"></view>';
        }
        $ph->outertext = $source_html; //replace the placeholder with the html specified, if any
      }
      //refresh the dom again so placholder content gets loaded and is parseable
      $component_content_dom = str_get_html($component_content_dom->outertext);
    }
    
    $script = $component_dom->find('script', 0)->innertext;
    $this->append_component_script($node_name, $script);  
    $this->register_component_script($node_tag, $node_name, $node_id);
   
    $views = $component_content_dom->find('view');
    $components = $component_content_dom->find('component');
    
    
    foreach ($views as $view_node)
    {
      $ns = $view_node->getAttribute('ns');
      $id = str_replace('{id}', $node_id, $view_node->getAttribute('id')); 
      $view_node->setAttribute('id', $id);
      $this->parse($view_node, $ns ? $ns : $node_ns);
      $this->register_child_element($node_id, $view_node->getAttribute('id'));
    }
    
    foreach ($components as $cmp_node)
    {
      $id = str_replace('{id}', $node_id, $cmp_node->getAttribute('id')); 
      $ns = $cmp_node->getAttribute('ns');
      $cmp_node->setAttribute('id', $id);
      $this->parse($cmp_node, $ns ? $ns : $node_ns);
      $this->register_child_element($node_id, $cmp_node->getAttribute('id'));
    }
    
    $controller = $this->get_controller_contents($node_ns, $node_name);
    if ($controller != '')
    {
      $this->register_controller_script($node_name, $node_id); 
      $this->append_controller_script($node_name, $controller);
    }
    
    if ($source_node->tag == 'app')
    {
      $this->append_instance_script('mercury.namespace = "'.$this->app_ns().'";');
      $this->append_instance_script('mercury.token = "'.$this->_user_token.'";');
      $this->append_instance_script('mercury.token_param = "'.AxelradUserMgmt::USER_TOKEN_QUERY_PARAM.'";');
      
      
      $model_name = $component_dom->firstChild()->getAttribute('model');
      if (!$model_name) $model_name = $node_name.'Model';
      if ($model_name)
      {
        $this->_model_name = $model_name;
        $this->add_reference('https://'.$_SERVER['HTTP_HOST'].$this->get_namespace_virtual_root().'/models/'.$model_name.'.js?t='.time());
        $this->append_instance_script('mercury.model = new '.$model_name.'("'.$model_name.'");');
        $this->append_instance_script('mercury.model.baseUrl = "https://'.$_SERVER['HTTP_HOST'].'";');
      }
    }
    
    //$attrs = $source_node->attr;
    
    //$this->apply_styles($source_node, $component_content_dom->firstChild());
    $component_content_dom = $this->replace_attr_tokens($all_component_attrs, $component_content_dom);
    
    $this->map_component_properties($node_id, $component_content_dom);
    $source_node->outertext = $component_content_dom->outertext; //$html;
    
  }
  
  private $_placeholder_content = [];

  function register_placeholder_content($placeholder_id, $content)
  {
    if ($this->_placeholder_content[$placeholder_id] == null)
    {
      $this->_placeholder_content[$placeholder_id] = [];
    }

    $this->_placeholder_content[$placeholder_id][] = $content;
  }

  function map_key_attrs($target, $id, $style, $class)
  {
    
    if($target->hasAttribute('style'))
      $style = $target->getAttribute('style').' '.$style;
    
    if($target->hasAttribute('class'))
      $class = $target->getAttribute('class').' '.$class;
    
    $target->setAttribute('id', $id);
    $target->setAttribute('style', $style);
    $target->setAttribute('class', $class);
  }

  function append_attr_values($source, $target)
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
  
  function merge_attrs($source, $target)
  {
    //override any attrs in the target with values from the source...
    foreach ($source as $name => $value)
    {
      $target[$name] = $value;
    }
    
    return $target;
  }
  
  function node_attrs_to_array($node, $exclude_names = [])
  {
    $attr_array = [];
    foreach ($node->attr as $name => $value)
    {
      if (array_search($name, $exclude_names) === false)
        $attr_array[$name] = $value;
    }
    return $attr_array;
  }
  
  function attrlist_to_array($source, $exclude_names = [])
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
  
  private $_mapped_props = []; //this is a hack 
  
  function map_component_properties($component_id, $component_dom)
  {
    if ($this->_mapped_props[$component_id] == true)
      return; //THIS IS A TOTAL HACK BUT FOR NOW IT SHOULD WORK
    
    $this->_mapped_props[$component_id] = true;
    
    $prop_nodes = $component_dom->find('[prop-name]');
        
    foreach ($prop_nodes as $prop_node)
    {
      $node_id = $prop_node->getAttribute('id');
      $prop_name = $prop_node->getAttribute('prop-name');
      
      $tag = $prop_node->tag;
      $method = 'html';
      if ($tag == 'input' || $tag == 'select' || $tag == 'textarea')
        $method = 'val';
      
      $this->registered_script_attrs[$node_id.'-'.$prop_name] = true;
      
      $this->append_instance_script('mercury.setMappedProp("'.$component_id.'", "'.$tag.'", "'.$node_id.'", "'.$prop_name.'");');      
      $prop_node->removeAttribute('prop-name');
    }
  }
  
  
  function get_document($node)
  {
    $parent = $node->parent();
    
    while ($parent != null)
    {
      $node = $parent;
      $parent = $node->parent();
    }
    return $node;
  }
  
  function apply_attrs($source_attrs, $target)
  {
    foreach ($source_attrs as $name => $value)
    {
      //echo '<br>setting attr '.$name.' = '.$value.' on '.$target->getAttribute('id');
      $target->setAttribute($name, $value);
    }
  }
  
  /* function get_script()
  {
    $s = '';
    
    foreach ($this->_script_srcs as $key => $value)
    {
      $s.='<script type="text/javascript" src="'.$value.'"></script>';
    }
    return $s;
  } */
  
  private $_component_scripts = [];
  private $_component_html = [];
  
  
  public function get_component_file_contents($namespace, $dir_name, $name)
  {
    $file_path = $this->get_component_file_path($namespace, $dir_name, $name);
    if (!file_exists($file_path))
    {
      return 'no_contents';
      //throw new Exception('The html file for component '.$name.' does not exist in '.$file_path.'.');
    }

    return file_get_contents($file_path);
  }
  
  public function get_controller_contents($namespace, $name)
  {
    $file_path = $this->get_component_file_path($namespace, 'controllers', $name.'Controller', 'js');
    if (!file_exists($file_path))
    {
      //throw new Exception('The js controller file for component '.$name.' does not exist in '.$file_path.'.');
      return '';
    }

    return file_get_contents($file_path);
  }
  
  function parse_references($dom_node, $namespace)
  {
    $reference_nodes = $dom_node->find('ref');
    if (!$reference_nodes) return;
    
    foreach ($reference_nodes as $ref)
    {
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
        $src = $this->get_namespace_virtual_root($ns).'/assets/'.$ref->getAttribute('name');
      }
      
      $this->add_reference($src, $additional_attrs);
    }
  }
  
  public function map_attr_values($attrs, $target)
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
  
  public function replace_attr_tokens($attrs, $target)
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