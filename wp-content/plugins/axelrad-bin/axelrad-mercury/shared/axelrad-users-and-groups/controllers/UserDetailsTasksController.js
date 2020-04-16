class UserDetailsTasksController extends MercuryController
{
  constructor(id) 
  { 
    super(id); 
  }
  
  onInit(me)
  {
    
    this.view.onEditClick(
      function()
      {
        me.model.user.beginUpdate(); //.value = me.model.groups.selectedRows[0];
      }
    );
    this.view.onDeleteClick(
      function()
      {
        me.model.user.beginDelete(); //.value = me.model.groups.selectedRows[0];
      }
    );
    
    this.view.onEmailClick(
      function()
      {
        alert('email clicked');
        me.model.user.beginOperation('send_welcome_email');
      }
    );
  }
}