class ReschedApptDialogController extends MercuryController
{
  constructor(id) { super(id); }
  
  onInit(me)
  {
    var appt = me.model.reschedAppt;
    appt.changed(
      function()
      {
        if (appt.value != null)
        {
          me.view.apptName = appt.value.contact_first_name+' '+appt.value.contact_last_name;
          me.view.showModal();
        }
        else
        {
          me.view.hideModal();
        }
      }
    );
  }
}