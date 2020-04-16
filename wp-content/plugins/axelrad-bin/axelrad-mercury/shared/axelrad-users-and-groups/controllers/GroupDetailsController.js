class GroupDetailsController extends MercuryController
{
  constructor(id) { super(id); }
  
  onInit(me)
  {
    
    this.model.group.changed(
      function() { me.syncGroup(); }
    );
    
    this.model.group.deleted(
      function() { me.syncGroup(); }
    );
    
    this.view.onEditClick(
      function()
      {
        me.model.group.beginUpdate(); //.value = me.model.groups.selectedRows[0];
      }
    );
    this.view.onDeleteClick(
      function()
      {
        me.model.group.beginDelete(); //.value = me.model.groups.selectedRows[0];
      }
    );
    
    this.model.groups.onSelectionChanged(
      function() { me.sync(); }
    );
    
    this.sync();
  }
  
  
  
  syncGroup()
  {
    if (this.model.group.value != null)
    {
      this.view.groupCount = 1;
      this.view.groupDisplayName = this.model.group.value.display_name;
      this.view.groupName = this.model.group.value.name;
      this.view.description = this.model.group.value.description;
      this.view.memberCount = this.model.group.value.member_count;
    }
    else
    {
      this.view.groupCount = 0;
    }
  }
  
  sync()
  {
    var count = this.model.groups.selectedCount;
    this.view.groupCount = count;
    if (count != 1)
      this.view.title = (count == 0 ? 'No' : count) + ' groups selected'; 
  }
}