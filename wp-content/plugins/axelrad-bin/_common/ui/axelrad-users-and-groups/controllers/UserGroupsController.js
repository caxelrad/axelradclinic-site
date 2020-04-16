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
        me.model.membership.value = me.model.memberships.findRow(me.view.selectedGroupName);
        me.model.membership.beginDelete();
      }
    );
  }
  
  
  refreshMemberships()
  {
    this.log('refreshMemberships');
    this.view.setMemberships(this.model.memberships.value);
    this.view.membershipCount = this.model.memberships.length;
  }

}