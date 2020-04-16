class UsersMainListController extends MercuryController
{
  constructor(id)
  {
    super(id);
  }
  
  onInit(me)
  {
    
    this.model.users.changed(
      function()
      {
        me.refresh();
      }
    );
    
    
    this.view.onSelectionChanged(
      function()
      {
        me.model.users.selectRows(me.view.selectedKeys); 
      }
    );
    
    
    this.refresh();
    
  }
  
  refresh()
  {
    this.view.setUsers(this.model.users.value);
  }

}