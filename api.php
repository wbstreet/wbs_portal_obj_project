<?php

require_once(__DIR__.'/lib.class.portal_obj_project.php');

$action = $_POST['action'];

//$section_id = $clsFilter->f('section_id', [['integer', "Не указана секция!"]], 'fatal');
//$page_id = $clsFilter->f('page_id', [['integer', "Не указана страница!"]], 'fatal');

require_once(WB_PATH."/framework/class.admin.php");
$admin = new admin('Start', '', false, false);
$clsModPortalObjProject = new ModPortalObjProject(null, null);

include_once(WB_PATH.'/framework/class.order.php');
$order = new order(substr($clsModPortalObjProject->tbl_project_road, 1, -1), 'position', 'road_id', 'obj_id');

function get_obj_id_new() {
    global $clsModPortalObjProject, $admin;
    $where = [
        "{$clsModPortalObjProject->tbl_obj_settings}.`obj_id`={$clsModPortalObjProject->tbl_project}.`obj_id`",
        "{$clsModPortalObjProject->tbl_obj_settings}.`user_owner_id`=".process_value($admin->get_user_id()),
        '`is_created`=0',
        ];
    $tables = [$clsModPortalObjProject->tbl_project, $clsModPortalObjProject->tbl_obj_settings];
    $r = select_row($tables, $clsModPortalObjProject->tbl_project.'.`obj_id`', implode($where, ' AND '));
    if (gettype($r) === 'string') print_error($r);
    if ($r === null) print_error('Проект не найден!');
    
    return $r->fetchRow()['obj_id'];
}

function check_access_subobj($table, $subobj_id, $key) {
    global $admin, $clsModPortalObjProject;
    
    $r = select_row([
        $table,
        $clsModPortalObjProject->tbl_project,
        $clsModPortalObjProject->tbl_obj_settings,
        ], $clsModPortalObjProject->tbl_obj_settings.".`user_owner_id`", implode(' AND ', [
            $table.".`obj_id`=".$clsModPortalObjProject->tbl_project.".`obj_id`",
            $clsModPortalObjProject->tbl_obj_settings.".`obj_id`=".$clsModPortalObjProject->tbl_project.".`obj_id`",
            $table.".".$key."=".process_value($subobj_id),
            $clsModPortalObjProject->tbl_obj_settings.".`user_owner_id`=".$admin->get_user_id(),
            ]));
    if (gettype($r) === 'string') print_error($r);
    if ($r === null) print_error('Не существует!');
}

if ($action == 'create_project') {

    check_auth(); //check_all_permission($page_id, ['pages_modify']);
    $clsFilter->f('captcha', [['1', "Введите Защитный код!"], ['variants', "Введите Защитный код!", [$_SESSION['captcha']]]], 'fatal', '');
    
    $obj_id = get_obj_id_new();

    $r = update_row($clsModPortalObjProject->tbl_project, ['is_created'=>1], "`obj_id`=".process_value($obj_id));
    if (gettype($r) === 'string') print_error($r);

    print_success('Проект создан!', ['data'=>['skill'=>$skill, 'skill_id'=>$skill_id], 'absent_fields'=>[]]);

} else if ($action == 'update_project') {

    check_auth();
    
    $obj_id = $clsFilter->f('obj_id', [['integer', 'Укажите проект!']], 'append');
    $name = $clsFilter->f('name', [['1', 'Укажите имя поля!']], 'append');
    $value = $clsFilter->f('value', [['1', 'Укажите значение поля!']], 'append');
    if ($clsFilter->is_error()) $clsFilter->print_error();    

    if ($name == 'is_active') {
        $value = $value === 'true' ? '1' : '0';
    }

    $fields = [ $name=>$value, ];
    
    $_fields = $clsModPortalObjProject->split_arrays($fields);

    if ($fields)  $r = update_row($clsModPortalObjProject->tbl_project,  $fields,  "`obj_id`=".process_value($obj_id));
    else $r = update_row($clsModPortalObjProject->tbl_obj_settings, $_fields, "`obj_id`=".process_value($obj_id));

    if (gettype($r) === 'string') print_error($r);

    print_success('Успешно!');

} else if ($action == 'update_image_project') {

    check_auth();
    
    $obj_id = $clsFilter->f('obj_id', [['integer', 'Укажите проект!']], 'append');
    if ($clsFilter->is_error()) $clsFilter->print_error();    

    $img_id = $clsStorageImg->save($_FILES['image']['tmp_name']);
    if (gettype($img_id) === 'string') print_error($img_id);

    $r = update_row($clsModPortalObjProject->tbl_project,  ['storage_image_id'=>$img_id],  "`obj_id`=".process_value($obj_id));
    if (gettype($r) === 'string') print_error($r);
    

    print_success('Успешно!');

} else if ($action == 'cancel_project') {

    check_auth();

    $obj_id = get_obj_id_new();

    // помечаем проект удалённым

    $r = update_row($clsModPortalObjProject->tbl_project, $clsModPortalObjProject->default_fields, "`obj_id`=".process_value($obj_id));
    if (gettype($r) === 'string') print_error($r);

    // помечапем все задачи из дорожной карты удылёнными

    $r = update_row($clsModPortalObjProject->tbl_project_road, ['is_deleted'=>'1', 'obj_id'=>'0'], '`obj_id`='.process_value($obj_id));
    if (gettype($r) === 'string') print_error($r);

    // помечапем все требуемые ресурсы удылёнными

    $r = update_row($clsModPortalObjProject->tbl_project_resource, ['is_deleted'=>'1', 'obj_id'=>null, 'user_id'=>null], '`obj_id`='.process_value($obj_id));
    if (gettype($r) === 'string') print_error($r);

    print_success('Успешно!');

/* ------------------------------
------- Дорожная карта
----------------------------------*/

} else if ($action == 'add_task') {
    
    check_auth();

    $obj_id = $clsFilter->f('obj_id', [['1', 'Укажите проект!']], 'append');
    $text = $clsFilter->f('text', [['1', 'Укажите задачу!']], 'append');
    if ($clsFilter->is_error()) $clsFilter->print_error();

    // опрределяем новую позицию

    $fields = [
        'text'=>$text,
        'obj_id'=>$obj_id,
        'position'=>$order->get_new($obj_id),
        'is_done'=>'0',
    ];
    $road_id = insert_row_uniq_deletable($clsModPortalObjProject->tbl_project_road, $fields, ["obj_id", 'text'], 'road_id');
    if (gettype($road_id) === 'string') print_error($road_id);

    print_success('Успешно!', ['data'=>['road_id'=>$road_id], 'absent_fields'=>[]]);

} else if ($action == 'delete_task') {

    check_auth();

    $road_id = $clsFilter->f('road_id', [['1', 'Укажите задачу!']], 'fatal');
    check_access_subobj($clsModPortalObjProject->tbl_project_road, $road_id, 'road_id');

    $r = update_row($clsModPortalObjProject->tbl_project_road, ['is_deleted'=>'1', 'obj_id'=>'0'], '`road_id`='.process_value($road_id));
    if (gettype($r) === 'string') print_error($r);

    print_success('Успешно!');

} else if ($action == 'toggle_task') {

    check_auth();

    $road_id = $clsFilter->f('road_id', [['1', 'Укажите задачу!']], 'fatal');
    check_access_subobj($clsModPortalObjProject->tbl_project_road, $road_id, 'road_id');

    // получаем текущее состояние

    $r = select_row($clsModPortalObjProject->tbl_project_road, '`is_done`', '`road_id`='.process_value($road_id));
    if (gettype($r) === 'string') print_error($r);
    if ($r === null) print_error('Задача не найдена!');

    // инвертируем и обновляем
    
    $is_done = $r->fetchRow()['is_done'] === '1' ? '0' : '1';
    
    $r = update_row($clsModPortalObjProject->tbl_project_road, ['is_done'=>$is_done], '`road_id`='.process_value($road_id));
    if (gettype($r) === 'string') print_error($r);

    print_success('Успешно!', ['data'=>['is_done'=>$is_done]]);

} else if ($action == 'move_down_task') {

    check_auth();

    $road_id = $clsFilter->f('road_id', [['1', 'Укажите задачу!']], 'fatal');
    check_access_subobj($clsModPortalObjProject->tbl_project_road, $road_id, 'road_id');

    $order->move_down($road_id);

    print_success('');

} else if ($action == 'move_up_task') {

    check_auth();

    $road_id = $clsFilter->f('road_id', [['1', 'Укажите задачу!']], 'fatal');
    check_access_subobj($clsModPortalObjProject->tbl_project_road, $road_id, 'road_id');

    $order->move_up($road_id);

    print_success('');

/* ------------------------------
------- Потребногсти (требуемые ресурсы)
----------------------------------*/

} else if ($action == 'add_resource') {

    check_auth();

    $obj_id = $clsFilter->f('obj_id', [['1', 'Укажите проект!']], 'append');
    $rcategory_id = $clsFilter->f('rcategory_id', [['1', 'Укажите категорию!']], 'append');
    $rname = $clsFilter->f('rname', [['1', 'Укажите задачу!']], 'append');
    if ($clsFilter->is_error()) $clsFilter->print_error();

    $fields = [
        'obj_id'=>$obj_id,
        'resource_category_id'=>$rcategory_id,
        'resource_name'=>$rname,
        'resource_needme'=>'1',
    ];
    $resource_id = insert_row_uniq_deletable($clsModPortalObjProject->tbl_project_resource, $fields, ["obj_id", 'resource_category_id', 'resource_name'], 'resource_id');
    if (gettype($resource_id) === 'string') print_error($resource_id);

    print_success('Успешно!', ['data'=>['resource_id'=>$resource_id], 'absent_fields'=>[]]);

} else if ($action == 'delete_resource') {

    check_auth();

    $resource_id = $clsFilter->f('resource_id', [['1', 'Укажите ресурс!']], 'fatal');
    check_access_subobj($clsModPortalObjProject->tbl_project_resource, $resource_id, 'resource_id');

    $r = update_row($clsModPortalObjProject->tbl_project_resource, ['is_deleted'=>'1', 'obj_id'=>null, 'user_id'=>null], '`resource_id`='.process_value($resource_id));
    if (gettype($r) === 'string') print_error($r);

    print_success('Успешно!');

} else if ($action == 'add_member') {

    check_auth();

    $obj_id = $clsFilter->f('obj_id', [['1', 'Укажите проект!']], 'append');
    $username = $clsFilter->f('username', [['1', 'Укажите пользователя!']], 'append');
    $role = $clsFilter->f('role', [['1', 'Укажите роль!']], 'append');
    if ($clsFilter->is_error()) $clsFilter->print_error();

    $r = select_row("`".TABLE_PREFIX."users`", '*', "`username`=".process_value($username));
    if (gettype($r) === 'string') print_error($r);
    if ($r === null) print_error('Пользователь не найден!');
    $user = $r->fetchRow();

    # member_table=  member_id | user_id | obj_id | role | is_deleted | is_confirmed

    $fields = [
        'obj_id'=>$obj_id,
        'user_id'=>$user['user_id'],
        'role'=>$role,
    ];
    $member_id = insert_row_uniq_deletable($clsModPortalObjProject->tbl_project_member, $fields, ["obj_id", 'user_id'], 'member_id');
    if (gettype($member_id) === 'string') print_error($member_id);

    // вынимаем ссылку на профиль пользователя

    include_once(WB_PATH.'/modules/wbs_portal_obj_profile/lib.class.portal_obj_profile.php');
    $clsModPortalObjProfile = new ModPortalObjProfile(null, null);

    list($profile_link, $bSuccess) = $clsModPortalObjProfile->get_link($user['username']);
    if (!$bSuccess) { $clsModPortalObjProfile->print_error($profile_link); $profile_link = WB_URL;}

    print_success('Успешно!', ['data'=>['member_id'=>$member_id, 'surname'=>$user['surname'], 'name'=>$user['name'], 'profile_link'=>$profile_link], 'absent_fields'=>[]]);    

} else if ($action == 'update_member') {

    check_auth();

    $member_id = $clsFilter->f('member_id', [['1', 'Укажите участника!']], 'fatal');
    check_access_subobj($clsModPortalObjProject->tbl_project_member, $member_id, 'member_id');

    $role = $clsFilter->f('role', [['1', 'Укажите роль!']], 'fatal');

    $r = update_row($clsModPortalObjProject->tbl_project_member, ['role'=>$role], "`member_id`=".process_value($member_id));
    if (gettype($r) === 'string') print_error($r);

    print_success('Успешно!', ['absent_fields'=>[]]);

} else if ($action == 'delete_member') {

    check_auth();

    $member_id = $clsFilter->f('member_id', [['1', 'Укажите участника!']], 'fatal');
    check_access_subobj($clsModPortalObjProject->tbl_project_member, $member_id, 'member_id');

    $r = update_row($clsModPortalObjProject->tbl_project_member, ['is_deleted'=>'1', 'obj_id'=>null], '`member_id`='.process_value($member_id));
    if (gettype($r) === 'string') print_error($r);

    print_success('Успешно!');

} else { print_error('Неверный apin name!'); }

?>