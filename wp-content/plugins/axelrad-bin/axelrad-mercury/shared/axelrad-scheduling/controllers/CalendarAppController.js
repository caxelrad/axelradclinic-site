class CalendarAppController extends MercuryController
{
  constructor(id)
  {
    super(id);
    this.lastDate = new Date();
    this.lastLocationId = 0;
  }
  
  onInit(me)
  {
    
    me.model.locations.fetched(
      function(list)
      {
        if (me.model.location.value == null)
          me.model.location.value = list[0];
      }
    );
    
    me.model.location.changed(
      function()
      {
        me.loadApptTypes();
        me.tryLoadAppts();
      }
    );
    
    me.model.date.changed(
      function()
      {
        me.tryLoadAppts();
      }
    );
    
    me.model.appts.fetched(
      function()
      {
        var appts = me.model.appts.value;
        for (var index in appts)
        {
          me.model.appts.value[index].start = me.setApptDateVal(appts[index]); 
          me.model.appts.value[index].status_text_color = 
            me.model.getStatusTextColor(appts[index].status_color);
        }
      }
    );
    
    me.model.appts.onRowChanged(
      function(row)
      {
        row.start = me.setApptDateVal(row);
      }
    );
    
    me.model.apptStatusValues.fetched(
      function()
      {
        var values = me.model.apptStatusValues.value;
        for (var index in values)
        {
          me.model.apptStatusValues.value[index].text_color = 
            me.model.getStatusTextColor(values[index].color);
        }
      }
    );
    
    me.model.newApptTypeId.changed(
      function()
      {
        var type = me.model.getApptType(me.model.newApptTypeId.value);
        var typeVal = encodeURIComponent(JSON.stringify(type));
        var location = encodeURIComponent(JSON.stringify(me.model.location.value));
        var dt = encodeURIComponent(me.model.date.value.toServerString(false));
        window.open('/new-appointment/?n=1&d='+dt+'&t='+typeVal+'&l='+location, '_blank').focus();
      }
    );
    
    me.model.date.value = new Date(); //show todays date as the starting point for now.
    me.loadCalendars();
    me.loadApptStatusValues();
    me.loadEmailTemplates();
    me.tryLoadAppts();
    
    
  }
  
  loadApptTypes()
  {
    this.model.newApptTypes.fetch();
    this.model.apptTypes.fetch();
    var op = window.operations.get('LoadApptTypesOperation');
    op.newAppts = true;
    op.calendarId = this.model.location.value.id;
    op.run();
    
    op = window.operations.get('LoadApptTypesOperation');
    op.calendarId = this.model.location.value.id;
    op.newAppts = false;
    op.run();
  }
  
  loadEmailTemplates()
  {
    window.operations.get('LoadEmailTemplatesOperation').run();
  }

  loadCalendars()
  {
    window.operations.get('LoadCalendarsOperation', this.model.locations).run();
  }
  
  loadApptStatusValues()
  {
    window.operations.get('LoadApptStatusOperation').run();
  }
  
  setApptDateVal(appt)
  {
    if (typeof appt.start == 'string')
      return u.parseDate(appt.start); //converts to a javascript date object
    else 
      return appt.start
    
  }
  
  tryLoadAppts()
  {
    if (this.model.location.value != null &&
        this.model.date.value != null)
    {
      if (this.model.location.value.id != this.lastLocationId ||
        this.model.date.value != this.lastDate)
        {
          var op = window.operations.get('LoadApptsOperation');
          this.lastLocationId = op.locationId = this.model.location.value.id;
          this.lastDate = op.date = this.model.date.value;
          op.run();
        }
    }

  }
}