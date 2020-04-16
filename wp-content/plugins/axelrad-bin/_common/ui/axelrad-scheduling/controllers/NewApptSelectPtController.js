class NewApptSelectPtController extends MercuryController
{
  constructor(id)
  {
    super(id);
  }
  
  onInit(me)
  {
    me.model.patientList.changed(
      function()
      {
        me.view.setItems(me.model.patientList.value);
      }
    );
    
    me.view.onNotFound(
      function()
      {
        //go with the name they entered at tne beginning...
        me.model.patient.value = 
        { 
          'id' : 0,
          'first_name' : me.model.initialFirstName.value,
          'last_name' : me.model.initialLastName.value
        };
      }
    );
    
    me.view.onSelectionChanged(
      function()
      {
        me.model.patient.value = me.view.selectedItem;
      }
    );
    
    me.view.onBack(
      function()
      {
        me.model.patientList.value = null; //clear out the list and it will go back
      }
    );
  }
}