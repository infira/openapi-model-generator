source ./inc/header.sh
liveDB=${domainLiveDb[$domain]}
betaDB=${domainBetaDb[$domain]}
ssh -t virt79333@DN-68-92.TLL01.ZONEAS.EU <<HERE
	cd /data01/virt79333/domeenid/www.garmineesti.ee/console/
	bash inc/dropBetaTables.sh $betaDB
	bash inc/dumpDb.sh $liveDB
	echo "Importing $betaDB structure"
	mysql $betaDB < $liveDB.structure.sql
	echo "Importing $betaDB data"
	mysql $betaDB < $liveDB.data.sql
	rm -f $liveDB.structure.sql
	rm -f $liveDB.data.sql
HERE