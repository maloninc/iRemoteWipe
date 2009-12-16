VERSION=0.9.0
PACKAGE=iremote-wipe-$(VERSION)

help:
	@echo 
	@echo "[How to make]"
	@echo "make install   : install iRemoteWipe"
	@echo "make uninstall : uninstall iRemoteWipe"
	@echo 

dist:
	mkdir -p $(PACKAGE)
	cp HOW-TO-INSTALL README Makefile configure config.patch ldap-sample.php uninstall.sh.in wiper.php debug.sh.in \
    wipe_table.sql z-push-1.3RC.tar.gz httpd.patch.in setup.sh.in wipectl $(PACKAGE)/
	(export COPYFILE_DISABLE=1; tar zcvf $(PACKAGE).tar.gz ./$(PACKAGE)/)
	rm -fvr $(PACKAGE)/

install:
	./setup.sh

uninstall:
	./uninstall.sh

clean:
	rm -fv debug.sh setup.sh uninstall.sh httpd.patch
