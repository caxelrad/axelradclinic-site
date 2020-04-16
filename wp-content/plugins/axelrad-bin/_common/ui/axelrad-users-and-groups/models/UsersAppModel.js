class UsersAppModel extends MercuryModel
{
  
  constructor(name)
  {
    super(name);
    
    this.defaultGroupName = new MercuryModelProp(this, 'defaultGroupName', null);
    this.user = new MercuryModelProp(this, 'user', null);
    this.users = new MercuryModelList(this, 'users', 'id', []);
    this.groups = new MercuryModelList(this, 'groups', 'name', []);
    this.memberships = new MercuryModelList(this, 'memberships', 'group_name', []);
    this.membership = new MercuryModelProp(this, 'membership', null); 
    this.members = new MercuryModelList(this, 'members', 'user_id', []);
  }
  
  emailExists(email, handler)
  {
    this.get('email_exists', {'email' : email }, handler);
  }
  
  getLoginAsUrl(user_id, handler)
  {
    this.get('get_user_login_as_url', {'user_id' : user_id}, handler);
  }
  
  onInit(me)
  {
    
  }
}
