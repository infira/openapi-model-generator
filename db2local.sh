source ./inc/header.sh
liveDB=${domainLiveDb[$domain]}
localDB=${domainLocalDb[$domain]}


ssh -t virt79333@DN-68-92.TLL01.ZONEAS.EU <<HERE
	cd /data01/virt79333/domeenid/www.garmineesti.ee/console
	bash inc/dumpDb.sh $liveDB
HERE

cd /Users/gentaliaru/Work/Vagrant/dev/syncedFolder/
rsync -av --progress virt79333@DN-68-92.TLL01.ZONEAS.EU:/data01/virt79333/domeenid/www.garmineesti.ee/console/*.sql ./

ssh -t vagrant@192.168.33.10 <<HERE
	cd ~/sharedFolder/gws_console/
	bash inc/dropLocalTables.sh $localDB
	sudo mysql $localDB  < ~/sharedFolder/$liveDB.structure.sql
	sudo mysql $localDB  < ~/sharedFolder/$liveDB.data.sql
HERE
rm -f /Users/gentaliaru/Work/Vagrant/dev/syncedFolder/$liveDB.structure.sql
rm -f /Users/gentaliaru/Work/Vagrant/dev/syncedFolder/$liveDB.data.sql

ssh -t virt79333@DN-68-92.TLL01.ZONEAS.EU <<HERE
	rm -f /data01/virt79333/domeenid/www.garmineesti.ee/console/$liveDB.structure.sql
	rm -f /data01/virt79333/domeenid/www.garmineesti.ee/console/$liveDB.data.sql
HERE