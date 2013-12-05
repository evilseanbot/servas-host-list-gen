SELECT d.*, dc.*
FROM acservas."S_H_Disabilities" d,
     acservas."S_H_DisabilityCategories" dc
WHERE d."DisabilityId" = dc."DisabilityId" 