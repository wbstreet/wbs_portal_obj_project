<?php

require_once(__DIR__.'/lib.class.portal_obj_project.php');

$action = $_POST['action'];

//$section_id = $clsFilter->f('section_id', [['integer', "Не указана секция!"]], 'fatal');
//$page_id = $clsFilter->f('page_id', [['integer', "Не указана страница!"]], 'fatal');

require_once(WB_PATH."/framework/class.admin.php");
$admin = new admin('Start', '', false, false);
$clsModPortalObjProject = new ModPortalObjProject(null, null);

if ($action == 'add_project') {

    check_auth(); //check_all_permission($page_id, ['pages_modify']);

    $title = $clsFilter->f('title', [['1', 'Введите специализацию!'], ['mb_strCount', 'Максимальное число символов - 255', 0, 255]], 'append');
    $description = $clsFilter->f('description', [['1', 'Введите специализацию!'], ['mb_strCount', 'Максимальное число символов - 255', 0, 255]], 'append');
    $text = $clsFilter->f('text', [['1', 'Введите специализацию!'], ['mb_strCount', 'Максимальное число символов - 255', 0, 255]], 'append');
    if ($clsFilter->is_error()) $clsFilter->print_error();

    /*$fields = ['skill'=>$skill];
    $skill_id = insert_row_uniq($clsModPortalObjProject->tbl_profile_skill, $fields, false, 'skill_id') ;
    if (gettype($skill_id) === 'string') print_error($skill_id);

    $fields = ['skill_id'=>$skill_id, 'user_id'=>$admin->get_user_id()];
    $r = insert_row_uniq($clsModPortalObjProfile->tbl_profile_skill_user, $fields);
    if (gettype($r) === 'string') print_error($r);

    print_success('Специализация успешно добавлена!', ['data'=>['skill'=>$skill, 'skill_id'=>$skill_id], 'absent_fields'=>[]]);
    */
    
} else if ($action == 'delete_skill') {

    /*check_auth();

    $skill_id = $clsFilter->f('skill_id', [['integer', 'Укажите специализацию!']], 'append');
    if ($clsFilter->is_error()) $clsFilter->print_error();
    
    $where = glue_fields(['skill_id'=>$skill_id, 'user_id'=>$admin->get_user_id()], ' AND ');
    $r = delete_row($clsModPortalObjProfile->tbl_profile_skill_user, $where);
    if (gettype($r) === 'string') print_error($r);
    
    print_success('Успешно удалено!');*/

} else { print_error('Неверный apin name!'); }

?>