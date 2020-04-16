class GroupDeleteDialogController extends MercuryController
{
  constructor(id) 
  { 
    super(id);
    this.confirmed = false;
  }
  
  
  onInit(me)
  {
    
    this.model.group.deleting(
      function()
      {
        me.view.groupName = me.model.group.value.display_name;
        me.view.showModal();
      }
    );
    
    this.model.group.deleted(
      function()
      {
        me.view.hideModal();
        me.resetConfirm();
      }
    );
    
    this.model.group.deleteCancelled(
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
          me.model.group.delete();
        }
      }
    );
    
    this.view.onCancel(
      function()
      {
        me.model.group.cancelDelete();
      }
    );
  }
  
  resetConfirm()
  {
    this.view.resetConfirm();
    this.confirmed = false;
  }
}