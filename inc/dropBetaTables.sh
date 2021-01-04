for table in `echo "show tables" | sudo mysql ${domainBetaDb[$domain]}`;
do
    if [[ ! $table == "Tables_in_${domainBetaDb[$domain]}" ]]; then
        if [[ $table == "v_"* ]]; then
          DROP_TABLES_STRING+=" drop view ${domainBetaDb[$domain]}.${table};"
				else
					DROP_TABLES_STRING+=" drop table ${domainBetaDb[$domain]}.${table};"
        fi
    fi
done
echo $DROP_TABLES_STRING# >> drop.sql
sudo mysql ${domainBetaDb[$domain]} < drop.sql
rm -f drop.sql