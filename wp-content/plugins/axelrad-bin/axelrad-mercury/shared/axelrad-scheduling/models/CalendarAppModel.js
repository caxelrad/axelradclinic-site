class CalendarAppModel extends MercuryModel
{
  
  constructor(name)
  {
    super(name);
   
    const ERR_APPT_CANCEL = 'err-appt-cancel';
    const ERR_APPTS_LOAD = 'err-appts-load';
    
    this.date = new MercuryModelProp(this, 'date', new Date());
    this.location = new MercuryModelProp(this, 'location', null);
    this.appt = new MercuryModelProp(this, 'appt', null);
    this.cancelingAppt = new MercuryModelProp(this, 'cancelingAppt', null);
    this.reschedAppt = new MercuryModelProp(this, 'reschedAppt', null);
    
    this.appts = new MercuryModelList(this, 'appts', 'id', []);
    this.locations = new MercuryModelList(this, 'locations', 'id', []);
    this.apptTypes = new MercuryModelList(this, 'apptTypes', 'id', []);
    this.newApptTypes = new MercuryModelList(this, 'newApptTypes', 'id', []);
    
    this.apptStatusValues = new MercuryModelList(this, 'apptStatusValues', 'name', []);
    
    this.emailTemplates = new MercuryModelList(this, 'emailTemplates', 'id', []);
    
    this.newApptTypeId = new MercuryModelProp(this, 'newApptTypeId', null);
  }
  
  onInit(me)
  {
    this.appts.onSelectionChanged(
      function()
      {
        if (me.appts.selectedKeys.length > 1)
        {
          me.appt.value = null;
        }
        else
        {
          me.appt.value = me.appts.findRow(me.appts.selectedKeys[0]);
        }
      }
    );
  }
  
  getStatus(name)
  {
    var me = this;
    var values = this.apptStatusValues.value;
    for (var index in values)
    {
      if (values[index].name == name)
      {
        return values[index];
      }
    }
    
    return {};
  }
  
  getStatusTextColor(status_color)
  {
    return tinycolor(status_color).getBrightness() > 175 ? '#000': '#fff'; 
  }
  
  getApptType(typeId)
  {
    var type = this.apptTypes.findRow(typeId);
    if (type != null)
      return type;
    
    type = this.newApptTypes.findRow(typeId);
    if (type != null)
      return type;
    
    return null;
  }
  
  getApptTypeColor(typeId)
  {
    
    var type = this.getApptType(typeId);
    if (type != null)
      return type.color;
    
    return '';
  }
  
  
  _notifyError(code, message)
  {
    this.fire('on-error', this._getError(code, message));
  }
  
  onError(handler)
  {
    this.bind('on-error', handler);
  }
}
