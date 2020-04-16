class UserEditDialogController extends MercuryController
{
  constructor(id) { super(id); }
  
  onInit(me)
  {

    me.model.user.updating(
      function() 
      {
        me.view.email = me.model.user.value.email;
        me.view.firstName = me.model.user.value.first_name;
        me.view.lastName = me.model.user.value.last_name;
        me.view.showModal(); 
      }
    ); 
    
    this.model.user.updateCancelled(
      function()
      {
        me.view.form.email = '';
        me.view.form.firstName = '';
        me.view.form.lastName = '';
        me.view.hideModal();
      }
    );
    
    this.view.changed(
      function()
      {
        me.sync();
      }
    );
    
    me.model.user.updated(
      function() { me.view.hideModal(); }
    );
    
    me.view.onCancel(
      function()
      {
        me.model.user.cancelUpdate();
        me.view.hideModal();
      }
    );
    
    me.view.onSubmit(
      function()
      {
        
        me.model.user.update(
          {
            'email' : me.view.form.email,
            'first_name' : me.view.form.firstName,
            'last_name' : me.view.form.lastName
          }
        );
      }
    );
    
    me.sync();
  }
  
  sync()
  {
    this.view.allowSubmit = this.view.form.email != '' && this.view.form.email.length > 5;
  }
}