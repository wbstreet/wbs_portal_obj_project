DROP TABLE IF EXISTS `{TABLE_PREFIX}mod_wbs_portal_obj_project`;
CREATE TABLE `{TABLE_PREFIX}mod_wbs_portal_obj_project` (
  `obj_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `text` text NOT NULL,
  `storage_image_id` int(11),
  `is_created` int(11) NOT NULL,
   PRIMARY KEY (`obj_id`)
){TABLE_ENGINE=MyISAM};

DROP TABLE IF EXISTS `{TABLE_PREFIX}mod_wbs_portal_obj_project_road`;
CREATE TABLE `{TABLE_PREFIX}mod_wbs_portal_obj_project_road` (
  `road_id` int(11) NOT NULL AUTO_INCREMENT,
  `obj_id` int(11) NOT NULL,
  `position` int(11) NOT NULL,
  `is_done` int(11) NOT NULL,
  `is_deleted` int(11) NOT NULL,
  `text` varchar(255) NOT NULL,
  PRIMARY KEY (`road_id`)
){TABLE_ENGINE=MyISAM};

DROP TABLE IF EXISTS `{TABLE_PREFIX}mod_wbs_portal_obj_project_member`;
CREATE TABLE `{TABLE_PREFIX}mod_wbs_portal_obj_project_member` (
  `member_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `obj_id` int(11) NOT NULL,
  `is_active` int(11) NOT NULL,
  PRIMARY KEY (`member_id`)
){TABLE_ENGINE=MyISAM};