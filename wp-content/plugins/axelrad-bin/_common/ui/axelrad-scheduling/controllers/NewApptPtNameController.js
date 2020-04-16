class NewApptPtNameController extends MercuryController
{
  constructor(id)
  {
    super(id);
  }
  
  onInit(me)
  {
    
    me.view.onSubmit(
     function()
     {
       //here we do the search and update the model and when model updates we move to another page...
       var operation = operations.get('FindPtsOperation', me.model.patientList);
       me.model.initialFirstName.value = operation.firstName = me.view.firstName;
       me.model.initialLastName.value = operation.lastName = me.view.lastName;
       operation.run();
     }
    );
    
    me.view.onNameChanged(
      function()
      {
        me.view.submitEnabled = me.view.firstName.length > 2;
      }
    );
  }
}