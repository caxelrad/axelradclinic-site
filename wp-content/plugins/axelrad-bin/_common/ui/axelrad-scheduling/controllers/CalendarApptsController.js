class CalendarApptsController extends MercuryController
{
  constructor(id)
  {
    super(id);
  }
  
  onInit(me)
  {
    
    var items = [];
    this.model.appts.changed(
      function(model_list)
      {
        me.refreshAppts();
      }
    );
    
    
    this.view.onSelectionChanged(
      function()
      {
        //me.model.appts.clearSelection();
        me.model.appts.selectRows(me.view.selectedKeys); 
      }
    );
    
    this.refreshAppts();
    
  }
  
  refreshAppts()
  {
    var me = this;
    var appts = [];
    
    this.model.appts.value.forEach(
      function(model_item)
      {
        var appt = [];
        for (var key in model_item)
        {
          appt[key] = model_item[key];
        }
        
        appt.name = model_item.contact_first_name+' '+model_item.contact_last_name;
        appts.push(appt);
      }
    );
    
    this.view.setAppts(appts);
  }
  
  getTypeName(val)
  {
    if (val == 'f')
      return 'Follow-Up';
    else
      return 'Intake';
  }

  getStatusName(val)
  {
    return val.charAt(0).toUpperCase() + val.slice(1);
  }

  getTypeColor(val)
  {
    if (val == 'f')
      return "#AFE7D3";
    else 
      return "#D5B3DC";
  }
}