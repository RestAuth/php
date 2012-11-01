SRCDIR=RestAuth/
DOCDIR=doc/
HTMLDIR=${DOCDIR}/html
RELEASE=$(shell grep -m 1 release package.xml | sed 's/\s*<release>\(.*\)<\/release>\s*/\1/')

all: 

test:
	phpunit tests

test-standards:
	-phpcs --report-width=120 tests/* RestAuth/*

coverage: ${HTMLDIR}/coverage/index.html
${HTMLDIR}/coverage/index.html:
	phpunit --coverage-html ./doc/coverage tests/

examples:
	php examples/test.php

install:
	pear install -f package.xml

pear-package:
	pear package

doc: ${HTMLDIR}/index.html
${HTMLDIR}/index.html:
	mkdir -p ${HTMLDIR}
	phpdoc -ue -s --title "php-restauth documentation" -o HTML:frames:default -dn php-restauth -d ${SRCDIR},tutorials -t ${HTMLDIR}

clean:
	rm -rf ${DOCDIR}
	
release:
	git checkout ${RELEASE}
	tar --exclude-vcs --xform 's/^./php-restauth-${RELEASE}/' -czf ../php-restauth-${RELEASE}.tar.gz .
	git checkout master
