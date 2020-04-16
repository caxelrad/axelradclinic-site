class CalendarMiddleController extends MercuryController
  {
    constructor(id)
    {
      super(id);
    }

    onInit(me)
    {
      
      this.model.locations.changed(
       function(items)
        {
           me.refreshLocations();
        }
      );

      this.model.date.changed(
        function(val)
        {
          me.refreshDate();
        }
      );

      this.model.location.changed(
        function(val)
        {
          me.refreshLocation();
        }
      );
      
      this.view.locationChanged(
        function()
        {
          me.model.location.value = 
            me.model.locations.findRow(me.view.locationId);
        }
      );
      
      //alert(window.model.date.value);
      this.date = me.model.date.value;
      this.refreshLocations();
      this.refreshLocation();
    }

    refreshDate()
    {
      this.view.date = this.model.date.value;
    }
    
    refreshLocation()
    {
      if (this.model.location.value == null) return;
      
      this.view.locationId = this.model.location.value.id;
    }
    
    refreshLocations()
    {
      var dropdownItems = [];
      this.model.locations.value.forEach(
        function(item)
        {
          dropdownItems.push(
            {
              "id" : item.id, 
              "name" : item.name
            }
          );
        }
      );

      this.view.setLocationOptions(dropdownItems);
    }
    
    
  }