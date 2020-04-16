<?php

class Appointment extends AxModelObj
{
    
    public $subject;
    public $start_utc;
    public $weekday_name;
    public $duration;
    public $type_id;
    public $type_name;
    public $location_id;
    public $location_name;
    public $contact_id;
    public $contact_name;
    public $contact_email;
    public $contact_phone;
    public $clinician_id;
    public $clinician_name;
    public $status;
    public $is_standby;

    function __construct($id = 0)
    {
        
        $this->subject = $this->add_prop('subject');
        $this->start_utc = $this->add_prop('start_utc', AxPropertyType::type_date);
        $this->weekday_name = $this->add_prop('weekday_name');
        $this->duration = $this->add_prop('duration', AxPropertyType::type_int);
        $this->type_id = $this->add_prop('type_id', AxPropertyType::type_int);
        $this->type_name = $this->add_prop('type_name');
        $this->location_id = $this->add_prop('location_id', AxPropertyType::type_int);
        $this->location_name = $this->add_prop('location_name');
        $this->contact_id = $this->add_prop('contact_id', AxPropertyType::type_int);
        $this->contact_name = $this->add_prop('contact_name');
        $this->contact_email = $this->add_prop('contact_email');
        $this->contact_phone = $this->add_prop('contact_phone');
        $this->clinician_id = $this->add_prop('clinician_id', AxPropertyType::type_int);
        $this->clinician_name = $this->add_prop('clinician_name');
        $this->status = $this->add_prop('status');
        $this->is_standby = $this->add_prop('is_standby', AxPropertyType::type_bool);

        parent::__construct($id);

    }

    protected function on_saving()
    {
        if (!$this->contact_id->value()
            || !$this->location_id->value()
            || !$this->type_id->value())
            throw new AxObjValidationException($this, 'Appointments must have a contact, location, and type specified.');

        //look for duplicate...
        $filter = new AxModelFilter();
        $filter->add('first_name', '=', $this->first_name->value(), $this->first_name->type);
        $filter->add('last_name', '=', $this->last_name->value(), $this->last_name->type);
        $filter->add('full_name', '=', $this->full_name->value(), $this->full_name->type);
        if (AxClinicModel::exists('contact', $filter))
            throw new AxDuplicateObjException($this, 'There is a duplicate '.$this->class_name().'.');
    }
}
