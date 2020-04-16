class NewApptModel extends MercuryModel
{
  
  constructor(name)
  {
    super(name);
    
    this.forNewPatient = new MercuryModelProp(this, 'forNewPatient', false);
    
    this.apptType = new MercuryModelProp(this, 'apptType', null);
    this.location = new MercuryModelProp(this, 'location', null);
    this.date = new MercuryModelProp(this, 'date', new Date());
    
    this.locations = new MercuryModelList(this, 'locations', 'id', []);
    
    this.timeSlots = new MercuryModelList(this, 'timeSlots', 'time', []);
    
    this.patientList = new MercuryModelList(this, 'patientList', 'id', null);
    this.patient = new MercuryModelProp(this, 'patient', null);
    this.patientValid = new MercuryModelProp(this, 'patientValid', false);
    
    this.initialFirstName = new MercuryModelProp(this, 'initialFirstName', '');
    this.initialLastName = new MercuryModelProp(this, 'initialLastName', '');
    
    this.bookedTimes = new MercuryModelList(this, 'bookedTimes', 'datetime', null);
  }
  
  onInit(me)
  {
    this.apptType.value = JSON.parse(u.param('t'));
    this.location.value = u.parseJson(u.param('l'));
    this.date.value = u.param('d') ? Date.fromString(u.param('d')) : new Date();
    this.forNewPatient.value = u.param('n') == '1';
  }
  
  getSamplePatientList()
  {
    var pts = [];
    for (var i = 0; i < 10; i++)
    {
      pts.push(
        {
          'id' : 3432+i,
          'first_name' : 'Julie '+i, 
          'last_name' : 'Jones ' +i,
          'email' : 'chris@chrisaxelrad.com',
          'phone' : '8326897059'
        }
      );
    }
    
    return pts;
  }
}