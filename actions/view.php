<?php

include(__DIR__.'/../lib.class.portal_obj_project.php');
$clsModPortalObjProject = new ModPortalObjProject($page_id, $section_id);

if ($admin->is_authenticated()) {$is_auth = true;}
else { $is_auth = false; }

$project = null;
if ($modPortalArgs['obj_id'] === '0') { // создаём объект
    
    // проверяем наличие пустого созданного проекта
    
    $where = [
        $clsModPortalObjProject->tbl_project.'.`obj_id`='.$clsModPortalObjProject->tbl_obj_settings.'.`obj_id`',
        $clsModPortalObjProject->tbl_project.'.`is_created`="0"',
        $clsModPortalObjProject->tbl_obj_settings.'.`user_owner_id`='.process_value($admin->get_user_id()),
        $clsModPortalObjProject->tbl_obj_settings.'.`section_id`='.process_value($section_id),
        $clsModPortalObjProject->tbl_obj_settings.'.`page_id`='.process_value($page_id),
    ];
    $tables = [$clsModPortalObjProject->tbl_project, $clsModPortalObjProject->tbl_obj_settings];

    $r = select_row($tables, '*', implode(' AND ', $where));
    if (gettype($r) === 'string') $clsModPortalObjProject->print_error($r);
    else if ($r !== null) {

        // если есть, то вынимаем его

        $project = $r->fetchRow();
        $modPortalArgs['obj_id'] = $project['obj_id'];

    } else {
    
        // иначе добавляем новый пустой проект

        $modPortalArgs['obj_id'] = $clsModPortalObjProject->create_project(array_merge($clsModPortalObjProject->default_fields, [
            'user_owner_id'=>$admin->get_user_id(),
            'is_created'=>'0',
            'section_id'=>$section_id,
            'page_id'=>$page_id,
            'obj_type_id'=>$clsModPortalObjProject->obj_type_id,
        ]));
        if (gettype($modPortalArgs['obj_id']) === 'string') $clsModPortalObjProject->print_error($modPortalArgs['obj_id']);
    }
    
}

if ($modPortalArgs['obj_id'] === null) { // выводим список проектов

    $r = $clsModPortalObjProject->get_project([
        'is_created'=>'1',
        'order_by'=>'`date_created`',
        'order_dir'=>'DESC'
        ]);
    if (gettype($r) == 'string') $clsModPortalObjProject->print_error($r);

    $projects = [];
    $page_link = page_link($wb->link);
    while ($r !== null && $project = $r->fetchRow()) {

        $project['obj_url'] = $page_link.'?obj_id='.$project['obj_id'];
        $publication['objs_from_url'] = $page_link.'?obj_owner='.$project['user_owner_id'];

        $projects[] = $project;

    }

    $clsModPortalObjProject->render('view_list.html', [
        'objs'=>$projects,
        'is_auth'=>$is_auth,
    ]);
    
} else { // отображаем один проект

    // вынимаем
    
    if ($project === null) {
        $r = $clsModPortalObjProject->get_project(['obj_id'=>$modPortalArgs['obj_id']]);
        if (gettype($r) == 'string') $clsModPortalObjProject->print_error($r);
        else if ($r === null) $clsModPortalObjProject->print_error('Проект не найден');
        else $project = $r->fetchRow();
    }
    
    $user = select_row('`'.TABLE_PREFIX.'users`', '*', '`user_id`='.process_value($project['user_owner_id']));
    if (gettype($user) === 'string') $clsModPortalObjProject->print_error($user);
    else if ($user === null) $clsModPortalObjProject->print_error('Пользователь не найден');
    else $user = $user->fetchRow();

    // отображаем

    $clsModPortalObjProject->render('view.html', [
        'project'=>$project,
        'project_id'=>$modPortalArgs['obj_id'],
        'user'=>$user,
        'road'=>$road,
        'members'=>$members,
        'btn_edit'=>"<input type='button' value='Edit' onclick=\"edit_project(this)\" style='padding:0 5px 0 5px; margin:0;'>",
    ]);

}

?>