class UserDeleteDialogController extends MercuryController
{
  constructor(id) 
  { 
    super(id);
    this.confirmed = false;
  }
  
  
  onInit(me)
  {
    
    this.model.user.deleting(
      function()
      {
        me.view.user_delete_modal.userName = me.model.user.value.fqn;
        me.view.showModal();
      }
    );
    
    this.model.user.deleted(
      function()
      {
        me.view.hideModal();
        me.resetConfirm();
      }
    );
    
    this.model.user.deleteCancelled(
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
          me.model.user.delete();
        }
      }
    );
    
    this.view.onCancel(
      function()
      {
        me.model.user.cancelDelete();
      }
    );
  }
  
  resetConfirm()
  {
    this.view.resetConfirm();
    this.confirmed = false;
  }
}