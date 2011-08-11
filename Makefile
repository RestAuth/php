SRCDIR=RestAuth/
DOCDIR=doc/
HTMLDIR=${DOCDIR}/html

all: 

test:
	# test for coding standards:
	phpcs --report-width=120 tests/* RestAuth/*
	
	# start unit tests
	phpunit tests

coverage:
	phpunit --coverage-html ./doc/coverage tests/

examples:
	php examples/test.php

doc:
	mkdir -p ${HTMLDIR}
	phpdoc -s --title "php-restauth documentation" -o HTML:Smarty:PHP -dn php-restauth -d ${SRCDIR},tutorials -t ${HTMLDIR}

	# fix the most horrible CSS descisions:
	sed -i 's/padding-left:\t\t8px;/padding-left:\t\t20px;/' doc/html/media/style.css

clean:
	rm -rf ${DOCDIR}
