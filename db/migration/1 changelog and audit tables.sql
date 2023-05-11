DROP TABLE IF EXISTS `change_log`;
CREATE TABLE `exam_prep`.`change_log` (
  `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `change_number` SMALLINT NULL,
  `delta_set` VARCHAR(50) NULL,
  `description` VARCHAR(500) NULL,
  `applied` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`));

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
  END;//  
delimiter ;

DROP TABLE IF EXISTS `question_audit`;
CREATE TABLE `question_audit` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `question_id` INT(11),
    `column_name` VARCHAR(30),
    `before_value` TEXT,
    `after_value` TEXT,
    `modified` timestamp,
    PRIMARY KEY (`id`),
    INDEX `fk_question_audit_question1_idx` (`question_id` ASC),
    CONSTRAINT `fk_question_audit_question1`
    FOREIGN KEY (`question_id`)
    REFERENCES `exam_prep`.`question` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

DROP TABLE IF EXISTS `answer_audit`;
CREATE TABLE `answer_audit` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `question_id` INT(11),
    `answer_id` INT(11),
    `column_name` VARCHAR(30),
    `before_value` TEXT,
    `after_value` TEXT,
    `modified` timestamp,
    PRIMARY KEY (`id`),
    INDEX `fk_answer_audit_answer1_idx` (`answer_id` ASC),
    CONSTRAINT `fk_answer_audit_answer1`
    FOREIGN KEY (`answer_id`)
    REFERENCES `exam_prep`.`answer` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

# prevent same question for progress
ALTER TABLE `exam_prep`.`progress_question` 
ADD UNIQUE INDEX `ux_progress_question_progress_question` (`progress_id` ASC, `question_id` ASC);

# update change log
INSERT INTO `change_log` VALUES (1, 1, '1 changelog and audit tables.sql', 'Adding audits for question and answer to track changes. Added changelog table. And new unique index to precent same question added to progress.', NOW());

