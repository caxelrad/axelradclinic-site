<?php 

AxBin::load('axelrad-commands');

AxelradCommands::register('ax-reset', 'ax_test_reset');
AxelradCommands::register('ax-create-contact', 'ax_test_create_contact');
AxelradCommands::register('ax-create-user', 'ax_test_create_user');
AxelradCommands::register('ax-create-group', 'ax_test_create_group');
AxelradCommands::register('ax-list-groups', 'ax_test_list_groups');
AxelradCommands::register('ax-create-chart', 'ax_test_create_chart_note');
AxelradCommands::register('ax-delete-contact', 'ax_test_delete_contact');
AxelradCommands::register('ax-find-contact', 'ax_test_find_contact');
AxelradCommands::register('ax-create-contacts', 'ax_test_create_contacts');
AxelradCommands::register('ax-find-user', 'ax_test_find_user');
AxelradCommands::register('ax-add-member', 'ax_test_add_to_group');
AxelradCommands::register('ax-list-securables', 'ax_test_list_securables');

function ax_test_reset()
{
    AxelradUserMgmt::delete_group('all-users');
    wp_cache_delete('ax-default-group');

    $id = AxelradUserMgmt::find_user_id_by_email('chris@chrisaxelrad.com');
    AxelradUserMgmt::delete_user($id);

    _ax_debug('it is all reset');
}

function ax_test_list_securables()
{
    $category_id = $_GET['cat'] ? $_GET['cat'] : 0;

    $list = AxUserMgmtAccessCtrl::get_securables_external($category_id);
    _ax_debug('$list = '.json_encode($list));
    foreach ($list as $item)
    {
        if ($item['type'] == 'category')
        {
            _ax_debug('Got category: <a href="'.AxelradCommands::get_cmd_url('ax-list-securables', ['cat' => $item['id']]).'">'.$item['name'].'</a>');
        }
        else
        {
            _ax_debug($item['title']);
        }
    }
}
function ax_test_create_group()
{
    $id = AxelradUserMgmt::group_create_external('chris-test-2', 'Chris Test 2', 'A test group by Chris.');
    _ax_debug('group created with id '.$id);
}

function ax_test_list_groups()
{
    $list = AxelradUserMgmt::groups_fetch_internal($_GET['search']);
    foreach ($list as $item)
        _ax_debug(json_encode($item));
}

function ax_test_create_user()
{
    $id = AxelradUserMgmt::create_user('chris@chrisaxelrad.com', 'Chris', 'Axelrad');
    _ax_debug('user created with id '.$id);
}

function ax_test_find_user()
{
    $id = AxelradUserMgmt::find_user_id_by_email('chris@axelradclinic.com');

    _ax_debug('the user id for chris@axelradclinic.com is: '.$id);
    return $id;
}

function ax_test_add_to_group()
{
    $user_id = ax_test_find_user();

    if (!AxelradUserMgmt::group_exists('test-group'))
    {
        $group_id = AxelradUserMgmt::group_create_external('test-group', 'Test Group', 'Just a test.');
    }
    else 
    {
        $group_id = AxelradUserMgmt::get_group_id_from_name('test-group');
    }

    AxelradUserMgmt::add_group_member($group_id, $user_id);
    _ax_debug('user added to group!');
}

function ax_test_create_contact()
{
    $id = AxClinicData::contact_create('chris.axelrad@gmail.com', 'Chris', 'Axelrad');
}

function ax_test_create_patient()
{
    $id = AxClinicData::find_contacts_with_email('chris.axelrad@gmail.com');
}

function ax_test_create_chart_note()
{
    $contact = R::load('contact', 1);

    $chart = R::dispense('chartnote');
    $chart->content = 'This is a chart note.';
    $chart->datecreated = date('Y-m-d', time());

    $contact->ownChartList[] = $chart;
    R::store($contact);

}
/* function ax_test_create_contact()
{
    AxClinicModel::load('contact');
    
    $contact = new Contact();
    $contact->first_name->value('Jane');
    $contact->last_name->value('Doe');
    $contact->full_name->value('Jane Doe');

    try
    {
        $contact->save();
    }
    catch (Exception $ex)
    {
        _ax_debug('ERROR: '.$ex->getMessage());
    }
} */

function ax_test_create_contacts()
{
    AxClinicModel::load('contact');
    
    for ($i = 1; $i < 11; $i++)
    {
        $contact = new Contact();
        $contact->first_name->value('Jane');
        $contact->last_name->value('Doe'.$i);
        $contact->full_name->value('Jane Doe'.$i);
        try
        {
            $contact->save();
        }
        catch (Exception $ex)
        {
            _ax_debug('ERROR: '.$ex->getMessage());
        }
    }
    
    _ax_debug('contacts created!');
}

function ax_test_delete_contact()
{
    AxClinicModel::load('contact');

    $contact = new Contact($_GET['id']);
    try
    {
        $contact->delete();
        _ax_debug('The contact was deleted.');
    }
    catch (Exception $ex)
    {
        _ax_debug('ERROR: '.$ex->getMessage());
    }
}


function ax_test_find_contact()
{
    _ax_debug('ax_test_find_contact');

    AxClinicModel::load('contact');

    $filter = new AxModelFilter();
    $filter->add('full_name', 'LIKE', 'Jane%', 'str');

    $list = AxClinicModel::find('contact', $filter);
    _ax_debug('there are '.count($list).' contacts matching the filter.');
    foreach ($list as $item)
    {
        _ax_debug(json_encode($item));
    }
    
}