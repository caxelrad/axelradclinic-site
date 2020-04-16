class UserAddGroupDialogController extends MercuryController
{
  constructor(id) 
  { 
    super(id);
    this.confirming = false;
    this.clickedGroupId = '';
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
    
    this.view.onItemClicked(
      function()
      {
        if (!me.confirming)
        {
          me.confirm();
        }
        else
        {
          if (me.clickedGroupId != me.view.selectedGroupId)
          {
            me.confirm();
          }
          else 
          {
            me.model.membership.create(
              {
                'user_id' :  me.model.user.value.id, 
                'group_id' : me.view.selectedGroupId
              }
            );
          }
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
  
  confirm()
  {
    this.view.resetConfirm();
    this.confirming = true;
    this.view.selectedGroupName = this.groupDisplayName;
    this.clickedGroupId = this.view.selectedGroupId;
    this.view.confirm("Click again to add <b>"+this.userName+"</b> to <b>"+this.groupDisplayName+"</b>");
  }

  get groupDisplayName() 
  { 
    if (this.view.selectedGroupId)
      return this.model.groups.findRow(this.view.selectedGroupId).display_name; 
    else
      return '';
  }

  get userName() 
  { 
    return this.model.user.value.first_name+' '+this.model.user.value.last_name; 
  }

  resetConfirm()
  {
    this.view.resetConfirm();
    this.confirmed = false;
  }
  
  setGroups()
  {
    var groups = this.model.groups.value;    
    var memberships = this.model.memberships.value;
    var result = [];
    for (var x = 0; x < groups.length; x++)
    {
      var add = true;
      var id = groups[x].id;
      
      for (var i = 0; i < memberships.length; i++)
      {
        if (memberships[i].id == id)
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