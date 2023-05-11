SELECT 
	sa.enrollment_id,
	sa.module_id,
	sav.vocabulary_id, 
	COUNT(sav.answered) AS answered, 
	RIGHT(GROUP_CONCAT(sav.correct), 1) AS correct, 
	RIGHT(GROUP_CONCAT(sav.bookmarked), 1) AS bookmarked
FROM exam_prep.study_attempt_vocabulary sav
INNER JOIN exam_prep.study_attempt sa ON sav.study_attempt_id = sa.id
WHERE sav.answered = 1 AND sa.status = 1
GROUP BY sa.enrollment_id, sa.module_id, sav.vocabulary_id
ORDER BY sav.id DESC;