#!/bin/bash
declare db=$1
# shellcheck disable=SC2006
for table in `echo "show tables" | mysql "${db}"`;
do
    if [[ ! $table == "Tables_in_${db}" ]]; then
        if [[ $table == "v_"* ]]; then
          IGNORED_TABLES_STRING+=" --ignore-table=${db}.${table}"
        fi
    fi
done
declare structurePath="../tmp/${db}.structure.sql"
declare dataPath="../tmp/${db}.data.sql"

echo "Dump ${db} structure"
mysqldump --add-drop-table --skip-triggers --single-transaction --no-data ${IGNORED_TABLES_STRING} ${db} > ${structurePath}
sed -i 's/DEFINER=[^*]*\*/\*/g' "${structurePath}"

echo "Dump ${db} data"
mysqldump --add-drop-trigger --skip-triggers --no-create-info --lock-tables=false --skip-lock-tables ${IGNORED_TABLES_STRING} --ignore-table="${db}.db_log" --ignore-table="${db}.session" --ignore-table="${db}.sent_emails" --ignore-table="${db}.log" ${db} > ${dataPath}
sed -i 's/DEFINER=[^*]*\*/\*/g' "${dataPath}"