SRCDIR=src/
DOCDIR=doc/
HTMLDIR=${DOCDIR}/html

doc:
	mkdir -p ${HTMLDIR}
	phpdoc --title "php-restauth documentation" -o HTML:Smarty:PHP -dn php-restauth -d ${SRCDIR} -t ${HTMLDIR}

clean:
	rm -rf ${DOCDIR}
