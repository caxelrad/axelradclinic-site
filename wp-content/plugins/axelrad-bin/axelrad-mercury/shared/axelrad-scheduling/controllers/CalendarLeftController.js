class CalendarLeftController extends MercuryController
{
  constructor(id)
  {
    super(id);
  }
  
  onInit(me)
  {
    me.view.onDateChanged(
      function(val)
      {
        me.model.date.value = me.view.date;
      }
    );
    
    this.model.newApptTypes.changed(
      function()
      {
        me.view.setApptTypes(me.model.newApptTypes.value);
      }
    );
    
    me.view.onNewApptClick(
      function()
      {
        me.model.newApptTypeId.value = me.view.selectedApptTypeId;
      }
    );
  }
  
}