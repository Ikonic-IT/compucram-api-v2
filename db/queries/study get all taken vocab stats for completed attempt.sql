SELECT 
	count(*) as answered,
	sum(correct) as correct,
	count(*) - sum(correct) as incorrect,
	sum(bookmarked) as bookmarked,
	(SELECT COUNT(*) FROM module_vocabulary WHERE module_id = 1) as total
FROM (
	SELECT 
		RIGHT(GROUP_CONCAT(sav.correct), 1) AS correct, 
		RIGHT(GROUP_CONCAT(sav.bookmarked), 1) AS bookmarked
	FROM exam_prep.study_attempt_vocabulary sav
	INNER JOIN exam_prep.study_attempt sa ON sav.study_attempt_id = sa.id
	WHERE sav.answered = 1 AND sa.status = 1 AND sa.enrollment_id = 1 AND sa.module_id = 1
	GROUP BY sav.vocabulary_id
	ORDER BY sav.id DESC
) sub