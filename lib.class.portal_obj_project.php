<?php

$path_core = __DIR__.'/../wbs_portal/lib.class.portal.php';
if (file_exists($path_core )) include($path_core );
else echo "<script>console.log('Модуль wbs_portal_obj_project требует модуль wbs_portal')</script>";

if (!class_exists('ModPortalObjProject')) { 
class ModPortalObjProject extends ModPortalObj {

    function __construct($page_id, $section_id) {
        parent::__construct('project', 'Проекты пользователей', $page_id, $section_id);
        $this->tbl_project = "`".TABLE_PREFIX."mod_{$this->prefix}project`";
        $this->tbl_project_road = "`".TABLE_PREFIX."mod_{$this->prefix}project_road`";
        $this->tbl_project_member = "`".TABLE_PREFIX."mod_{$this->prefix}project_memeber`";
        $this->tbl_project_resource_category = "`".TABLE_PREFIX."mod_{$this->prefix}project_resource_category`";
        $this->tbl_project_resource = "`".TABLE_PREFIX."mod_{$this->prefix}project_resource`";
        $this->tbl_wb_users = "`".TABLE_PREFIX."users`";
        $this->clsStorageImg = new WbsStorageImg();
        
        $this->default_fields = [
            'title'=>'Это заголовок проекта',
            'text'=>'Это подробное описание',
            'description'=>'Это краткое описание',
            'is_created'=>'0',
        ];
    }

    function uninstall() {
        global $database;
        
        // проверяем наличие объектов

        /*$r = select_row($this->tbl_apartment, 'COUNT(`obj_id`) as ocount');
        if ($r === false) return "Неизвестная ошибка!";
        if ($r->fetchRow()['ocount'] > 0) return "Существуют объекты!";*/
        
        // проверяем, наличие партнёров

        /*$r = select_row($this->tbl_partner, 'COUNT(`partner_id`) as pcount');
        if ($r === false) return "Неизвестная ошибка!";
        if ($r->fetchRow()['pcount'] > 0) return "Существуют партнёры!";*/

        // проверяем, наличие категорий

        /*$r = select_row($this->tbl_category, 'COUNT(`category_id`) as ccount');
        if ($r === false) return "Неизвестная ошибка!";
        if ($r->fetchRow()['ccount'] > 0) return "Существуют категории!";*/

        // удаляем модуль

        $arr = [/*"DROP TABLE ".$this->tbl_apartment,
                "DROP TABLE ".$this->tbl_category,
                "DROP TABLE ".$this->tbl_partner,*/
        ];

        $r = parent::uninstall($arr);
        if ($r === false) return "Неизвестная ошибка!";
        if ($r !== true) return $r;
        
        return true;
        
    }
    
    function install() {
        return parent::install();
    }
    
    function create_project($fields) {
        global $database;

        $_fields = $this->split_arrays($fields);

        $r = insert_row($this->tbl_obj_settings, $_fields);
        if ($r !== true) return "Неизвестная ошибка";

        $obj_id = $database->getLastInsertId();

        $fields['obj_id'] = $obj_id;
        $r = insert_row($this->tbl_project, $fields);
        if ($r !== true) return "Неизвестная ошибка";

        return (integer)$obj_id;
    }

    function get_obj($sets=[], $only_count=false) {
        global $database;

        $where = [
            "{$this->tbl_project}.`obj_id`={$this->tbl_obj_settings}.`obj_id`",
            "{$this->tbl_obj_settings}.`obj_type_id`=".process_value($this->obj_type_id),
            "{$this->tbl_obj_settings}.`user_owner_id`={$this->tbl_wb_users}.`user_id`"
        ];
        $this->_getobj_where($sets, $where);

        if (isset($sets['is_created']) && $sets['is_created'] !== null) $where[] = "{$this->tbl_project}.`is_created`=".process_value($sets['is_created']);

        $find_keys = [
            'title'=>"{$this->tbl_project}.`title`",
            'text'=>"{$this->tbl_project}.`text`",
            'description'=>"{$this->tbl_project}.`description`",
        ];
        $where_find = $this->_getobj_search($sets, $find_keys);
        if ($where_find) $where[] = $where_find;

        $where = implode(' AND ', $where);
        $select = $only_count ? "COUNT($this->tbl_project.obj_id) AS count" : "*";
        $order_limit = $this->_getobj_order_limit($sets);

        $sql = "SELECT $select FROM {$this->tbl_project}, {$this->tbl_obj_settings}, {$this->tbl_wb_users} WHERE $where $order_limit";
        
        //return $sql;
        //echo "<script>console.log(`".htmlentities($sql)."`);</script>";

        return $this->_getobj_return($sql, $only_count);

    }
    
}
}