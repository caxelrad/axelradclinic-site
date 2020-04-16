class NewApptAppController extends MercuryController
{
  constructor(id)
  {
    super(id);
  }
  
  onInit(me)
  {
  
    
    me.model.bookedTimes.changed(
      function()
      {
        me.view.appt_times.setItems(me.model.bookedTimes.value);
      }
    );
    
    me.model.patientList.changed(
      function()
      {
        me.log('detected patientList change');
        if (me.model.patientList.value == null)
          me.view.getPtName();
        else 
        {
          if (me.model.patientList.value.length > 0)
            me.view.showPtMatches();
          else 
            me.view.showPtInfo();
        }
      }
    );
    
    me.model.patientValid.changed(
      function()
      {
        if (!me.model.patientValid.value)
          me.view.showPtInfo(); //go ahead and confirm the information anyway...  
        else
          me.view.showBooking();
      }
    );
    
    me.model.patient.changed(
      function()
      {
        if (me.model.patient.value != null)
        {
          var pt = me.model.patient.value;
          me.view.patientName = pt.first_name+' '+pt.last_name;
          
          if (!me.model.patientValid.value)
            me.view.showPtInfo(); //go ahead and confirm the information anyway...  
          else
            me.view.showBooking();
        }
        else 
        {
          me.view.patientName = '';
          if (me.model.patientList.value != null && 
              me.model.patientList.value.length > 0)
            me.view.showPtMatches();
          else
            me.view.getPtName();
        }
      }
    );
    
    me.model.apptType.changed(
      function()
      {
        me.view.typeColor = me.model.apptType.value.color;
        me.view.typeName = me.model.apptType.value.name;
      }
    );
    
    me.view.onCancel(
      function()
      {
        window.opener.focus();
        window.close();
      }
    );
   
    me.view.onRemoveTime(
      function(datetime)
      {
        var time = me.model.bookedTimes.findRow(datetime);
        me.view.locationName = me.model.location.value.name;
        me.view.displayTime = time.pretty_date+' at '+time.pretty_time;
        me.view.confirmRemove(time);
      }
    );
    
    me.view.onRemoveConfirmed(
      function()
      {
        me.model.bookedTimes.value = [];
      }
    );
    
    me.view.typeColor = me.model.apptType.value.color;
    me.view.typeName = me.model.apptType.value.name;
    me.view.showBooking();
  }
}