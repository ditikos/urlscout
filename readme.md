ditikos/urlscout
================

**DISCLAIMER**
This is a proof of concept subcommand for wp-cli. Not broadly tested so memory bugs might exist.

## Description
Adds url listing capability from db to wp-cli.


## Using
~~~
wp urlscout
~~~

## Requirements
Requires to be in a wp installation folder so that the wp-cli will detect wordpress settings.

## Tables involved:
- wp_options
- wp_posts
- wp_postmeta

## Future tasks
- Use mysqldump to get a record of the database and search inside for url.