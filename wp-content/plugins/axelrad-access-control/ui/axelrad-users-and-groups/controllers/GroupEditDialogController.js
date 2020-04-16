class GroupEditDialogController extends MercuryController
{
  constructor(id) { super(id); }
  
  onInit(me)
  {

    this.model.group.updating(
      function()
      {
        me.view.form.groupName = me.model.group.value.name;
        me.view.form.groupDisplayName = me.model.group.value.display_name;
        me.view.form.groupDescription = me.model.group.value.description;
        me.view.showModal();
      }
    );
    
    this.model.group.updateCancelled(
      function()
      {
        me.view.form.groupName = '';
        me.view.form.groupDisplayName = '';
        me.view.form.groupDescription = '';
        me.view.hideModal();
      }
    );
    
    this.view.changed(
      function()
      {
        me.sync();
      }
    );
    
    me.model.group.updated(
      function() { me.view.hideModal(); }
    );
    
    me.view.onCancel(
      function()
      {
        me.model.group.cancelUpdate();
        me.view.hideModal();
      }
    );
    
    me.view.onSubmit(
      function()
      {
        
        me.model.group.update(
          {
            'id' : me.model.group.value.id,
            'display_name' : me.view.form.groupDisplayName,
            'description' : me.view.form.groupDescription
          }
        );
      }
    );
    
    me.sync();
  }
  
  sync()
  {
    this.view.allowSubmit = this.view.form.groupDisplayName.length > 5;
  }
}