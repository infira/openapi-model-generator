source ./inc/header.sh
liveDB=${domainLiveDb[$domain]}
localDB=${domainLocalDb[$domain]}

declare branch=$2
if [[ -z "$branch" ]]; then
	declare branch='_live_';
else
  declare branch="_${branch}_";
fi
localDB="${localDB/_NAME_/$branch}"

ssh -t virt79333@DN-68-92.TLL01.ZONEAS.EU <<HERE
	cd /data01/virt79333/domeenid/www.garmineesti.ee/console
	bash inc/dumpDb.sh $liveDB
HERE

rsync -av --progress --del virt79333@DN-68-92.TLL01.ZONEAS.EU:/data01/virt79333/domeenid/www.garmineesti.ee/console/*.sql ~/ws/vagrantSyncedFolder

ssh -t vagrant@192.168.33.10 <<HERE
	cd /var/www/git/gws/gws_console/
	bash inc/dropLocalTables.sh $localDB
	sudo mysql $localDB < ~/host/$liveDB.structure.sql
	sudo mysql $localDB < ~/host/$liveDB.data.sql
HERE

rm -f ~/ws/vagrantSyncedFolder/$liveDB.structure.sql
rm -f ~/ws/vagrantSyncedFolder/$liveDB.data.sql

ssh -t virt79333@DN-68-92.TLL01.ZONEAS.EU <<HERE
	rm -f /data01/virt79333/domeenid/www.garmineesti.ee/console/$liveDB.structure.sql
	rm -f /data01/virt79333/domeenid/www.garmineesti.ee/console/$liveDB.data.sql
HERE


