class CancelApptConfirmController extends MercuryController
{
  constructor(id) { super(id); }
  
  onInit(me)
  {
    var appt = me.model.cancelingAppt;
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
    
    me.view.onOK(
      function()
      {
        alert('gonna cancel without notifying standby.');
      }
    );
    
    me.view.onOKStandby(
      function()
      {
        alert('gonna cancel and notify standby.');
      }
    );
  }
}