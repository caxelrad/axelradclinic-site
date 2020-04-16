<?php

class Contact extends AxModelObj
{
    
    public $first_name;
    public $last_name;
    public $full_name;
    public $email;
    public $email2;
    public $mobile_phone;
    public $work_phone;
    public $home_phone;
    
    public $dob;
    public $last_completed_appt;
    public $next_appt;
    public $last_cancelled_appt;
    public $last_no_show_appt;
    
    public $clinician_id;

    public $emergency_contact_name;
    public $emergency_contact_phone;
    public $emergency_contact_email;

    public $billing_address_street;
    public $billing_address_street_2;
    public $billing_address_city;
    public $billing_address_state;
    public $billing_address_zip;

    public $shipping_address_street;
    public $shipping_address_street_2;
    public $shipping_address_city;
    public $shipping_address_state;
    public $shipping_address_zip;

    public $appointments;

    function __construct($id = 0)
    {
        _ax_debug('new Contact object ('.$id.')');
        $this->first_name = $this->add_prop('first_name');
        $this->last_name = $this->add_prop('last_name');
        $this->full_name = $this->add_prop('full_name');
        $this->email = $this->add_prop('email');
        $this->email2 = $this->add_prop('email2');
        $this->mobile_phone = $this->add_prop('mobile_phone');
        $this->work_phone = $this->add_prop('work_phone');
        $this->home_phone = $this->add_prop('home_phone');

        $this->dob = $this->add_prop('dob', AxPropertyType::type_date);
        $this->next_appt = $this->add_prop('next_appt', AxPropertyType::type_date);
        $this->last_completed_appt = $this->add_prop('last_completed_appt', AxPropertyType::type_date);
        $this->last_cancelled_appt = $this->add_prop('last_cancelled_appt', AxPropertyType::type_date);
        $this->last_no_show_appt = $this->add_prop('last_no_show_appt', AxPropertyType::type_date);

        $this->clinician_id = $this->add_prop('clinician_id', AxPropertyType::type_int);


        $this->emergency_contact_name = $this->add_prop('emergency_contact_name');
        $this->emergency_contact_phone = $this->add_prop('emergency_contact_phone');
        $this->emergency_contact_email = $this->add_prop('emergency_contact_email');

        $this->billing_address_street = $this->add_prop('billing_address_street');
        $this->billing_address_street_2 = $this->add_prop('billing_address_street_2');
        $this->billing_address_city = $this->add_prop('billing_address_city');
        $this->billing_address_state = $this->add_prop('billing_address_state');
        $this->billing_address_zip = $this->add_prop('billing_address_zip');

        $this->shipping_address_street = $this->add_prop('shipping_address_street');
        $this->shipping_address_street_2 = $this->add_prop('shipping_address_street_2');
        $this->shipping_address_city = $this->add_prop('shipping_address_city');
        $this->shipping_address_state = $this->add_prop('shipping_address_state');
        $this->shipping_address_zip = $this->add_prop('shipping_address_zip');

        $this->appointments = $this->add_child_prop('Appointment');
        

        parent::__construct($id);

    }

    protected function on_saving()
    {
        if ($this->first_name->value() == '' 
            || $this->last_name->value() == ''
            || $this->full_name->value() == '')
            throw new AxObjValidationException($this, 'first_name, last_name, and full_name are required.');

        //look for duplicate...
        $filter = new AxModelFilter();
        $filter->add('first_name', '=', $this->first_name->value(), $this->first_name->type);
        $filter->add('last_name', '=', $this->last_name->value(), $this->last_name->type);
        $filter->add('full_name', '=', $this->full_name->value(), $this->full_name->type);
        if (AxClinicModel::exists('contact', $filter))
            throw new AxDuplicateObjException($this, 'There is a duplicate '.$this->class_name().'.');
    }
}
