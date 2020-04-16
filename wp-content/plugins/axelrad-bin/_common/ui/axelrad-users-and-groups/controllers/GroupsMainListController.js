class GroupsMainListController extends MercuryController
{
  constructor(id)
  {
    super(id);
  }
  
  onInit(me)
  {
    
    this.model.groups.changed(
      function()
      {
        me.refresh();
      }
    );
    
    
    this.view.onSelectionChanged(
      function()
      {
        //me.model.appts.clearSelection();
        me.model.groups.selectRows(me.view.selectedKeys); 
      }
    );
    
    
    this.refresh();
    
  }
  
  refresh()
  {
    this.view.setGroups(this.model.groups.value);
    if (this.model.newGroup.value != null)
      this.view.selectRow(this.model.newGroup.value.id);
  }

}