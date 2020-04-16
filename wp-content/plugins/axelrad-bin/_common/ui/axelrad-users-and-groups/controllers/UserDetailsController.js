class UserDetailsController extends MercuryController
{
  constructor(id) 
  { 
    super(id); 
  }
  
  onInit(me)
  {
    
    this.model.user.fetched(
      function() { me.syncUser(); }
    );
    
    this.model.user.created(
      function() { me.syncUser(); }
    );
    
    this.model.user.updated(
      function() { me.syncUser(); }
    );
    
    this.model.user.deleted(
      function() { me.syncUser(); }
    );
    
    this.model.users.onSelectionChanged(
      function() { me.sync(); }
    );
    
    this.view.userCount = 0;
    this.sync();
  }
  
  syncUser()
  {
    if (this.model.user.isEmpty())
       return;
    
    this.view.user_name = this.model.user.value.first_name+' '+this.model.user.value.last_name;
    this.view.userCount = 1;
  }
  
  sync()
  {
    this.view.userCount = 0;
    var count = this.model.users.selectedKeys.length;
    if (count == 0)
    {
      this.view.user_name = '';
    }
    else if (count > 1)
    {
      this.view.userCount = count;
      this.view.user_name = this.view.userCount+' users selected';
    }
  }
}