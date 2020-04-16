class UserGroupsController extends MercuryController
{
  constructor(id) 
  { 
    super(id); 
  }
  
  onInit(me)
  {
    
    me.model.memberships.fetched(
      function()
      {
        me.refreshMemberships();
      }
    );
    
    
    me.view.addGroupClicked(
      function()
      {
        me.model.membership.value = {};
        me.model.membership.beginCreate();
      }
    );
    
    me.view.rmvGroupClicked(
      function()
      {
        var group = me.model.memberships.findRow(me.view.selectedGroupId);
        var membership = { "user_id" : me.model.user.value.id, "group_id" : me.view.selectedGroupId, "group_display_name" : group.display_name };
        me.model.membership.value = membership;
        me.model.membership.beginDelete();
      }
    );
  }
  
  
  refreshMemberships()
  {
    this.log('refreshMemberships');
    this.view.setMemberships(this.model.memberships.value);
    this.view.membershipCount = this.model.memberships.value.length;
  }

}