SRCDIR=src/
DOCDIR=doc/
HTMLDIR=${DOCDIR}/html

test:
	phpunit tests/users.php
	phpunit tests/properties.php
#	phpunit tests/groups.php

examples:
	php examples/test.php

doc:
	mkdir -p ${HTMLDIR}
	phpdoc --title "php-restauth documentation" -o HTML:Smarty:PHP -dn php-restauth -d ${SRCDIR} -t ${HTMLDIR}

clean:
	rm -rf ${DOCDIR}
