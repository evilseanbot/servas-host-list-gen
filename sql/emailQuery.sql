SELECT e.*, ed.*
FROM acservas."S_Emails" e,
     acservas."S_Email_CategoryDefinitions" ed
WHERE e."EmailCategoryId" = ed."EmailCategoryId" 