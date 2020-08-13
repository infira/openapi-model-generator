echo "Dumping ${domainLiveDb[$domain]} db"

for table in `echo "show tables" | mysql ${domainLiveDb[$domain]}`;
do
    if [[ ! $table == "Tables_in_${domainLiveDb[$domain]}" ]]; then
        if [[ $table == "v_"* ]]; then
          IGNORED_TABLES_STRING+=" --ignore-table=${domainLiveDb[$domain]}.${table}"
        fi
    fi
done

echo "Dump structure"
mysqldump --add-drop-table --skip-triggers --single-transaction --no-data $IGNORED_TABLES_STRING ${domainLiveDb[$domain]} > $domain.structure.sql
sed -i 's/DEFINER=[^*]*\*/\*/g' $domain.structure.sql

echo "Dump content"
mysqldump --add-drop-trigger --skip-triggers --no-create-info --lock-tables=false --skip-lock-tables ${IGNORED_TABLES_STRING} --ignore-table=${domainLiveDb[$domain]}.db_log --ignore-table=${domainLiveDb[$domain]}.session --ignore-table=${domainLiveDb[$domain]}.sent_emails --ignore-table=${domainLiveDb[$domain]}.log ${domainLiveDb[$domain]} > $domain.data.sql
sed -i 's/DEFINER=[^*]*\*/\*/g' $domain.data.sql