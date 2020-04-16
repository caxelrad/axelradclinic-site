<?php

require_once('rb.php');

//AxBin::load('axelrad-user-mgmt');

class AxData
{
    public static function _flatten($bean_result)
    {
        //flattens the query result from a findAll or getAssoc or whatever... 
        
        _ax_debug('flattening');

        $rows = [];
        foreach ($bean_result as $id => $row)
        {
            $row['id'] = $id;
            _ax_debug(json_encode($row));
            $rows[] = $row;
        }

        return $rows;
    }

    public static function configure($host, $db_name, $user_name, $password)
    {
        R::setup( 'mysql:host='.$host.';dbname='.$db_name, $user_name, $password );
    }


    public static function record($event_name, $obj_type, $obj_id, $meta)
    {
        $event = R::dispense('event');
        $event->name = $event_name;
        $event->obj_type = $obj_type;
        $event->obj_id = $obj_id;
        $event->meta = json_encode($meta);
        $event->user_id = AxelradUserMgmt::current_id();
        $event->created = date('c');
        R::store($event);
    }
}

class AxDataProp
{
    private $_name;
    private $_type = 'text';
    private $_required = false;
    private $_minlength = 0;
    private $_maxlength = 0;
    private $_default_value = null;
    private $_format_as = '';
    private $_must_contain = '';
    private $_must_not_contain = '';
    private $_can_only_contain = '';
    
    const TYPE_TEXT = 'text';
    const TYPE_EMAIL = 'email';
    const TYPE_PHONE = 'phone';
    const TYPE_NUM = 'num';
    const TYPE_BOOL = 'bool';
    const TYPE_DATE = 'date';

    function __construct($name, $type = AxDataProp::TYPE_TEXT)
    {
        $this->_name = $name;
        $this->_type = $type;
    }

    public function name()
    {
        return $this->_name;
    }

    public function type()
    {
        return $this->_type;
    }

    public function default_value($val = null)
    {
        if ($val !== null)
        {
            $this->_default_value = $val;
            return $this;
        }
        else
            return $this->_default_value;
    }

    
    public function required($val = null)
    {
        if ($val !== null)
        {
            _ax_debug('required = '.$val);
            $this->_required = $val;
            return $this;
        }
        else
            return $this->_required;
    }

    public function minlength($val = null)
    {
        if ($val !== null)
        {
            $this->_minlength = $val;
            return $this;
        }
        else
            return $this->_minlength;
    }

    public function maxlength($val = null)
    {
        if ($val !== null)
        {
            _ax_debug('maxlength = '.$val);
            $this->_maxlength = $val;
            return $this;
        }
        else
            return $this->_maxlength;
    }

    public function must_contain($val = null)
    {
        if ($val !== null)
        {
            $this->_must_contain = $val;
            return $this;
        }
        else
            return $this->_must_contain;
    }

    public function must_not_contain($val = null)
    {
        if ($val !== null)
        {
            $this->_must_not_contain = $val;
            return $this;
        }
        else
            return $this->_must_not_contain;
    }

    public function can_only_contain($val = null)
    {
        if ($val !== null)
        {
            $this->_can_only_contain = $val;
            return $this;
        }
        else
            return $this->_can_only_contain;
    }

    public function format_as($val = null)
    {
        if ($val !== null)
        {
            $this->_format_as = $val;
            return $this;
        }
        else
            return $this->_format_as;
    }

}

class AxPropValidationException extends Exception 
{
    public $key;
    public $data;
    public $msg;

    function __construct($key, $data, $msg)
    {
        $this->key = $key;
        $this->data = $data;
        $this->message = $msg;
        $this->code = 0;
    }
}

class AxPropList
{
    private $_items = [];
    public $model = null;

    function __construct($model)
    {
        $this->model = $model;
    }

    public function add($prop)
    {
        _ax_debug('AxPropList->add: '.$prop->name());
        
        if ($this->_items[$prop->name()])
            throw new Exception('Property '.$prop->name().' already exists in '.get_class($this->model).'.');

        $this->_items[$prop->name()] = $prop;

        return $prop;
    }

    public function items() { return $this->_items; }

    public function contains($name) { return $this->_items[$name] != null; }

    public function add_text($name)
    {
        return $this->add(new AxDataProp($name));
    }

    public function add_num($name)
    {
        return $this->add(new AxDataProp($name, AxDataProp::TYPE_NUM));
    }

    public function add_bool($name)
    {
        return $this->add(new AxDataProp($name, AxDataProp::TYPE_BOOL));
    }

    public function add_date($name)
    {
        return $this->add(new AxDataProp($name, AxDataProp::TYPE_DATE));
    }

    public function add_email($name)
    {
        return $this->add(new AxDataProp($name, AxDataProp::TYPE_EMAIL));
    }

    public function add_phone($name)
    {
        return $this->add(new AxDataProp($name, AxDataProp::TYPE_PHONE));
    }
}

abstract class AxDataObj extends RedBean_SimpleModel
{
    protected $_event_name = '';
    
    public const ILLEGAL_NAME_CHARS = '#%@$*^+=/\\';
    public const ALPHA_NUM_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxzy1234567890';
    public const ALPHA_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxzy';
    public const ALPHA_UPPER = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    public const ALPHA_LOWER = 'abcdefghijklmnopqrstuvwxzy';
    public const NUMERIC = '1234567890';


    protected function records_create() { return false; }
    protected function records_open() { return false; }
    protected function records_update() { return false; }
    protected function records_delete() { return false; }

    private $_props = null;
    
    function _properties()
    {
        if ($this->_props == null)
        {
            $this->_props = new AxPropList($this);
            $this->_props->add_num('id')->required(true)->default_value(0);
            $this->add_props($this->_props);
            $this->_props->add_date('created')->default_value(date('c'));
            $this->_props->add_date('modified')->default_value(date('c'));
            $this->_props->add_num('created_by')->default_value(AxelradUserMgmt::current_id());
            $this->_props->add_num('modified_by')->default_value(AxelradUserMgmt::current_id());

        }

        return $this->_props;
    }

    protected function props() { return $this->_properties()->items(); }

    protected abstract function add_props(AxPropList $prop_list);

    protected function get_snapshot()
    {
        $snapshot = [];
        foreach ($this->bean as $key => $value)
        {
            $snapshot[$key] = $value;
        }

        return $snapshot;
    }

    protected function get_changes()
    {
        $changed = [];
        foreach ($this->bean as $key => $value)
        {
            if ($this->bean->hasChanged($key))
                $changed[$key] = ['old' => $this->bean->old($key), 'new' => $value];
        }
        return $changed;
    }
    public function is_new() { return $this->bean->id == 0; }

    public function open()
    {
        if ($this->records_open())
            $this->record_event('open');
    }

    protected function on_open() { }

    protected function validation_error($key, $data, $msg)
    {
        _ax_debug(get_class($this).'->validation_error()');
        throw new AxPropValidationException($key, $data, $msg);
    }

    protected function validate()
    {
        $this->on_validate();
    }

    protected function on_validate() 
    { 
        _ax_debug(get_class($this).'->validate()');

        //check for missing required values
        $req = [];

        //check for values too short or too long
        $too_short = [];
        $too_long = [];

        foreach ($this->props() as $name => $prop)
        {
            $value = $this->bean[$name];
            _ax_debug('validating property '.$name.' with value: '.$value);
            if ($prop->required())
            {
                if ($value === null || $value === '')
                    $req[] = $name;
            }

            if ($prop->type == AxDataProp::TYPE_EMAIL && $value)
            {
                if (!AxelradUtil::email_is_valid($value))
                    $this->validation_error('INvALID_EMAIL', $value, 'The email address is not valid.');
            }

            if ($value && $prop->maxlength())
            {
                if (strlen($value) > $prop->maxlength())
                    $too_long[] = $prop->name();
            }

            if ($value && $prop->minlength())
            {
                if (strlen($value) < $prop->minlength())
                    $too_short[] = $prop->name();
            }
        }

        if (count($req) > 0) 
            $this->validation_error(
                'PROP_REQUIRED', 
                $req,
                'The following properties are required for '.$this->bean->getMeta('type').': '.implode(', ', $req));
        
        if (count($too_long) > 0)
            $this->validation_error(
                'PROP_LONG', 
                $too_long,
                'The following properties exceed the max length for '.$this->bean->getMeta('type').': '.implode(', ', $too_long));

        if (count($too_short) > 0)
            $this->validation_error(
                'PROP_SHORT', 
                $too_short,
                'The following properties do not meet the minimum length for '.$this->bean->getMeta('type').': '.implode(', ', $too_long));


        //check for illegal or missing stuff...
        foreach ($this->props() as $name => $prop)
        {
            $value = $this->bean->$name;

            if ($prop->must_contain())
            {
                if ($value && !_ax_util_str_contains_one_of($value, $prop->must_contain()))
                    $this->validation_error(
                        'PROP_MUST_CONTAIN', 
                        $prop->must_contain(),
                        'The property '.$name.' must contain at least one of the following: '.$prop->must_contain()
                    );
            }

            if ($prop->must_not_contain())
            {
                if ($value && !_ax_util_str_contains_none_of($value, $prop->must_not_contain()))
                    $this->validation_error(
                        'PROP_MUST_NOT_CONTAIN', 
                        $prop->must_not_contain(),
                        'The property '.$name.' cannot contain any of the following: '.$prop->must_not_contain()
                    );
            }

            if ($prop->can_only_contain())
            {
                if ($value && !_ax_util_str_contains_only($value, $prop->can_only_contain()))
                    $this->validation_error(
                        'PROP_CAN_ONLY_CONTAIN', 
                        $prop->can_only_contain(),
                        'The property '.$name.' can only contain the following: '.$prop->can_only_contain()
                    );
            }
        }
    }

    public function update() 
    {
        $this->_event_name = 'update';

        $invalid = [];
        foreach ($this->bean as $key => $value)
        {
            if (!$this->_properties()->contains($key))
            {
                $invalid[] = $key;
            }
        }
        
        if (count($invalid) > 0)
            $this->validation_error(
                'INVALID_PROPS', $invalid, 
                'The '.get_class($this).' contains the following invalid property names:'.implode(', ', $invalid)
            );

        if ($this->is_new())
        {
            _ax_debug('bean is new.');
            //populate default values... 
            foreach ($this->props() as $name => $prop)
            {
                if ($this->bean[$name] == null)
                {
                    _ax_debug('setting default value for '.$name.' as '.$prop->default_value());
                    $this->bean->$name = $prop->default_value();
                }
            }

            $this->_event_name = 'create';

            $this->bean->created_by = AxelradUserMgmt::current_id();
            
            if ($this->on_check_exists())
                $this->validation_error('EXISTS', get_class($this), 'Object exists.');
        }

        $this->bean->modified_by = AxelradUserMgmt::current_id();

        //gonna validate! 
        $this->validate();

        
        do_action('ax_data_obj_saving', $this->bean);
        
        $this->on_update();

        if ($this->records_update() && $this->_event_name == 'update')
            $this->_changes = $this->get_changes();
    }

    protected $_changes = [];

    protected function on_update() { }

    protected function on_check_exists()
    {
        return false;
    }

    //override this in your model if you want a property to have some other default value
    protected function get_prop_default_value($prop_name)
    {
        return $this->props()[$prop_name]->default_value();
    }

  
    public function after_update() 
    {
        do_action('ax_data_obj_saved', $this->bean);

        if ($this->_event_name == 'update')
        {
            if ($this->records_update())
            {
                $this->record_event('update', $this->_changes);
            }
        }
        else
        {
            if ($this->records_create())
            {
                $this->record_event('create', $this->bean);
            }
        }

        $this->on_after_update();
    }

    protected function on_after_update() {}

    private $_deleted_obj = null;

    public function delete()
    {
        $this->on_delete();
        $this->_deleted_obj = $this->get_snapshot();
    }

    protected function on_delete() {}

    public function after_delete()
    {
        if ($this->records_delete())
        {
            $this->record_event('delete', $this->_deleted_obj);
            $this->_deleted_obj = null;
        }
    }
    protected function on_after_delete() {}

    protected function record_event($event_name, $meta = null)
    {
        $obj_type = strtolower(str_replace('Model_', '', get_class($this)));
        $obj_id = $this->bean->id;
        AxData::record($event_name, $obj_type, $obj_id, $meta);
    }
}

