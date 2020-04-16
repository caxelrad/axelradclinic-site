class UserRmvGroupDialogController extends MercuryController
{
  constructor(id) 
  { 
    super(id);
    this.confirmed = false;
  }
  
  
  onInit(me)
  {
    
    this.model.membership.deleting(
      function()
      {
        me.view.userName = me.model.user.value.first_name+' '+me.model.user.value.last_name;
        me.view.groupDisplayName = me.model.membership.value.group_display_name;
        me.view.showModal();
      }
    );
    
    this.model.membership.deleted(
      function()
      {
        me.view.hideModal();
        me.resetConfirm();
      }
    );
    
    this.model.membership.deleteCancelled(
      function() { me.view.hideModal(); me.resetConfirm(); }
    );
    
    
    this.view.onOK(
      function()
      {
        if (!me.confirmed)
        {
          me.confirmed = true;
          me.view.confirmAgain();
        }
        else
        {
          me.model.membership.delete();
        }
      }
    );
    
    this.view.onCancel(
      function()
      {
        me.model.membership.cancelDelete();
      }
    );
  }
  
  resetConfirm()
  {
    this.view.resetConfirm();
    this.confirmed = false;
  }
}