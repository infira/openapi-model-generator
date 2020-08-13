error() {
	echo "$1" 1>&2
	exit 1
}

declare domain=$1
if [[ -z "$domain" ]]; then
	error      "\$domain is empty"
fi

domains=("garmin" "gopro" "meremaailm" "intra" "gps24" "nutistuudio" "gpseesti" "oakley")
if [[ ! " ${domains[*]} " == *" $domain "* ]]; then
	error    "Unknown domain '$domain'"
fi

declare -A domainLiveDb
declare -A domainTestDb
declare -A domainLocalDb
declare -A domainFullName

domainLiveDb[garmin]=d79590_lvgrm
domainTestDb[garmin]=d79590_tstgrm
domainLocalDb[garmin]=gws_garmin
domainFullName[garmin]=garmineesti

domainLiveDb[gopro]=d79590_lvgpr
domainTestDb[gopro]=d79590_tstgpr
domainLocalDb[gopro]=gws_gopro
domainFullName[gopro]=prokaamera

domainLiveDb[meremaailm]=d79590_lvmm
domainTestDb[meremaailm]=d79590_tstmm
domainLocalDb[meremaailm]=gws_meremaailm
domainFullName[meremaailm]=meremaailm

domainLiveDb[nutistuudio]=d79590_lvnut
domainTestDb[nutistuudio]=d79590_tstnut
domainLocalDb[nutistuudio]=gws_nutistuudio
domainFullName[nutistuudio]=nutistuudio

domainLiveDb[gps24]=d79590_lvgps24
domainTestDb[gps24]=d79590_tstgps24
domainLocalDb[gps24]=gws_gps24
domainFullName[gps24]=gps24

domainLiveDb[gpseesti]=d79590_lvgpe
domainTestDb[gpseesti]=d79590_tstgpe
domainLocalDb[gpseesti]=gws_gpseesti
domainFullName[gpseesti]=gpseesti

domainLiveDb[intra]=d79590_livint
domainTestDb[intra]=d79590_tstint
domainLocalDb[intra]=gws_intra
domainFullName[intra]=garmineesti

domainLiveDb[oakley]=d79590_lvgpr
domainTestDb[oakley]=d79590_tstoak
domainLocalDb[oakley]=gws_oakley
domainFullName[oakley]=oakley
