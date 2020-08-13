echo "Coping db ${domainLiveDb[$domain]} to ${domainTestDb[$domain]}"

for table in `echo "show tables" | mysql ${domainTestDb[$domain]}`;
do
    if [[ ! $table == "Tables_in_${domainTestDb[$domain]}" ]]; then
        if [[ $table == "v_"* ]]; then
          DROP_TABLES_STRING+=" drop view ${domainTestDb[$domain]}.${table};"
				else
					DROP_TABLES_STRING+=" drop table ${domainTestDb[$domain]}.${table};"
        fi
    fi
done

rm -rf drop.sql
echo $DROP_TABLES_STRING >> drop.sql


echo "Droping old tables"
mysql ${domainTestDb[$domain]} < drop.sql

source inc/dumpDb.sh

echo "Importing structure"
mysql ${domainTestDb[$domain]} < $domain.structure.sql
rm -rf $domain.structure.sql

echo "Importing data"
mysql ${domainTestDb[$domain]} < $domain.data.sql
rm -rf $domain.data.sql


bash autolaoder.sh $domain

if [[ $domain == "intra" ]]; then
	echo "Installing updates"
	curl "https://test.intra.garmineesti.ee/index.php?install=db&sub=updates&isSystem=1&minOutput=1"
	printf "\n"

	echo "Installing views"
	curl "https://test.intra.garmineesti.ee/index.php?install=db&sub=views&minOutput=1"
	printf "\n"

	echo "Installing views"
	curl "https://test.intra.garmineesti.ee/index.php?install=db&sub=databaseClasses&minOutput=1"
	printf "\n"
else
	echo "Installing updates"
	curl "https://test:vRsz8nEU6bu25N2YVBekvke7htKbNQ@test.${domainFullName[$domain]}.ee/?install=db&sub=updates&reset=0&minOutput=1"
	printf "\n"


	echo "Installing views"
	curl "https://test:vRsz8nEU6bu25N2YVBekvke7htKbNQ@test.${domainFullName[$domain]}.ee/?install=db&sub=views&minOutput=1"
	printf "\n"


	echo "Installing db classes"
	curl "https://test:vRsz8nEU6bu25N2YVBekvke7htKbNQ@test.${domainFullName[$domain]}.ee/?install=db&sub=databaseClasses&minOutput=1"
	printf "\n"
fi

bash autolaoder.sh $domain