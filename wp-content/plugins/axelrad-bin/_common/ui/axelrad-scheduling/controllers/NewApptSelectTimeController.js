class NewApptSelectTimeController extends MercuryController
{
  constructor(id) 
  { 
    super(id);
    this.initCount = 0;
  }
  
  
  onInit(me)
  {
    this.view.noTimesMsg = "There are no available times during normal hours on <b>{date}</b> in <b>{location}</b>.";
    
    this.view.onTimeSelected(
      function()
      {
        if (me.view.selectedTime.appts.length > 0)
        {
          
        }
        else
        {
          var time = Date.fromString(me.view.selectedTime.datetime);
          me.view.confirmTimeDisplay = time.prettyDateTime();
          me.view.apptTypeName = me.model.apptType.value.name;
          me.view.apptTypeColor = me.model.apptType.value.color;
          me.view.confirmTime();
        }
      }
    );
    
    this.view.onTimeConfirmed(
      function()
      {
        //add it to model and move on b/c this is a new pt we only book one at a time.
        me.model.bookedTimes.value = [me.view.selectedTime];
      }
    );
    
    this.view.onDateChanged(
      function()
      {
        me.view.displayDate = me.view.date.toDateString();
        me.model.date.value = me.view.date;
      }
    );
    
    this.view.onLocationChanged(
      function()
      {
        me.model.location.value = me.model.locations.findRow(me.view.locationId);
      }
    );
    
    me.model.date.changed(
      function()
      {
        me.loadTimes();
      }
    );
    
    me.model.location.changed(
      function()
      {
        me.loadTimes();
        me.view.locationName = me.model.location.value.name;
      }
    );
    
    me.model.timeSlots.changed(
      function()
      {
        var times = me.model.timeSlots.value;
        for (var i = 0; i < times.length; i++)
        {
          var time = Date.fromString(times[i].datetime);
          times[i].uses_template = times[i].appts.length > 0 ? 'taken' : 'default';
          times[i].pretty_date = time.prettyDate();
          times[i].pretty_time = time.prettyTime();
          times[i].location_name = me.model.location.value.name;
          times[i].type_name = me.model.apptType.value.name;
          times[i].type_color = me.model.apptType.value.color;
          times[i].type_text_color = u.getTextColor(me.model.apptType.value.color);
          if (times[i].appts.length == 1)
          {
            times[i].display_info = times[i].pretty_time+' - '+times[i].appts[0].contact_first_name+' '+times[i].appts[0].contact_last_name+
              ' ('+times[i].appts[0].type_name+')';
            times[i].type_color = times[i].appts[0].type_color;
            //times[i].text_ = times[i].appts[0].type_color;
          }
          else if (times[i].appts.length > 1)
            times[i].display_info = times[i].pretty_time+' - '+times[i].appts.length+' people';
          else 
            times[i].display_info = times[i].pretty_time;
        }
        
        me.view.setTimes(times);
      }
    );
    
    me.model.locations.changed(
      function()
      {
        me.log('setting locations on the view...');
        me.view.setLocations(me.model.locations.value);
        me.view.locationId = me.model.location.value.id;
      }
    );
    
    this.view.date = me.model.date.value;
    
    var locOp = operations.get('LoadCalendarsOperation', me.model.locations);
    locOp.run();
    
  }
  
  loadTimes()
  {
    if (this.initCount == 0)
    {
      this.initCount = 1;
      return;
    }
    //call the operation now....
    var op = operations.get('GetTimeSlotsOperation', this.model.timeSlots);
    op.date = this.model.date.value;
    op.calendarId = this.model.location.value.id;
    op.run();
  }
  
}