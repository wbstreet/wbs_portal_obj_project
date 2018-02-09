<?php
if ($admin->is_authenticated()) {

$project_id = $modPortalArgs['obj_id'];

include(__DIR__.'/../lib.class.portal_obj_project.php');
$clsModPortalObjProject = new ModPortalObjProject($page_id, $section_id);

if ($admin->is_authenticated()) {$is_auth = true;}
else { $is_auth = false; }

// Вынимаем основные данные проекта

$project = null;
if ($project_id === null) {

    // проверяем наличие пустого созданного проекта

    $where = [
        $clsModPortalObjProject->tbl_project.'`obj_id`='.$clsModPortalObjProject->tbl_settings.'`obj_id`',
        $clsModPortalObjProject->tbl_project.'`is_created`="0"',
        $clsModPortalObjProject->tbl_settings.'`user_owner_id`='.process_value($admin->get_iser_id()),
    ];
    $tables = [$clsModPortalObjProject->tbl_project, $clsModPortalObjProject->tbl_settings]

    $r = select_row($tables, '*', implode(' AND ', $where));
    if (gettype($r) === 'string') $clsModPortalObjProject->print_error($r);
    else if ($r !== null) {

        // если есть, то вынимаем его

        $project = $r->fetchRow();
        $project_id = $project['obj_id'];

    } else {
    
        // иначе добавляем новый пустой проект

        $project_id = $clsModPortalObjProject->create_project([
            'title'=>'',
            'text'=>'',
            'description'=>'',
            'user_owner_id'=>$admin->get_user_id(),
           'is_created'=>'0',
        ]);
        if (gettype($project_id) === 'string') $clsModPortalObjProject->print_error($project_id);
    }
}

if ($project_id !== null && $project === null) {
    $r = $clsModPortalObjProject->get_project(['obj_id'=>$project_id]);
    if (gettype($r) == 'string') $clsModPortalObjProject->print_error($r);
    else if ($r === null) $clsModPortalObjProject->print_error('Проект не найден');
    else $project = $r->fetchRow();
}

// вынимаем данные дорожной карты проекта

$road = [];

// вынимаем данные участников проекта

$members = [];

// отображаем

$loader = new Twig_Loader_Array(array(
    'edit' => file_get_contents(__DIR__.'/edit.html'),
));
$twig = new Twig_Environment($loader);

echo $twig->render('edit', [
    'project'=>$project,
    'road'=>$road,
    'members'=>$members,
]);

}
?>