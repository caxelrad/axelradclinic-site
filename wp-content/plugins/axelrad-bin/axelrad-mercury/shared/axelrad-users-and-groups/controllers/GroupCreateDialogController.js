class GroupCreateDialogController extends MercuryController
{
  constructor(id) { super(id); }
  
  onInit(me)
  {
    
    this.view.changed(
      function()
      {
        me.sync();
      }
    );
    
    me.model.group.creating(
      function() 
      {
        me.view.form.groupName = '';
        me.view.form.groupDisplayName = '';
        me.view.form.groupDescription = '';
        me.view.showModal(); 
      }
    );
    
    me.model.group.createCancelled(
      function() { me.view.hideModal(); }
    );
    
    
    me.model.group.created(
      function() { me.view.hideModal(); }
    );
    
    me.view.onSubmit(
      function()
      {
        
        me.model.group.create(
          {
            'name' : me.view.form.groupName,
            'display_name' : me.view.form.groupDisplayName,
            'description' : u.escape(me.view.form.groupDescription)
          }
        );
      }
    );
    
    me.sync();
  }
  
  sync()
  {
    this.view.allowSubmit = this.view.form.groupName.length > 5;
    
  }
}