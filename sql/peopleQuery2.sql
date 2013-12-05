SELECT p.*, 
       r."PersonId" as r_person_id, 
       date_part('year',age(p."BirthYear")) as p_age, 
       rd."RelationshipDefinition"
FROM 
    acservas."S_Person" p LEFT JOIN 
	acservas."S_P_Relationships" r ON p."PersonId" = r."RelatedPersonId" LEFT JOIN 
	acservas."S_P_RelationshipDefinitions" rd ON 
	r."RelationshipDefinitionId" = rd."RelationshipDefinitionId" 
ORDER BY 
    r."RelatedPersonId" 