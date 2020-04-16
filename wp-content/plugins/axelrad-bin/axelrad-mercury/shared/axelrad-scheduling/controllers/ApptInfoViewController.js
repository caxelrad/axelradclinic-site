class ApptInfoViewController extends MercuryController
{
  constructor(id) { super(id); }
  
  onInit(me)
  {
    var sourceApptName = this.view.sourceApptName;
    var sourceAppt = this.model[sourceApptName]; //i.e. model.appt or model.newAppt or whatever...
    sourceAppt.changed(
      function()
      {
        if (sourceAppt.value == null)
        {
          me.view.clear();
        }
        else
        {
          var appt = sourceAppt.value;
          var status = me.model.getStatus(appt.status);
          me.view.apptTypeName = appt.type_name;
          me.view.apptTypeColor = appt.type_color;
          me.view.apptStatusText = status.display_name;
          me.view.apptStatusColor = status.color;
          me.view.apptStatusTextColor = status.text_color;
          me.view.locationName = appt.calendar_name;
          me.view.displayDate = appt.friendly_date;
          me.view.statusVisible = sourceApptName == 'appt';
        }
      }
    );
  }
}