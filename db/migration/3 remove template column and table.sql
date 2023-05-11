ALTER TABLE `exam_prep`.`organization`
DROP FOREIGN KEY `fk_organization_template`;
ALTER TABLE `exam_prep`.`organization`
DROP COLUMN `template_id`,
DROP INDEX `fk_organization_template_idx` ;

DROP TABLE `exam_prep`.`template`;

# update change log
INSERT INTO `change_log` VALUES (3, 3, '3 remove template column and table.sql', 'Remove organization templates.', NOW());