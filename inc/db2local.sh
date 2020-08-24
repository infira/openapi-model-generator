source ./inc/header.sh

ssh -t virt79333@DN-68-92.TLL01.ZONEAS.EU <<HERE
	cd /data01/virt79333/domeenid/www.garmineesti.ee/console
	bash dumpDb.sh $domain
HERE

cd /Users/gentaliaru/Work/Vagrant/dev/syncedFolder/
rsync -av --progress virt79333@DN-68-92.TLL01.ZONEAS.EU:/data01/virt79333/domeenid/www.garmineesti.ee/console/*.sql ./

ssh -t vagrant@192.168.33.10 <<HERE
	cd ~/sharedFolder/gws_console/
	bash dropLocalTables.sh $domain
	sudo mysql ${domainLocalDb[$domain]}  < ~/sharedFolder/$domain.structure.sql
	sudo mysql ${domainLocalDb[$domain]}  < ~/sharedFolder/$domain.data.sql
HERE
rm -f /Users/gentaliaru/Work/Vagrant/dev/syncedFolder/$domain.structure.sql
rm -f /Users/gentaliaru/Work/Vagrant/dev/syncedFolder/$domain.data.sql

ssh -t virt79333@DN-68-92.TLL01.ZONEAS.EU <<HERE
	rm -f /data01/virt79333/domeenid/www.garmineesti.ee/console/$domain.structure.sql
	rm -f /data01/virt79333/domeenid/www.garmineesti.ee/console/$domain.data.sql
HERE


if [[ $domain == "intra" ]]; then
	curl "http://local.gws.intra/index.php?install=db&sub=views"
else
	curl "http://local.gws.${localDomainName[$domain]}/?install=db&sub=updates&reset=0"
	curl "http://local.gws.${localDomainName[$domain]}/?install=db&sub=views"
fi