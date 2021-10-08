declare db=$1
# shellcheck disable=SC2006
for table in $(echo "show tables" | sudo mysql ${db}); do
  if [[ ! $table == "Tables_in_${db}" ]]; then
    if [[ $table == "v_"* ]]; then
      DROP_TABLES_STRING+=" drop view ${db}.${table};"
    else
      DROP_TABLES_STRING+=" drop table ${db}.${table};"
    fi
  fi
done
declare dropPath="../tmp/${db}.drop.sql"
echo "${DROP_TABLES_STRING}" >>"${dropPath}"
sudo mysql "${db}" <"${dropPath}"
rm -f "${dropPath}"
