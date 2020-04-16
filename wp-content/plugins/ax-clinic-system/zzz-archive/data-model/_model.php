<?php

class AxClinicModel
{

    public static function init($db_server, $db_name, $db_user, $db_pwd)
    {
        require_once('rb.php');
        R::setup( 'mysql:host='.$db_server.';dbname='.$db_name, $db_user, $db_pwd );
    }

    static function get_class_name($obj_name)
    {
        return _ax_util_to_camel_case($obj_name);
    }

    public static function load($obj_name)
    {
        _ax_debug('loading the '.$obj_name.' object.');
        require_once(trailingslashit(__DIR__).$obj_name.'.php');
        _ax_debug($obj_name.' loaded.');
    }
    
    static $_refs = [];
    static function get_ref_instance($obj_name)
    {
        if (self::$_refs[$obj_name] == null)
        {
            self::load($obj_name);
            $class_name = self::get_class_name($obj_name);
            self::$_refs[$obj_name] = new $class_name();
        }
        return self::$_refs[$obj_name];
    }

    public static function _new($obj_name, $id = 0)
    {
        self::load($obj_name);
        $class_name = self::get_class_name($obj_name);
        return new $class_name($id);
    }

    private static $_user_id = 0;

    public static function current_user_id($user_id = -1)
    {
        if ($user_id == -1)
            return self::$_user_id;
        else 
            self::$_user_id = $user_id;
    }

    static $_table_names = [];
    static function get_table_name($obj_name)
    {
        return self::get_ref_instance($obj_name)->table_name();
    }

    //returns DATA not objects - to get objects, call "to_objects"
    public static function find($obj_name, AxModelFilter $filter) 
    {

        $template = $filter->template();
        $values = $filter->values();
        $table_name = self::get_table_name($obj_name);
        _ax_debug('attempting to find '.get_class($obj_name).' in table '.$table_name.': '.$template.' --> '.json_encode($values));
        
        return R::find( $table_name, $template, $values);
    }

    public static function to_objects($obj_name, $items)
    {
        _ax_debug('to_objects: '.$obj_name.', '.json_encode($items));

        $list = [];
        foreach ($items as $item)
        {
            $instance = self::_new($obj_name, $item['id']);
            $instance->refresh($item);
            $list[] = $instance;
        }
        
        return $list;
    }

    
    public static function read($obj_name, $id)
    {

        $instance = self::_new($obj_name, $id);
        $instance->read();
        return $instance;
    }

    public static function count($obj_name, $filter)
    {
        $table_name = self::get_table_name($obj_name);
        $template = $filter->template();
        $values = $filter->values();

        _ax_debug('attempting to get count of '.get_class($obj_name).' in table '.$table_name.': '.$template.'.');

        return R::count($table_name, $template, $values);
    }

    public static function exists($obj_name, $filter)
    {
        return self::count($obj_name, $filter) > 0;
    }

    // public static function find_like($obj_name, $filter, $sort = null)
    // {

    //     $template = $filter->template(). ($sort ? $sort->to_string() : '');

    //     return R::findLike( self::get_table_name($obj_name), 
    //         $template, 
    //         $filter->values());
    // }
    
}

class AxModelSort
{
    private $_rows = [];

    function __construct($prop_name, $direction = 'ASC')
    {
        $this->add($prop_name, $direction);
    }

    public function add($prop_name, $direction = 'ASC')
    {
        $this->_rows[$prop_name] = $direction;
    }

    public function to_string()
    {
        $sort = '';

        foreach ($this->_rows as $prop => $direction)
        {
            if ($sort != '') $sort = $sort.', ';

            $sort = $sort.' '.$prop.' '.$direction;
        }

        return $sort ? ' ORDER BY'.$sort : '';
        
    }
}
class AxFilterRow
{
    private $_prop_name;
    private $_match;
    private $_value;
    private $_type;
    function __construct($prop_name, $match, $value, $type)
    {
        $this->_prop_name = $prop_name;
        $this->_match = $match;
        $this->_value = $value;
        $this->_type = $type;
    }

    public function prop_name() { return $this->_prop_name; }
    public function match() { return $this->_match; }
    public function value() { return $this->_value; }
    public function type() { return $this->_type; }
}

class AxModelFilter
{
    private $_operator;
    private $_rows = [];
    

    function __construct($operator = 'AND')
    {
        $this->_operator = $operator;
    }

    public function operator() { return $this->_operator; }

    public function add($prop_name, $match, $value, $type)
    {
        $this->_rows[$prop_name] = new AxFilterRow($prop_name, $match, $value, $type);
    }

    public function template()
    {
        $template = '';
        foreach ($this->_rows as $row)
        {
            if ($template != '') $template = $template .' '.$this->operator();
            $template = $template.' '.$row->prop_name().' '.$row->match().' ?';
        }

        return $template;
    }

    public function rows() { return $this->_rows; }

    public function values()
    {
        $values = [];
        foreach ($this->_rows as $row)
        {
            $values[] = [$row->value(), $row->type()];
        }

        return $values;
    }
}

class AxPropertyType
{
    public const type_str = PDO::PARAM_STR;
    public const type_int = PDO::PARAM_INT;
    public const type_bool = PDO::PARAM_BOOL;
    public const type_date = PDO::PARAM_STR;
    public const type_dbl = PDO::PARAM_STR;
}

abstract class AxModelObj
{
    public $id;
    public $created;
    public $modified;

    private $_bean = null;

    function __construct($id = 0)
    {
        _ax_debug('new '.get_class($this).' object ('.$id.')');
        $this->id = $this->add_prop('id', AxPropertyType::type_int, 0);
        $this->created = $this->add_prop('created', AxPropertyType::type_date);
        $this->modified = $this->add_prop('modified', AxPropertyType::type_date);

        $this->id->value($id);
    }


    public function table_name() { return strtolower('ax'.$this->class_name()); }

    private $_data_props = [];
    private $_child_props = [];
    private $_linked_props = [];
    private $_parent_props = [];

    protected function add_prop($field_name, $type=AxPropertyType::type_str, $default_value = null)
    {
        //_ax_debug('property '.$field_name.' defined for '.get_class($this));
        
        $prop = new AxModelProperty($this, $field_name, $type, $default_value);
        $this->_data_props[] = $prop;
        return $prop;
    }

    protected function add_child_prop($child_obj_name)
    {
        $prop = new AxModelChild($this, $child_obj_name);
        $this->_child_props[] = $prop;
        return $prop;
    }

    protected function add_linked_prop($linked_obj_name)
    {
        $prop = new AxModelLinked($this, $linked_obj_name);
        $this->_linked_props[] = $prop;
        return $prop;
    }

    protected function add_parent_prop($parent_obj_name)
    {
        $prop = new AxModelParent($this, $parent_obj_name);
        $this->_parent_props[] = $prop;
        return $prop;
    }
    
    public function class_name() { return get_class($this); }

    public function created($date = null)
    {
        if ($date)
            $this->_['created'] = $date;
        else
            return $this->_['created'];
    }

    public function modified($date = null)
    {
        if ($date)
            $this->_['modified'] = $date;
        else
            return $this->_['modified'];
    }

    protected function bean($force_load = false)
    {
        if ($this->_bean = null || $force_load)
        {
            if ($this->id->value() != 0)
                $this->_bean = R::load( $this->table_name(), $this->id->value() );
            else 
                $this->_bean = R::dispense( $this->table_name());
        }

        return $this->_bean;
    }

    public function rename($new_name)
    {
        if ($this->id->value() != 0)
        {
            _ax_debug('renaming '.get_class($this).' with id '.$this->id->value());
            _ax_debug('$new_name = '.$new_name);

            $this->refresh($this->bean(true));

            $old_name = $this->on_rename($new_name); 
            _ax_debug('$old_name = '.$old_name);
            $this->on_renamed($old_name, $new_name);
        }
    }

    //return the old name if worked, otherwise throw an exception
    protected function on_rename($new_name) { }

    protected function on_renamed($old_name, $new_name) { }

    public function read()
    {
        if ($this->id->value() != 0)
        {
            _ax_debug('reading data of '.get_class($this).' with id '.$this->id->value());
            $this->refresh($this->bean(true));
            $this->on_read_complete();   
        }
    }

    public function refresh($bean)
    {
        foreach ($this->_data_props as $prop)
        {
            $prop->refresh($bean);
        }
    }

    protected function on_read_complete() { }

    public function save()
    {

        _ax_debug(get_class($this).'save();');
        
        if ($this->id->value() == 0)
            $this->created->value(date(DATE_ATOM, time()));

        $this->modified->value(date(DATE_ATOM, time()));
        

        $this->on_saving();

        _ax_debug('$this->id->value() = '.$this->id->value());
        
        foreach ($this->_data_props as $prop)
        {
            $this->bean()[$prop->field_name] = $prop->value();
        }

        $this->id->value(R::store($this->bean()));
        
        _ax_debug(get_class($this).' saved. id = '.$this->id->value());
        
        $this->on_saved();

        return $this->id->value();   
    }

    protected function on_saving() { }
    protected function on_saved() { }
    
    public function delete()
    {
        if ($this->id->value() > 0)
            R::trash( $this->bean() );
        else 
            throw new AxNotSupportedException($this, 'Cannot delete object with id 0.');
    }

    //override this and throw exception to stop the deletion process.
    protected function on_deleting() { } 

    //override this to do something after deletion
    protected function on_deleted() { }

    protected function get_linked_prop_name($linked_obj_name)
    {
        return 'shared'.ucfirst($linked_obj_name).'List';
    }

    protected function get_child_prop_name($child_obj_name)
    {
        return 'own'.ucfirst($child_obj_name).'List';
    }

    protected function refresh_relation($relation, $prop_name)
    {
        $stored_list = $this->bean()->$prop_name;
        $items = [];
        //have to iterate to get the values...
        foreach ($stored_list as $item)
        {
            $instance = AxClinicModel::_new($relation->listed_obj_name, $item->id);
            $instance->refresh($item);
            $items[] = $instance;
        }

        $relation->items($items);
    }

    public function refresh_parent(AxModelParent $parent)
    {
        $prop_name = $parent->parent_obj_name;
        $stored_parent = $this->bean()->$prop_name; //read the bean

        //load it into a wrapper obj
        $instance = AxClinicModel::_new($parent->parent_obj_name, $stored_parent->id);
        $instance->refresh($stored_parent);

        //set the value to the wrapper obj
        $parent->value($instance);
    }

    public function refresh_children(AxModelChild $relation)
    {
        $prop_name = $this->get_child_prop_name($relation->child_obj_name);
        $this->refresh_relation($relation, $prop_name);
    }

    public function add_child($relation, $item)
    {
        $prop_name = $this->get_child_prop_name($relation->child_obj_name);
        $this->bean()->$prop_name[] = $item->bean();
        R::store( $this->bean() );
    }

    public function rmv_child($relation, $item)
    {
        //opens the exclusive version so things are actually deleted...
        $prop_name = 'x'.$this->get_child_prop_name($relation->child_obj_name);
        unset($this->bean()->$prop_name[$item->id->value()]);
        R::store($this->bean());
    }

    public function refresh_linked(AxModelChild $relation)
    {
        $prop_name = $this->get_linked_prop_name($relation->linked_obj_name);
        $this->refresh_relation($relation, $prop_name);
    }

    public function link($relation, $item)
    {
        $prop_name = $this->get_linked_prop_name($relation->linked_obj_name);
        $this->bean()->$prop_name[] = $item->bean();
        R::store( $this->bean() );
    }

    public function unlink($relation, $item)
    {
        //opens the exclusive version so things are actually deleted...
        $prop_name = $this->get_linked_prop_name($relation->linked_obj_name);
        unset($this->bean()->$prop_name[$item->id->value()]);
        R::store($this->bean());
    }
}

class AxModelProperty
{
    public $field_name;
    public $type;
    public $default_value;
    private $_value;
    private $_changed = false;

    function __construct($owner, $field_name, $type, $default_value)
    {
        //_ax_debug('construct AxModelProp: '.get_class($owner).', '.$field_name.', '.$type.', '.$default_value);
        $this->owner = $owner;
        $this->field_name = $field_name;
        $this->type = $type;
        $this->default_value = $default_value;
    }

    //ONLY USE THIS to read the value from the data stream...
    public function refresh($bean)
    {
        $this->_value = $bean[$this->field_name];
        $this->_changed = false;
    }

    public function value($val = null) 
    { 
        if ($val === 0 || $val != null)
        { 
            $this->_changed == ($val != $this->_value);
            $this->_value = $val; 
        }
        else 
        {
            return $this->_value; 
        }
    }

    public function changed() { return $this->_changed; }
}

//one to many
class AxModelChild
{
    public $owner;
    public $child_obj_name;
    public $relation;
    private $_items = [];

    function __construct($owner, $child_obj_name)
    {
        $this->owner = $owner;
        $this->child_obj_name = $child_obj_name;
    }

    //this adds the item and saves it so be careful!
    public function add($item)
    {
        $this->owner->add_child($this, $item);
    }

    //this actually removes the item from the db so be careful!
    public function remove($item)
    {
        $this->owner->rmv_child($this, $item);
    }

    public function items($arr = null) 
    { 
        if ($arr != null)
        { 
            $this->_items = $arr; 
        }
        else 
        {
            $this->owner->refresh_children($this);
            return $this->_items; 
        }
    }
}

//many to many
class AxModelLinked
{
    public $owner;
    public $linked_obj_name;
    private $_items = [];

    function __construct($owner, $linked_obj_name)
    {
        $this->owner = $owner;
        $this->linked_obj_name = $linked_obj_name;
    }

    //this adds the item and saves it so be careful!
    public function add($item)
    {
        $this->owner->link($this, $item);
    }

    //this actually removes the item from the db so be careful!
    public function remove($item)
    {
        $this->owner->unlink($this, $item);
    }

    public function items($arr = null) 
    { 
        if ($arr != null)
        { 
            $this->_items = $arr; 
        }
        else 
        {
            $this->owner->refresh_linked($this);
            return $this->_items; 
        }
    }
}

//many to one
class AxModelParent
{
    public $owner;
    public $parent_obj_name;
    private $_value = null;

    function __construct($owner, $parent_obj_name)
    {
        $this->owner = $owner;
        $this->parent_obj_name = $parent_obj_name;
    }

    
    public function value($val = null) 
    { 
        if ($val != null)
        { 
            $this->_value = $val; 
        }
        else 
        {
            $this->owner->refresh_parent($this);
            return $this->_value; 
        }
    }

    public function changed() { return $this->_changed; }
}

class AxNotSupportedException extends Exception
{
    function __construct($obj, $message)
    {
        $msg = 'Unsupported operation on '.get_class($obj).': '.$message;
        parent::__construct($msg);
        _ax_debug($msg);
    }
}

class AxDuplicateObjException extends Exception
{
    function __construct($obj, $message)
    {
        $msg = 'A duplicate '.get_class($obj).' was detected: '.$message;
        parent::__construct($msg);
        _ax_debug($msg);
    }
}

class AxObjValidationException extends Exception
{
    function __construct($obj, $message)
    {
        $msg = 'The class '.get_class($obj).' failed validation: '.$message;
        parent::__construct($msg);
        _ax_debug($msg);
    }
}

