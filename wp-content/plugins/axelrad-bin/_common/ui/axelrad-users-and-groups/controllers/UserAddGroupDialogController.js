class UserAddGroupDialogController extends MercuryController
{
  constructor(id) 
  { 
    super(id);
    this.confirmed = false;
  }
  
  
  onInit(me)
  {
    
    this.model.membership.creating(
      function()
      {
        me.view.userName = me.model.user.value.first_name+' '+me.model.user.value.last_name;
        me.view.showModal();
      }
    );
    
    this.model.membership.created(
      function()
      {
        me.resetConfirm();
      }
    );
    
    this.model.memberships.fetched( function() { me.setGroups(); } );
    
    this.model.membership.createCancelled(
      function() { me.view.hideModal(); me.resetConfirm(); }
    );
    
    this.view.onGroupClicked(
      function()
      {
        if (!me.confirmed)
        {
          me.confirmed = true;
          me.view.groupDisplayName = me.model.groups.findRow(me.view.selectedGroupName).display_name;
          me.view.confirmAgain();
        }
        else
        {
          me.model.membership.create(
            {
              'user_id' :  me.model.user.value.id, 
              'group_name' : me.view.selectedGroupName
            }
          );
        }
      }
    );
    
    this.view.onCancel(
      function()
      {
        me.model.membership.cancelCreate();
      }
    );
    
    this.setGroups();
  }
  
  resetConfirm()
  {
    this.view.resetConfirm();
    this.confirmed = false;
    this.view.groupDisplayName = '';
  }
  
  setGroups()
  {
    var groups = this.model.groups.value;    
    var memberships = this.model.memberships.value;
    var result = [];
    for (var x = 0; x < groups.length; x++)
    {
      var add = true;
      var name = groups[x].name;
      
      for (var i = 0; i < memberships.length; i++)
      {
        if (memberships[i].group_name == name)
        {
          add = false;
          break;
        }
      }
      if (add)
        result.push(groups[x]);
    }
    
    this.view.setGroups(result);
  }
}