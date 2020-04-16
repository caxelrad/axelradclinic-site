class ContactHeaderController extends MercuryController
{
  constructor(id)
  {
    super(id);
  }
  
  onInit(me)
  {
    if (this.model.appt)
    {
      this.model.appt.changed(
        function()
        {
          if (me.model.appt.value == null)
          {
            me.view.contactName = '';
            me.view.contactEmail = '';
            me.view.contactPhone = '';
          }
          else
          {
            me.view.contactName = me.model.appt.value.contact_first_name+' '+me.model.appt.value.contact_last_name;
            me.view.contactEmail = me.model.appt.value.contact_email;
            me.view.contactPhone = me.model.appt.value.contact_phone;
          }
          
        }
      );
    }
    
    this.model.emailTemplates.changed(
      function()
      {
        me.view.setEmailTemplateValues(me.model.emailTemplates.value);
      }
    );
    
    me.view.setEmailTemplateValues(me.model.emailTemplates.value);
  }
  
}