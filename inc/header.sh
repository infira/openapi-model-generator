error() {
	echo "$1" 1>&2
	exit 1
}

declare domain=$1
if [[ -z "$domain" ]]; then
	error      "\$domain is empty"
fi

domains=("intra" "garmin" "gopro" "gps24" "gpseest" "luxify" "meremaailm" "miiego" "nutistuudio" "oakley")
if [[ ! " ${domains[*]} " == *" $domain "* ]]; then
	error    "Unknown domain '$domain'"
fi

declare -A domainLiveDb
declare -A domainLocalDb

domainLiveDb[intra]=d79590_livint
domainLocalDb[intra]=gws_NAME_intra

domainLiveDb[garmin]=d79590_lvgrm
domainLocalDb[garmin]=gws_NAME_garmin

domainLiveDb[gopro]=d79590_lvgpr
domainLocalDb[gopro]=gws_NAME_gopro

domainLiveDb[gps24]=d79590_lvgps24
domainLocalDb[gps24]=gws_NAME_gps24

domainLiveDb[gpseesti]=d79590_luxify
domainLocalDb[gpseesti]=gws_NAME_luxify

domainLiveDb[gpseesti]=d79590_lvgpe
domainLocalDb[gpseesti]=gws_NAME_gpseesti

domainLiveDb[meremaailm]=d79590_lvmm
domainLocalDb[meremaailm]=gws_NAME_meremaailm

domainLiveDb[miiego]=d79590_miiego
domainLocalDb[miiego]=gws_NAME_miiego

domainLiveDb[nutistuudio]=d79590_lvnut
domainLocalDb[nutistuudio]=gws_NAME_nutistuudio

domainLiveDb[oakley]=d79590_oakley
domainLocalDb[oakley]=gws_NAME_oakley

