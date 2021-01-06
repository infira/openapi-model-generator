source ./inc/header.sh
liveDB=${domainLiveDb[$domain]}
betaDB=${domainBetaDb[$domain]}
bash inc/dropBetaTables.sh $betaDB
bash inc/dumpDb.sh $liveDB
echo "Importing $betaDB structure"
mysql $betaDB < $liveDB.structure.sql
echo "Importing $betaDB data"
mysql $betaDB < $liveDB.data.sql
rm -f $liveDB.structure.sql
rm -f $liveDB.data.sql