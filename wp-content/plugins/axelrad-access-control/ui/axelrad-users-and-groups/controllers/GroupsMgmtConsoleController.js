class GroupsMgmtConsoleController extends MercuryController
{
  constructor(id)
  {
    super(id);
  }
  
  onInit(me)
  {
    
    
    me.model.groups.onSelectionChanged(
      function()
      {
        me.fetchGroup();
      }
    );
    
    me.view.onNewClick(
      function()
      {
        me.model.group.beginCreate();
      }
    );
    
    this.model.group.created(
      function()
      {
        me.loadGroups();
      }
    );
    
    this.model.group.updated(
      function()
      {
        me.loadGroups();
      }
    );
    
    me.model.group.deleted(
      function()
      {
        me.loadGroups();
      }
    );
    
    me.loadGroups();
    
  }
  
  fetchGroup()
  {
    var count = this.model.groups.selectedCount;
    if (count == 1)
    {
      this.model.group.value = {'id' : this.model.groups.selectedRows[0].id };
      this.model.group.fetch();
    }
    else
      this.model.group.clear();
  }
  
  loadGroups()
  {
    this.model.groups.fetch();
  }
  
}