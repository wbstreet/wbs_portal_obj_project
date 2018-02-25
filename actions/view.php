<?php

include(__DIR__.'/../lib.class.portal_obj_project.php');
$clsModPortalObjProject = new ModPortalObjProject($page_id, $section_id);

$modPortalArgs['obj_owner'] = $clsFilter->f2($_GET, 'obj_owner', [['variants', '', ['all', 'my']]], 'default', 'all');

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

    $fields = [
        'is_created'=>'1',
        'order_by'=>'`date_created`',
        'order_dir'=>'DESC',
        'find_str'=>$modPortalArgs['s'],
        'find_in'=>$modPortalArgs['s_in'],
    ];
    
    if ($modPortalArgs['obj_owner'] === 'my' && $is_auth) {
        $fields['user_owner_id'] = $admin->get_user_id();
    } else {
        $fields['is_active'] = '1';
    }

    // количество
    $max_count = $clsModPortalObjProject->get_obj($fields, true);
    if (gettype($max_count) == 'string') $clsModPortalObjProject->print_error($max_count);

    $divs = calc_paginator_and_limit($modPortalArgs, $fields, $max_count);

    $r = $clsModPortalObjProject->get_obj($fields);
    if (gettype($r) == 'string') $clsModPortalObjProject->print_error($r);

    $projects = [];
    $page_link = page_link($wb->link);
    while ($r !== null && $project = $r->fetchRow()) {

        $project['orig_image'] = $clsStorageImg->get($project['storage_image_id'], 'origin');
        $project['preview_image'] = $clsStorageImg->get($project['storage_image_id'], '350x250');

        $project['obj_url'] = $page_link.'?obj_id='.$project['obj_id'];
        $project['objs_from_url'] = $page_link.'?obj_owner='.$project['user_owner_id'];

        $projects[] = $project;

    }

    $clsModPortalObjProject->render('view_list.html', [
        'objs'=>$projects,
        'is_auth'=>$is_auth,
        'page'=>$wb,
        'modPortalArgs'=>$modPortalArgs,
        'divs'=>$divs,
        'page_link'=>$page_link,
    ]);
    
} else { // отображаем один проект

    // вынимаем
    
    if ($project === null) {
        $r = $clsModPortalObjProject->get_obj(['obj_id'=>$modPortalArgs['obj_id']]);
        if (gettype($r) == 'string') $clsModPortalObjProject->print_error($r);
        else if ($r === null) $clsModPortalObjProject->print_error('Проект не найден');
        else $project = $r->fetchRow();
    }

    // дорожная карта
    
    $r = select_row($clsModPortalObjProject->tbl_project_road, '*', glue_fields([
        'is_deleted'=>'0',
        'obj_id'=>$project['obj_id'],
        ], ' AND ').' ORDER BY `position`');
    if (gettype($r) === 'string') $clsModPortalObjProject->print_error($r);
    $road = [];
    while($r !== null && $row = $r->fetchRow()) $road[] = $row;

    // категории требуемых ресурсов

    $r = select_row($clsModPortalObjProject->tbl_project_resource_category, '*', '1=1 ORDER BY `resource_category_name`');
    if (gettype($r) === 'string') { $clsModPortalObjProject->print_error($r); $r = null; }
    $rcategories = [];
    while($r !== null && $row = $r->fetchRow()) $rcategories[] = $row;

    // требуемые ресурсы
    
    $where = [
        $clsModPortalObjProject->tbl_project_resource.'.`resource_category_id`='.$clsModPortalObjProject->tbl_project_resource_category.'.`resource_category_id`',
        $clsModPortalObjProject->tbl_project_resource.'.`obj_id`='.process_value($project['obj_id']),
        ];

    $r = select_row([
        $clsModPortalObjProject->tbl_project_resource,
        $clsModPortalObjProject->tbl_project_resource_category
        ], '*', implode(' AND ', $where).' ORDER BY '.$clsModPortalObjProject->tbl_project_resource.'.`resource_category_id`');
    if (gettype($r) === 'string') { $clsModPortalObjProject->print_error($r); $r = null; }
    $resources = [];
    while($r !== null && $row = $r->fetchRow()) $resources[] = $row;

    // участники

    $members = [];
    
    $r = select_row(
        [$clsModPortalObjProject->tbl_project_member, "`".TABLE_PREFIX."users`"],
        "*",
        implode(' AND ', [
            $clsModPortalObjProject->tbl_project_member.".`obj_id`=".process_value($modPortalArgs['obj_id']),
            "`".TABLE_PREFIX."users`.user_id=".$clsModPortalObjProject->tbl_project_member.".`user_id`",
        ])
    );
    if (gettype($r) === 'string') { $clsModPortalObjProject->print_error($r); $r = null; }
    while($r !== null && $row = $r->fetchRow()) $members[] = $row;

    // информация о пользователе

    $user = select_row('`'.TABLE_PREFIX.'users`', '*', '`user_id`='.process_value($project['user_owner_id']));
    if (gettype($user) === 'string') { $clsModPortalObjProject->print_error($user); $r = null; }
    else if ($user === null) $clsModPortalObjProject->print_error('Пользователь не найден');
    else $user = $user->fetchRow();

    // отображаем
    
    $can_edit = $is_auth && $admin->get_user_id() === $project['user_owner_id'] ? true : false;
    
    if ($can_edit) {
        ob_start();
        show_editor($project['text'], __FILE__);
        $editor = ob_get_contents();
        ob_end_clean();
    } else {
        $editor = '';
    }

    $project['orig_image'] = $clsStorageImg->get($project['storage_image_id'], 'origin');
    $project['preview_image'] = $clsStorageImg->get($project['storage_image_id'], '350x250');
    
    $btn_save = "sendform(this, 'update_image_project', {url:WB_URL+'/modules/wbs_portal_obj_project/api.php'});";

    $clsModPortalObjProject->render('view.html', [
        'project'=>$project,
        'project_id'=>$modPortalArgs['obj_id'],
        'user'=>$user,
        'roads'=>$road,
        'rcategories'=>$rcategories,
        'resources'=>$resources,
        'members'=>$members,
        //'members'=>$members,
        'btn_edit'=>$can_edit ? "<input type='button' value='редактировать' onclick=\"edit_project(this)\" style='padding:0 5px 0 5px; margin:0;'>" : '',
        'can_edit'=>$can_edit,
        'editor'=>$editor,
        'image_loader'=> $can_edit ? echoImageLoader('image', $project['orig_image'], '170px', '150px', true, $btn_save) : '',
        'spo'=>"page_id:'{$page_id}',section_id:'{$section_id}',obj_id:'{$project['obj_id']}'",
    ]);

    if (!$can_edit) share_page_link();

}

?>