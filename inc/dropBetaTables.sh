declare db=$1
for table in `echo "show tables" | mysql ${db}`;
do
    if [[ ! $table == "Tables_in_${db}" ]]; then
        if [[ $table == "v_"* ]]; then
          DROP_TABLES_STRING+=" drop view ${db}.${table};"
				else
					DROP_TABLES_STRING+=" drop table ${db}.${table};"
        fi
    fi
done
echo $DROP_TABLES_STRING >> drop.sql
mysql ${db} < drop.sql
rm -f drop.sql