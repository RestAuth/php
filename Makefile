SRCDIR=RestAuth/
DOCDIR=doc/
HTMLDIR=${DOCDIR}/html

all: 

test:
	phpunit tests

test-standards:
	-phpcs --report-width=120 tests/* RestAuth/*

coverage:
	phpunit --coverage-html ./doc/coverage tests/

examples:
	php examples/test.php

install:
	pear install -f package.xml

doc:
	mkdir -p ${HTMLDIR}
	phpdoc -ue -s --title "php-restauth documentation" -o HTML:frames:default -dn php-restauth -d ${SRCDIR},tutorials -t ${HTMLDIR}
#	sed -i 's/\tpadding-left:\t\t8px;/\tpadding-left:\t\t12px;/' doc/html/media/style.css
#	sed -i 's/\tmargin-left:\t\t0px;/\tmargin-left:\t\t8px;/' doc/html/media/style.css

clean:
	rm -rf ${DOCDIR}
