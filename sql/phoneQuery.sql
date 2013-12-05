SELECT p.*, pd.*
FROM acservas."S_Phones" p,
     acservas."S_Phone_CategoryDefinitions" pd
WHERE p."PhoneCategoryId" = pd."PhoneCategoryId" 