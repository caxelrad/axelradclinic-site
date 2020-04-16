class ApptMultiDetailViewController extends MercuryController
{
  constructor(id) { super(id); }
  
  onInit(me)
  {
    this.model.appts.onSelectionChanged(
      function()
      {
        var count = me.model.appts.selectedKeys.length;
        if (count > 1)
        {
          me.view.title = count+' appointments selected';
        }
      }
    );  
  }
  
}