SELECT p.*, pc.*
FROM acservas."S_H_Pets" p,
     acservas."S_H_PetCategories" pc
WHERE p."PetId" = pc."PetId" 
