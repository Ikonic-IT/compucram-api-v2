ALTER TABLE `exam_prep`.`user` 
CHANGE COLUMN `role` `role` ENUM('admin','content','member','guest') NOT NULL DEFAULT 'member' ;

# update change log
INSERT INTO `change_log` VALUES (2, 2, '2 add content role.sql', 'Content role for content manager.', NOW());

