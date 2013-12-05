SELECT 
    *,
    to_char(h."NotAvailDateFrom", 'MM/DD/YYYY') as nadff, 
    to_char(h."NotAvailDateTo", 'MM/DD/YYYY') as nadtf
FROM acservas."S_Address" a 
    LEFT JOIN acservas.zipcounty zc ON zc.zip = substring(a."Zip" from 1 for 5)
  LEFT JOIN acservas.countyregion cr ON zc.county = cr.countyname AND zc.state = cr.state
  LEFT JOIN acservas.regions r ON r.regionid = cr.regionid,
    acservas."S_Person" p,
  acservas."S_Host" h,
    acservas.full_state_names fn
WHERE p."PersonId" = a."PersonId" AND
      p."PersonId" = h."PersonId" AND
      a."State" = fn.state_abv AND
    a."AddressCategoryId" in ('1', '4') AND 
    h."HostStatus" in ('A', '') AND
    p."ActiveMember" = 'TRUE'
ORDER BY state_full_name, regionname, county, "City", "Zip"

