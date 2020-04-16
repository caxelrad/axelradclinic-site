class ApptDetailViewController extends MercuryController
{
  constructor(id) { super(id); }
  
  onInit(me)
  {
    
    this.model.appt.changed(
      function()
      {
        if (me.model.appt.value == null)
        {
          me.view.clear();
        }
        else
        {
          var appt = me.model.appt.value;
          var status = me.model.getStatus(appt.status);
          
          me.view.title = appt.contact_first_name+' '+appt.contact_last_name;
          me.view.apptTypeId = appt.type_id;
          me.view.apptTypeName = appt.type_name;
          me.view.apptStatusName = status.name;
          me.view.apptStatusBtnColor = status.color;
          me.view.apptStatusBtnTextColor = status.text_color;
          me.view.statusBtnVisible = u.sameDate(appt.start, new Date());
          
        }
      }
    );
    
    this.model.apptTypes.changed(
      function()
      {
        me.view.setApptTypes(me.model.apptTypes.value);
      }
    );
    
    this.model.apptStatusValues.changed(
      function()
      {
        me.view.setApptStatusValues(me.getButtonStatus());
      }
    );
    
    this.view.onCancelApptClicked(
      function()
      {
        me.model.appt.copyTo(me.model.cancelingAppt);
      }
    );
    
    this.view.onReschedApptClicked(
      function()
      {
        me.model.appt.copyTo(me.model.reschedAppt);
      }
    );
    
    me.view.setApptStatusValues(this.getButtonStatus()); //initialize them
  }
  
  getButtonStatus()
  {
    var status = [];
    var values = this.model.apptStatusValues.value;
    for (var index in values)
    {
      var value = values[index];
      if (value.name == 'pending' ||
          value.name == 'arrived' ||
          value.name == 'in-progress' ||
          value.name == 'running-late')
        status.push(value);
    }
    return status;
  }
  
}