SRCDIR=src/
DOCDIR=doc/

doc:
	phpdoc -o HTML:frames:earthli -d ${SRCDIR} -t ${DOCDIR}

clean:
	rm -rf ${DOCDIR}
