class PatientInfoFormController extends MercuryController
{
  constructor(id)
  {
    super(id);
  }
  
  onInit(me)
  {
    
    me.view.onSubmit(
     function()
     {
       me.log('going to validate...');
       me.validate();
     }
    );
    
    me.model.patient.changed(
      function()
      {
        me.sync();
      }
    );
    
    me.view.onBack(
      function()
      {
        me.model.patient.value = null; //this will trigger a backup
      }
    );
  }
  
  sync()
  {
    var p = this.model.patient.value;
    if (p == null) return;
    
    if (p.id == 0)
    {
      this.view.title = "OK, let's get the new patient's information in the system...";
      this.view.description = "Then we'll get the appointment booked.";
    }
    else
    {
      this.view.title = "Alright. Double check the information below to verify it is correct, then click 'Continue'.";
      this.view.description = "If you need to make corrections, click 'Back'.";
    }
    this.view.firstName = p.first_name;
    this.view.lastName = p.last_name;
    this.view.email = u.ex(p.email) ? p.email : '';
    this.view.phone = u.ex(p.phone) ? u.digitsOnly(p.phone) : '';
    this.view.source = u.ex(p.source) ? p.source : '';
    this.view.dob = u.ex(p.dob) ? p.dob : '';
  }
  
  validate()
  {
    this.log('validate email '+this.view.email+' = '+u.isValidEmail(this.view.email));
    
    var emailValid = u.isValidEmail(this.view.email);
    this.view.markValidState('email', emailValid );
    
    var phoneValid = u.isValidPhone(this.view.phone);
    this.view.markValidState('phone', phoneValid);
        
    var firstNameValid = this.view.firstName != '';
    this.view.markValidState('firstName', firstNameValid);
    
    var lastNameValid = this.view.lastName != '';
    this.view.markValidState('lastName', lastNameValid);
    
    var dobValid = (this.view.dob instanceof Date);
    
    this.view.markValidState('dob', dobValid);
    
    if (emailValid && phoneValid && firstNameValid && lastNameValid && dobValid)
    {
      this.model.patient.value = 
        {
          'first_name' : this.view.firstName, 
          'last_name' : this.view.lastName,
          'email': this.view.email,
          'phone' : this.view.phone,
          'source' : this.view.source,
          'dob' : this.view.dob,
        };
      this.model.patientValid.value = true;
    }
  }
}