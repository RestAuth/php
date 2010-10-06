SRCDIR=src/
DOCDIR=doc/

doc:
	phpdoc --title "php-restauth documentation" -o HTML:Smarty:PHP -dn php-restauth -d ${SRCDIR} -t ${DOCDIR}

clean:
	rm -rf ${DOCDIR}
