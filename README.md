# dkam: digikam-web

Web-based browsing using the DikiKam Photo Database


# Requirements

Configure Digikam to use a sqlite data, copy it to the root here.

Generate the classes from the schema.sql file, which are then browsable as doctrine entities.

Get table relationships:

* https://invent.kde.org/graphics/digikam/-/tree/master/project/documents?ref_type=heads
* Also see: https://github.com/patschwork/digiYiiKam
* 

```bash
cp ~/Pictures/digikam4.db .
sqlite3 digikam4.db .schema > schema.sql
./c app:sqlite 
```


The goal is to completely generate the entities so that 
