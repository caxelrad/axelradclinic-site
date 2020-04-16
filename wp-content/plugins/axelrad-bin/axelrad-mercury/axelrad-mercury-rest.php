<?php


add_action( 'rest_api_init', 'ax_mercury_rest_api_init');

function ax_mercury_rest_api_init()
{
  MercuryRestProvider::init_routes();
}

class MercuryRestModel
{
  
}

class MercuryRestProvider
{
  static function is_test_mode()
  {
    return $_GET['test_mode'] == '1';
  }
  
  public static function init_routes()
  {
    
    register_rest_route( 'mercury/v1', '/get', array(
        'methods' => 'GET',
        'callback' => 'MercuryRestProvider::get'
    ));
    
    $methods = 'POST';
    if (self::is_test_mode())
      $methods = ['POST', 'GET'];
    
    register_rest_route( 'mercury/v1', '/post', array(
        'methods' => $methods,
        'callback' => 'MercuryRestProvider::post'
    ));
  }
  
  public static function get($data)
  {
    return self::run($data); 
  }
  
  public static function post($data)
  {
    return self::run($data); 
  }
  
  static function get_operation($data)
  {
    return 
      [
        'namespace' => $data['namespace'],
        'model_name' => $data['model_name'], 
        'prop_name' => $data['prop_name'], 
        'cmd_name' => $data['cmd_name'],
        'data' => json_decode($data['data'], true), 
        'is_paged' => $data['is_paged'],
        'page_size' => $data['page_size'],
        'page_num' => $data['page_num']
      ];
  }
  
  static $models = [];
  static function load_model($namespace, $name)
  {
    if (self::$models[$name]) return;
    
    $root = AxelradMercuryLoader::get_namespace_root_dir($namespace).'/'.$namespace;
    $php_path = $root.'/models/'.$name.'.php';
    //echo $php_path;

    if (file_exists($php_path))
    {
      require_once($php_path);
      $models[$name] = true;
    }
  }
  
  static function run($data)
  {
    if (self::is_test_mode()) //really only used for POST's changed into GET's
      $data = json_decode($data['data'], true);
    
    $op = self::get_operation($data);
    
    $model_name = $op['model_name'];
    $namespace = $op['namespace'];
    self::load_model($namespace, $model_name);
    
    $prop_name = $op['prop_name'];
    $cmd_name = $op['cmd_name'];
    $params = $op['data'];
    
    if ($prop_name != '')
      $method_name = $prop_name.'_'.$cmd_name;
    else //it's a method directly from the model
      $method_name = $cmd_name;
    
    
    $is_paged = $op['is_paged'] == 'true';
    
    $return_value = 
    [
      'namespace' => $op['namespace'],
      'model_name' => $op['model_name'],
      'prop_name' => $op['prop_name'],
      'cmd_name' => $op['cmd_name'],
      'user_id' => AxelradUserMgmt::current_id()
    ];

    try
    {
        
      if ($is_paged)
      {
        $page_num = intval($op['page_num']);
        $page_size = intval($op['page_size']);
        
        $result = $model_name::$method_name($params, $page_num, $page_size);
        $return_value['success'] = true;
        $return_value['data'] = $result['rows'];
        $return_value['page_num'] = $page_num;
        $return_value['page_size'] = $page_size;
        $return_value['total_count'] = $result['total_count'];
        
        return $return_value;
      }
      else
      {
        $return_value['success'] = true;
        $return_value['data'] = $model_name::$method_name($params);
        return $return_value;
      }
    }
    catch (Exception $ex)
    {
      $return_value['success'] = false;
      $return_value['message'] = $ex->getMessage();
      $return_value['code'] = $ex->getCode();
      return $return_value;
    }
  }
  
  
//   public static function create($data)
//   {
//     return self::run($data, 'create');
//   }
  
//   public static function fetch($data)
//   {
//     return self::run($data, 'fetch');
//   }
  
//   public static function update($data)
//   {
//     return self::run($data, 'update');
//   }
  
//   public static function delete($data)
//   {
//     return self::run($data, 'delete');
//   }
  
  
}