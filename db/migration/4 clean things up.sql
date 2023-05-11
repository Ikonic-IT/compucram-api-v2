ALTER TABLE `exam_prep`.`state`
    ADD UNIQUE INDEX `name_UNIQUE` (`name` ASC),
    ADD UNIQUE INDEX `code_UNIQUE` (`code` ASC);

ALTER TABLE `exam_prep`.`industry`
    ADD UNIQUE INDEX `name_UNIQUE` (`name` ASC);

# don't need these
DROP TABLE `exam_prep`.`statistics_daily`;
DROP TABLE `exam_prep`.`user_access_control`;
DROP TABLE `exam_prep`.`user_role`;
DROP TABLE `exam_prep`.`config`;
DROP TABLE `exam_prep`.`tag`;

# make sure audits get removed if question is deleted to clean things up
ALTER TABLE `exam_prep`.`question_audit`
  DROP FOREIGN KEY `fk_question_audit_question1`;

ALTER TABLE `exam_prep`.`question_audit`
    ADD CONSTRAINT `fk_question_audit_question1` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `exam_prep`.`answer_audit`
  DROP FOREIGN KEY `fk_answer_audit_answer1`;

ALTER TABLE `exam_prep`.`answer_audit`
    ADD CONSTRAINT `fk_answer_audit_question1` FOREIGN KEY (`answer_id`) REFERENCES `answer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

# update change log
INSERT INTO `change_log` VALUES (4, 4, '4 clean things up.sql', 'Remove tables we don\'t need.', NOW());