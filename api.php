<?php

require_once(__DIR__.'/lib.class.portal_obj_project.php');

$action = $_POST['action'];

//$section_id = $clsFilter->f('section_id', [['integer', "Не указана секция!"]], 'fatal');
//$page_id = $clsFilter->f('page_id', [['integer', "Не указана страница!"]], 'fatal');

require_once(WB_PATH."/framework/class.admin.php");
$admin = new admin('Start', '', false, false);
$clsModPortalObjProject = new ModPortalObjProject(null, null);

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

if ($action == 'create_project') {

    check_auth(); //check_all_permission($page_id, ['pages_modify']);
    
    $obj_id = get_obj_id_new();

    $r = update_row($clsModPortalObjProject->tbl_project, ['is_created'=>1], "`obj_id`=".process_value($obj_id));
    if (gettype($r) === 'string') print_error($r);

    print_success('Специализация успешно добавлена!', ['data'=>['skill'=>$skill, 'skill_id'=>$skill_id], 'absent_fields'=>[]]);

} else if ($action == 'update_project') {

    check_auth();
    
    $obj_id = $clsFilter->f('obj_id', [['integer', 'Укажите проект!']], 'append');
    $name = $clsFilter->f('name', [['1', 'Укажите имя поля!']], 'append');
    $value = $clsFilter->f('value', [['1', 'Укажите значение поля! поля!']], 'append');
    if ($clsFilter->is_error()) $clsFilter->print_error();    

    $fields = [
        $name=>$value,
        ];
    $r = update_row($clsModPortalObjProject->tbl_project, $fields, "`obj_id`=".process_value($obj_id));
    if (gettype($r) === 'string') print_error($r);

    print_success('Успешно!');

} else if ($action == 'cancel_project') {

    check_auth();

    $obj_id = get_obj_id_new();

    $r = update_row($clsModPortalObjProject->tbl_project, $clsModPortalObjProject->default_fields, "`obj_id`=".process_value($obj_id));
    if (gettype($r) === 'string') print_error($r);

    print_success('Успешно!');

} else { print_error('Неверный apin name!'); }

?>