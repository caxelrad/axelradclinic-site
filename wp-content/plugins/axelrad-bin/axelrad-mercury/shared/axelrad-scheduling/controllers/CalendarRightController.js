class CalendarRightController extends MercuryController
{
  constructor(id) { super(id); }
  
  onInit(me)
  {
    this.model.appts.onSelectionChanged(
        function()
        {
          me.view.selectedCount = me.model.appts.selectedKeys.length;
        }
      );
  }
}