CREATE VIEW `view_study_attempt_question` AS
SELECT 
	ma.enrollment_id,
	ma.module_id,
	q.id as question_id,
	q.question_text,
	q.question_html,
	a.answer_text,
	a.answer_html,
	COUNT(maq.viewed) AS viewed, 
	RIGHT(GROUP_CONCAT(maq.correct), 1) AS correct, 
	RIGHT(GROUP_CONCAT(maq.bookmarked), 1) AS bookmarked
FROM module_attempt_question maq
INNER JOIN module_attempt ma ON maq.module_attempt_id = ma.id
INNER JOIN question q ON maq.question_id = q.id
INNER JOIN answer a ON q.id = a.question_id
WHERE maq.answered = 1 AND ma.type='study' AND ma.completed IS NOT NULL
GROUP BY ma.enrollment_id, ma.module_id, maq.question_id
ORDER BY maq.id DESC;