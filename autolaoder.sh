source ./inc/header.sh

if [[ $domain == "intra" ]]; then
    echo "Installing $domain autolaoder"
    curl "https://test.intra.garmineesti.ee/index.php?install=autoloader&minOutput=1"
    printf "\n"
else
    echo "Installing $domain autolaoder"
    curl "https://test:vRsz8nEU6bu25N2YVBekvke7htKbNQ@test.${domainFullName[$domain]}.ee/?install=autoloader&minOutput=1"
    printf "\n"
fi