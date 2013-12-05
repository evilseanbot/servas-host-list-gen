SELECT l.*, lc.*
FROM acservas."S_H_Languages" l,
     acservas."S_Host" h,
	 acservas."S_H_LanguageCategories" lc
WHERE h."HostId" = l."HostId" AND
      l."LanguageId" = lc."LanguageId"