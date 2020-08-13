for table in `echo "show tables" | sudo mysql ${domainLocalDb[$domain]}`;
do
    if [[ ! $table == "Tables_in_${domainLocalDb[$domain]}" ]]; then
        if [[ $table == "v_"* ]]; then
          DROP_TABLES_STRING+=" drop view ${domainLocalDb[$domain]}.${table};"
				else
					DROP_TABLES_STRING+=" drop table ${domainLocalDb[$domain]}.${table};"
        fi
    fi
done
echo $DROP_TABLES_STRING# >> drop.sql
sudo mysql ${domainLocalDb[$domain]} < drop.sql
rm -f drop.sql