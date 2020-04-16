class AppHeaderController extends MercuryController
{
  constructor(id)
  {
    super(id);
  }
  
  onInit(me)
  {
    me.view.title = 'Dashboard';
  }
  
}