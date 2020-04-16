class RegistrationFormController extends MercuryController
{
  constructor(id) { super(id); }
  
  onInit(me)
  {
    this.view.onSubmit(
      function()
      {
        //just call create then the validate handler will check and pass cancel if things aren't correct 
        me.model.user.create(
          {
            'email' : me.view.form.email,
            'first_name' : me.view.form.firstName,
            'first_name_sent' : me.view.firstNameVisible ? '1' : '', 
            'last_name' : me.view.form.lastName,
            'last_name_sent' : me.view.lastNameVisible ? '1' : '',
            'phone' : me.view.form.phone,
            'phone_sent' : me.view.phoneVisible ? '1' : '',
            'group_name' : me.view.addToGroupName
          }
        );
      }
    );
    
    me.model.user.validate(
      function(args)
      {
        if (me.model.user.isCreating)
        {
          me.validate(args.data, 
            function(isvalid)
            {
              args.cancel = !isvalid;
            }
          );
        }
      }
    );
    
    me.model.user.created(
      function()
      {
        //hit the thank you url.
        window.location.href = me.view.attr('success-url');
      }
    );
    
    me.model.user.onError(
      function()
      {
        me.view.hideLoading();
      }
    );
    
    me.model.sending(
      function(args)
      {
        me.view.showLoading();
      }
    );
    
    me.model.doneSending(
      function(args)
      {
        //we don't hide the loading here UNLESS there's an error (see above)
      }
    );
  }
  
  validate(data, handler)
  {
    var firstNameValid = true; var lastNameValid = true; var phoneValid = true;
    
    var emailValid = u.isValidEmail(data.email);
    this.view.form.markValidState('email', emailValid, 'Enter a valid email address.' );

    if (this.view.firstNameVisible)
    {
      if (this.view.firstNameRequired)
      {
        firstNameValid = data.first_name != '';
        this.view.form.markValidState('first_name', firstNameValid, 'First name cannot be empty.');
      }
    }
    
    if (this.view.lastNameVisible)
    {
      if (this.view.lastNameRequired)
      {
        lastNameValid = this.view.form.lastName != '';
        this.view.form.markValidState('last_name', lastNameValid, 'Last name cannot be empty.');
      }
    }
    
    if (this.view.phoneVisible)
    {
      var phoneEmpty = this.view.form.phone != '';
      if (phoneEmpty && this.view.phoneRequired)
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