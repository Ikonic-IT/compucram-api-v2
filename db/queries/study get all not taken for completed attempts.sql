select * 
from module_vocabulary 
where vocabulary_id NOT IN (
	select vocabulary_id 
	from study_attempt_vocabulary 
	where study_attempt_id in (9,10,11) group by vocabulary_id
)