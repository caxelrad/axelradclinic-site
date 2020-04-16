class UserRegisterAppController extends MercuryController
{
  constructor(id) { super(id); }
  
  onInit(me)
  {
    
    this.view.reg_form.onSubmit(
      function()
      {
        me.validate(
          function(isvalid)
          {
            if (isvalid)
            {
              me.model.user.create(
                {
                  'email' : me.view.form.email,
                  'first_name' : me.view.form.firstName,
                  'first_name_sent' : me.view.reg_form.firstNameVisible ? '1' : '', 
                  'last_name' : me.view.form.lastName,
                  'last_name_sent' : me.view.reg_form.lastNameVisible ? '1' : '',
                  'phone' : me.view.form.phone,
                  'phone_sent' : me.view.reg_form.phoneVisible ? '1' : '',
                  'group_name' : me.view.reg_form.addToGroupName
                }
              );
            }
          }
        );
      }
    );
    
    me.model.user.created(
      function()
      {
        //hit the thank you url.
        window.location.href = me.view.reg_form.attr('success-url');
      }
    );
    
    me.model.user.onError(
      function()
      {
        me.view.reg_form.hideLoading();
      }
    );
    
    me.model.sending(
      function(args)
      {
        me.view.reg_form.showLoading();
      }
    );
    
    me.model.doneSending(
      function(args)
      {
        //we don't hide the loading here UNLESS there's an error (see above)
      }
    );
  }
  
  validate(handler)
  {
    var firstNameValid = true; var lastNameValid = true; var phoneValid = true;
    
    var emailValid = u.isValidEmail(this.view.form.email);
    this.view.form.markValidState('email', emailValid, 'Enter a valid email address.' );

    if (this.view.reg_form.firstNameVisible)
    {
      if (this.view.reg_form.firstNameRequired)
      {
        firstNameValid = this.view.form.firstName != '';
        this.view.form.markValidState('firstName', firstNameValid, 'First name cannot be empty.');
      }
    }
    
    if (this.view.reg_form.lastNameVisible)
    {
      if (this.view.reg_form.lastNameRequired)
      {
        lastNameValid = this.view.form.lastName != '';
        this.view.form.markValidState('lastName', lastNameValid, 'Last name cannot be empty.');
      }
    }
    
    if (this.view.reg_form.phoneVisible)
    {
      var phoneEmpty = this.view.form.phone != '';
      if (phoneEmpty && this.view.reg_form.phoneRequired)
      {
        this.view.form.markValidState('phone', false, 'Phone number cannot be empty.');
        phoneValid = false;
      }
      else if (!phoneEmpty)
      {
        if (!u.isValidPhone(this.view.form.phone))
        {
          phoneValid = false;
          this.view.form.markValidState('phone', false, 'Enter a valid phone number.');
        }
      }
    }
    
    handler(emailValid && firstNameValid && lastNameValid && phoneValid);
    
  }
}