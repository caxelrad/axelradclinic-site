class UserMgmtConsoleController extends MercuryController
{
  constructor(id)
  {
    super(id);
  }
  
  onInit(me)
  {
    
    this.model.defaultGroupName.value = 'all-users';
    this.model.users.pageSize = 50;
    this.model.users.pageNum = 1;
    
    me.model.users.onPageNumChanged( function() { me.loadUsers(); } );
    
    me.model.users.onSelectionChanged( function() { me.fetchUser(); } );
    
    me.view.onNewClick( function() { me.model.user.beginCreate(); } );
    
    this.model.user.created( function() { me.loadUsers(); } );
    
    this.model.user.updated( function() { me.loadUsers(); } );
    
    me.model.user.deleted( function() { me.loadUsers(); } );
    
    me.model.sending(
      function(args)
      {
        if (args.command == 'delete' || 
            args.command == 'create' || 
            args.command == 'update')
        {
          u.showLoading();
        }
      }
    );
    
    me.model.doneSending(
      function(args)
      {
        if (args.command == 'delete' || 
            args.command == 'create' || 
            args.command == 'update')
        {
          u.hideLoading();
        }
      }
    );
    
    me.model.groups.fetched( 
      function() 
      { 
        me.view.setGroups(me.model.groups.value); 
        if (!u.isDefined(me.view.selectedGroupName))
          me.view.selectedGroupName = me.groupName();
      }
    );
    
    me.view.onGroupChanged( function() { me.loadMembers(); });
    
    me.model.user.fetched( function() { me.loadMemberships(); });
    
    me.model.membership.created( function() { me.loadMemberships(); });
    
    me.model.membership.deleted( function() { me.loadMemberships(); });
    
    me.view.onSearch( function() { me.loadUsers(); });
    me.view.onSearchClear( function() { me.loadUsers(); } );
    
    me.loadUsers();
    me.loadGroups();
  }
  
  groupName()
  {
    return !u.isDefined(this.view.selectedGroupName) ? this.model.defaultGroupName.value : this.view.selectedGroupName;
  }
  
  fetchUser()
  {
    var count = this.model.users.selectedCount;
    if (count == 1)
    {
      this.model.user.value = {'id' : this.model.users.selectedRows[0].id };
      this.model.user.fetch();
    }
    else
      this.model.user.clear();
  }
  
  loadMembers()
  {
    this.model.users.fetchPage({ 'search' : this.view.searchString, 'group_name' : this.groupName() });
  }
  
  loadUsers()
  {
    this.model.users.fetchPage({ 'search' : this.view.searchString, 'group_name' : this.groupName() });
  }
  
  loadGroups()
  {
    this.model.groups.fetch();
  }
  
  loadMemberships()
  {
    if (this.model.user.isEmpty())
      this.model.memberships.clear();
    else
      this.model.memberships.fetch({'user_id' : this.model.user.value.id });
  }
}