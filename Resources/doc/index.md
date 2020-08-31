TODO


RestApiController\
-> arguments passed to request may have influence on behaviour:
- limit: page limit, 0 for infinite
- filter: filters applied to getAll requests, must be valid for doctrine (eg. valid field names of entities)
- order: if is array, then should be like ["field"=>"asc", "field2"=>"desc"], else should be "asc" or "desc" and field is id
