#!/usr/bin/env bash
USERNAME="webcomcl_lendo"
HOST="127.0.0.1"
DB_PASSWORD='vcm2$1septTb'
DB_NAME="lendo_ebdb"
FILE='lendo_ebdb.sql.zip'
while [[ $# -gt 0 ]]
do
key="$1"

case $key in
    -u|--username)
    USERNAME="$2"
    shift # past argument
    ;;
    -h|--host)
    HOST="$2"
    shift # past argument
    ;;
    -d|--db_name)
    DB_NAME="$2"
    ;;
    -f|--file_name)
    FILE="$2"
    ;;
    *)
            # unknown option
    ;;
esac
shift # past argument or value
done
#./fedeploy.sh -b new-improvements-local -e acl -f
echo USERNAME   = "${USERNAME}"
echo HOST       = "${HOST}"
echo DB_NAME    = "${DB_NAME}"
echo FILE       = "${FILE}"

php ./import.php  "${HOST}" "${USERNAME}" "${DB_PASSWORD}" "${DB_NAME}" "${FILE}"
#'id=${}&url=http://bkjbezjnkelnkz.com'
