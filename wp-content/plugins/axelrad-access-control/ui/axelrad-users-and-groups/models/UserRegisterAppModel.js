class UserRegisterAppModel extends MercuryModel
{
  
  constructor(name)
  {
    super(name);
    
    this.user = new MercuryModelProp(this, 'user', null);  
  }
  
  emailExists(email, handler)
  {
    this.get('email_exists', {'email' : email }, handler);
  }
  
  onInit(me)
  {
    
  }
}
