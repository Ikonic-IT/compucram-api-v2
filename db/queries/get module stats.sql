select *
from module_attempt_question a
JOIN (
 select max(id) as id, count(question_id) as viewed
	from module_attempt_question 
	where module_attempt_id in (
		select id
		from module_attempt
		where enrollment_id = 1 
			and module_id = 1 
			and completed is not null 
			and type = 'study'
	)
	group by question_id
) b ON a.id = b.id
