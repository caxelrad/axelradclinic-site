class GroupDetailsController extends MercuryController
{
  constructor(id) { super(id); }
  
  onInit(me)
  {
    
    this.model.group.changed(
      function() { me.syncGroup(); }
    );
    
    this.model.group.deleted(
      function() { me.syncView(); }
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
      function() 
      { 
        me.syncView();
      }
    );
    
    this.syncView();
  }
  
  syncGroup()
  {
    var group = this.model.group.value;
    var v = this.view;
    if (group != null)
    {
      v.title = group.display_name;
      v.subtitle = group.name;
      v.description = group.description;
      v.memberCount = group.member_count;
    }
  }
  syncView()
  {
    var count = this.model.groups.selectedCount;
    this.view.groupCount = count;
    
    if (count == 0)
    {
      this.view.showNone = true;
    }
    else if (count == 1)
    {
      this.view.showSingle = true;
    }
    else
    {
      this.view.showSingle = true;
    }
    
  }
}