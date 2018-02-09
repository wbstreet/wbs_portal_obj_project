<?php

$path_core = __DIR__.'/../wbs_portal/lib.class.portal.php';
if (file_exists($path_core )) include($path_core );
else echo "<script>console.log('Модуль wbs_portal_obj_project требует модуль wbs_portal')</script>";

// используется только в данном файле. Пока неизвестно, включать её в sql_tools.php или нет.
if (!function_exists('guess_operator')) {
function guess_operator($value, $inverse=false) {
        if ($value === 'NULL') {
                if ($inverse) return ' is not ';
                else {return ' is ';}
        } else {
                if ($inverse) return '!=';
                else {return '=';}
        }
}
}

if (!class_exists('ModPortalObjProject')) { 
class ModPortalObjProject extends ModPortalObj {

    function __construct($page_id, $section_id) {
        parent::__construct('project', 'Проекты пользователей', $page_id, $section_id);
        $this->tbl_project = "`".TABLE_PREFIX."mod_{$this->prefix}project`";
        $this->tbl_project_road = "`".TABLE_PREFIX."mod_{$this->prefix}project_road`";
        $this->tbl_project_member = "`".TABLE_PREFIX."mod_{$this->prefix}project_memeber`";
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

    function get_project($sets=[], $only_count=false) {
        global $sql_builder, $database;

        $is_deleted = isset($sets['is_deleted']) ? $database->escapeString($sets['is_deleted']) : null;
        $is_moder = isset($sets['is_moder']) ? $sets['is_moder'] : null;

        if (isset($sets['limit_offset'])) $limit_offset = (integer)($sets['limit_offset']); else $limit_offset = null;
        if (isset($sets['limit_count'])) $limit_count = (integer)($sets['limit_count']); else $limit_count = null;
        if (isset($sets['find_str'])) $find_str = $database->escapeString($sets['find_str']); else $find_str = null;

        $order_by = isset($sets['order_by']) ? glue_keys($sets['order_by']) : null;
        $order_dir = isset($sets['order_dir']) ? $database->escapeString($sets['order_dir']) : null;

        $where = [];

        //$sql_builder->add_raw_where('1=1');
        if (isset($sets['obj_id'])) $where[] = "{$this->tbl_project}.`obj_id`=".process_value($sets['obj_id']);
        //if (isset($sets['settlement_id']) && $sets['settlement_id'] !== null) $where[] = '`settlement_id`='.process_value($sets['settlement_id']);
        if (isset($sets['is_active']) && $sets['is_active'] !== null) $where[] = "{$this->tbl_obj_settings}.`is_active`=".process_value($sets['is_active']);
        if (isset($sets['is_moder']) && $sets['is_moder'] !== null) $where[] = "{$this->tbl_obj_settings}.`moder_status`=".process_value($sets['is_moder']);
        if (isset($sets['is_deleted']) && $sets['is_deleted'] !== null) $where[] = "{$this->tbl_obj_settings}.`is_deleted`=".process_value($sets['is_deleted']);

        if (isset($sets['page_id']) && $sets['page_id'] !== null) $where[] = "{$this->tbl_obj_settings}.`page_id`=".process_value($sets['page_id']);
        if (isset($sets['section_id']) && $sets['section_id'] !== null) $where[] = "{$this->tbl_obj_settings}.`section_id`=".process_value($sets['section_id']);
 
        if (isset($sets['is_created']) && $sets['is_created'] !== null) $where[] = "{$this->tbl_project}.`is_created`=".process_value($sets['is_created']);
 
        if ( $find_str !== null ) {
            $find_str = str_replace('%', '\%', $find_str);
            $find_like = "({$this->tbl_project}.`title` LIKE '%$find_str%' OR {$this->tbl_project}.`description` LIKE '%$find_str%')";
        }

        $where[] = "{$this->tbl_project}.`obj_id`={$this->tbl_obj_settings}.`obj_id` AND {$this->tbl_obj_settings}.`obj_type_id`=".process_value($this->obj_type_id)." AND {$this->tbl_obj_settings}.`user_owner_id`={$this->tbl_wb_users}.`user_id`";
        if ( $find_str !== null ) $where[] = "($find_like)";

        $where = implode(' AND ', $where);

        $select = $only_count ? "COUNT(obj_id) AS count" : "*";

        if ( $order_by !== null ) {
            $order = " ORDER BY $order_by ";
            if ( $order_dir !== null ) $order .= " $order_dir ";
        } else $order = '';

        $limit = build_limit($limit_offset, $limit_count);

        $sql = "SELECT
        $select
        FROM {$this->tbl_project}, {$this->tbl_obj_settings}, {$this->tbl_wb_users} WHERE $where $order $limit";
        
        //return $sql;

        //echo "<script>console.log(`".htmlentities($sql)."`);</script>";

        $r = $database->query($sql);
        if ($database->is_error()) return $database->get_error();

        if ($only_count) {
            $count = $r->fetchRow()['count'];
            return (integer)$count;
        } else {
            if ($r->numRows() === 0) return null;
            return $r;
        }
    }
    
}
}