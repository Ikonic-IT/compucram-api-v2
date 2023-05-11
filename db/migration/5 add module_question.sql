CREATE TABLE IF NOT EXISTS `exam_prep`.`module_question` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `module_id` INT NOT NULL,
  `type` ENUM('study', 'practice', 'exam', 'preassessment') NOT NULL,
  `question_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_module_question_module1_idx` (`module_id` ASC),
  INDEX `fk_module_question_question1_idx` (`question_id` ASC),
  UNIQUE INDEX `module_id_type_question_id_UNIQUE` (`module_id` ASC, `type` ASC, `question_id` ASC),
  CONSTRAINT `fk_module_question_module1`
    FOREIGN KEY (`module_id`)
    REFERENCES `exam_prep`.`module` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_module_question_question1`
    FOREIGN KEY (`question_id`)
    REFERENCES `exam_prep`.`question` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;

# remove fk in question
ALTER TABLE `exam_prep`.question
    DROP FOREIGN KEY fk_question_question_bank1,
    MODIFY `question_bank_id` INT(11) NULL;

# more audit stuff
ALTER TABLE `exam_prep`.`question`
    ADD COLUMN `created_by` INT(11) NULL DEFAULT NULL AFTER `created`,
    ADD COLUMN `modified_by` INT(11) NULL DEFAULT NULL AFTER `modified`,
    ADD INDEX `fk_question_created_by_user_idx` (`created_by` ASC),
    ADD INDEX `fk_question_modified_by_user_idx` (`modified_by` ASC);

ALTER TABLE `exam_prep`.`question`
    ADD CONSTRAINT `fk_question_created_by_user`
      FOREIGN KEY (`created_by`)
      REFERENCES `exam_prep`.`user` (`id`)
      ON DELETE CASCADE
      ON UPDATE CASCADE,
    ADD CONSTRAINT `fk_question_modified_by_user`
      FOREIGN KEY (`modified_by`)
      REFERENCES `exam_prep`.`user` (`id`)
      ON DELETE CASCADE
      ON UPDATE CASCADE;

ALTER TABLE `exam_prep`.`answer`
    ADD COLUMN `created_by` INT(11) NULL DEFAULT NULL AFTER `created`,
    ADD COLUMN `modified_by` INT(11) NULL DEFAULT NULL AFTER `modified`,
    ADD INDEX `fk_answer_created_by_user_idx` (`created_by` ASC),
    ADD INDEX `fk_answer_modified_by_user_idx` (`modified_by` ASC);

ALTER TABLE `exam_prep`.`answer`
    ADD CONSTRAINT `fk_answer_created_by_user`
      FOREIGN KEY (`created_by`)
      REFERENCES `exam_prep`.`user` (`id`)
      ON DELETE CASCADE
      ON UPDATE CASCADE,
    ADD CONSTRAINT `fk_answer_modified_by_user`
      FOREIGN KEY (`modified_by`)
      REFERENCES `exam_prep`.`user` (`id`)
      ON DELETE CASCADE
      ON UPDATE CASCADE;

# update trigger for new audit stuff

DROP trigger IF EXISTS `question_audit`;
DROP trigger IF EXISTS `answer_audit`;

delimiter //
CREATE TRIGGER `question_audit` AFTER UPDATE ON question
  FOR EACH ROW
  BEGIN
    SET @now = NOW();

    IF NOT (NEW.question_text <=> OLD.question_text) THEN
        INSERT INTO `question_audit` (`question_id`, `column_name`, `before_value`, `after_value`, `modified`)
        VALUES (NEW.id, 'question_text', OLD.question_text, NEW.question_text, @now);
    END IF;

    IF NOT (NEW.feedback <=> OLD.feedback) THEN
        INSERT INTO `question_audit` (`question_id`, `column_name`, `before_value`, `after_value`, `modified`)
        VALUES (NEW.id, 'feedback', OLD.feedback, NEW.feedback, @now);
    END IF;

    IF NOT (NEW.techniques <=> OLD.techniques) THEN
        INSERT INTO `question_audit` (`question_id`, `column_name`, `before_value`, `after_value`, `modified`)
        VALUES (NEW.id, 'techniques', OLD.techniques, NEW.techniques, @now);
    END IF;

    IF NOT (NEW.active <=> OLD.active) THEN
        INSERT INTO `question_audit` (`question_id`, `column_name`, `before_value`, `after_value`, `modified`)
        VALUES (NEW.id, 'active', OLD.active, NEW.active, @now);
    END IF;

    IF NOT (NEW.audio_hash <=> OLD.audio_hash) THEN
        INSERT INTO `question_audit` (`question_id`, `column_name`, `before_value`, `after_value`, `modified`)
        VALUES (NEW.id, 'audio_hash', OLD.audio_hash, NEW.audio_hash, @now);
    END IF;

    IF NOT (NEW.audio_file <=> OLD.audio_file) THEN
        INSERT INTO `question_audit` (`question_id`, `column_name`, `before_value`, `after_value`, `modified`)
        VALUES (NEW.id, 'audio_file', OLD.audio_file, NEW.audio_file, @now);
    END IF;

    IF NOT (NEW.modified_by <=> OLD.modified_by) THEN
            INSERT INTO `question_audit` (`question_id`, `column_name`, `before_value`, `after_value`, `modified`)
            VALUES (NEW.id, 'modified_by', OLD.modified_by, NEW.modified_by, @now);
    END IF;
  END;//
delimiter ;

delimiter //
CREATE TRIGGER `answer_audit` AFTER UPDATE ON answer
  FOR EACH ROW
  BEGIN
    SET @now = NOW();

    IF NOT (NEW.answer_text <=> OLD.answer_text) THEN
        INSERT INTO `answer_audit` (`question_id`, `answer_id`, `column_name`, `before_value`, `after_value`, `modified`)
        VALUES (NEW.question_id, NEW.id, 'answer_text', OLD.answer_text, NEW.answer_text, @now);
    END IF;

    IF NOT (NEW.correct <=> OLD.correct) THEN
        INSERT INTO `answer_audit` (`question_id`, `answer_id`, `column_name`, `before_value`, `after_value`, `modified`)
        VALUES (NEW.question_id, NEW.id, 'correct', OLD.correct, NEW.correct, @now);
    END IF;

    IF NOT (NEW.audio_hash <=> OLD.audio_hash) THEN
        INSERT INTO `answer_audit` (`question_id`, `answer_id`, `column_name`, `before_value`, `after_value`, `modified`)
        VALUES (NEW.question_id, NEW.id, 'audio_hash', OLD.audio_hash, NEW.audio_hash, @now);
    END IF;

    IF NOT (NEW.audio_file <=> OLD.audio_file) THEN
        INSERT INTO `answer_audit` (`question_id`, `answer_id`, `column_name`, `before_value`, `after_value`, `modified`)
        VALUES (NEW.question_id, NEW.id, 'audio_file', OLD.audio_file, NEW.audio_file, @now);
    END IF;

    IF NOT (NEW.modified_by <=> OLD.modified_by) THEN
        INSERT INTO `answer_audit` (`question_id`, `answer_id`, `column_name`, `before_value`, `after_value`, `modified`)
        VALUES (NEW.question_id, NEW.id, 'modified_by', OLD.modified_by, NEW.modified_by, @now);
    END IF;
  END;//
delimiter ;

# update change log
INSERT INTO `change_log` VALUES (5, 5, '5 add module_question.sql', 'To support new question sharing structure and more audit goodies.', NOW());