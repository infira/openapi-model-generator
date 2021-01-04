source ./inc/header.sh

ssh -t virt79333@DN-68-92.TLL01.ZONEAS.EU <<HERE
	cd /data01/virt79333/domeenid/www.garmineesti.ee/console
	bash dumpDb.sh $domain
	bash dropBetaTables.sh $domain
	sudo mysql ${domainBetaDb[$domain]}  < $domain.structure.sql
	sudo mysql ${domainBetaDb[$domain]}  < $domain.data.sql
	rm -f $domain.structure.sql
	rm -f $domain.data.sql
HERE