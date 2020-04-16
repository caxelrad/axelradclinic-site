class GroupsAppModel extends MercuryModel
{
  
  constructor(name)
  {
    super(name);
    
    this.group = new MercuryModelProp(this, 'group', null);
    this.groups = new MercuryModelList(this, 'groups', 'id', []);
    //this.group_member_count = new MercuryModelProp(this, 'group_member_count', 0);
    
    this.newGroup = new MercuryModelProp(this, 'newGroup', null);
  }
  
  onInit(me)
  {
    
  }
}
