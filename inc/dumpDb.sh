declare db=$1
echo "Dumping ${db} db"
for table in `echo "show tables" | mysql ${db}`;
do
    if [[ ! $table == "Tables_in_${db}" ]]; then
        if [[ $table == "v_"* ]]; then
          IGNORED_TABLES_STRING+=" --ignore-table=${db}.${table}"
        fi
    fi
done

echo "Dump $db structure"
mysqldump --add-drop-table --skip-triggers --single-transaction --no-data $IGNORED_TABLES_STRING $db > $db.structure.sql
sed -i 's/DEFINER=[^*]*\*/\*/g' $db.structure.sql

echo "Dump $db data"
mysqldump --add-drop-trigger --skip-triggers --no-create-info --lock-tables=false --skip-lock-tables ${IGNORED_TABLES_STRING} --ignore-table=$db.db_log --ignore-table=$db.session --ignore-table=$db.sent_emails --ignore-table=$db.log $db > $db.data.sql
sed -i 's/DEFINER=[^*]*\*/\*/g' $db.data.sql