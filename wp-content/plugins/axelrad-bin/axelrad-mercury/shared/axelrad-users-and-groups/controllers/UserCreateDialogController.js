class UserCreateDialogController extends MercuryController
{
  constructor(id) { super(id); }
  
  onInit(me)
  {
    
    this.view.onKeyUp(
      function()
      {
        //me.sync();
      }
    );
    
    me.model.user.creating(
      function() 
      {
        me.view.form.email = '';
        me.view.form.firstName = '';
        me.view.form.lastName = '';
        me.view.showModal(); 
      }
    );
    
    me.model.user.createCancelled(
      function() { me.view.hideModal(); }
    );
    
    me.model.user.onError(
      function() { me.view.setError(me.model.user.error.message); }
    );
    
    me.model.user.created(
      function() 
      { 
        me.view.hideModal(); 
      }
    );
    
    me.view.onSubmit(
      function()
      {
        me.view.setError('');
        me.validate(
          function(isvalid)
          {
            if (isvalid)
            {
              mercury.testmode = true;
              me.model.user.create(
                {
                  'email' : me.view.form.email,
                  'first_name' : me.view.form.firstName,
                  'last_name' : me.view.form.lastName
                }
              );
            }
          }
        );
        
      }
    );
  }
  
  validate(handler)
  {
    var emailValid = u.isValidEmail(this.view.form.email);
    this.view.form.markValidState('email', emailValid, 'Enter a valid email address.' );

    var firstNameValid = this.view.form.firstName != '';
    this.view.form.markValidState('firstName', firstNameValid, 'First name cannot be empty.');
    
    var lastNameValid = true; //this.view.form.lastName != '';
    //this.view.form.markValidState('lastName', lastNameValid);
    
    if (!emailValid || !firstNameValid || !lastNameValid)
    {
      handler(false);
    }
    else
    {
      var me = this;
      
      this.model.emailExists(this.view.form.email, 
        function(response)
        {
           if (response.success)
           {
             if (response.data)
             {
               me.view.form.markValidState('email', false, 'This email already exists.');
               handler(false);
             }
             else
             {
               me.view.form.markValidState('email', true);
               handler(true);
             }
           }
           else
           {
             me.view.emailErrorMessage = 'Error checking email: '+response.data.message;
             handler(false);
           }
        }
      );
    }
  }
}