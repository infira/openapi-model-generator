error() {
	echo "$1" 1>&2
	exit 1
}

declare domain=$1
if [[ -z "$domain" ]]; then
	error      "\$domain is empty"
fi

domains=("garmin" "gopro" "meremaailm" "intra" "gps24" "nutistuudio" "gpseesti" "oakley" "garmin_master" "gopro_master" "meremaailm_master" "intra_master" "gps24_master" "master_nutistuudio" "gpseesti_master" "oakley_master")
if [[ ! " ${domains[*]} " == *" $domain "* ]]; then
	error    "Unknown domain '$domain'"
fi

declare -A domainLiveDb
declare -A domainBetaDb
declare -A domainLocalDb
declare -A domainFullName
declare -A localDomainName

domainLiveDb[garmin]=d79590_lvgrm
domainBetaDb[garmin]=d79590_betgrm
domainLocalDb[garmin]=gws_garmin
domainFullName[garmin]=garmineesti
localDomainName[garmin]=garmin

domainLiveDb[gopro]=d79590_lvgpr
#domainBetaDb[gopro]=d79590_lvgpr
domainLocalDb[gopro]=gws_gopro
domainFullName[gopro]=prokaamera
localDomainName[gopro]=gopro

domainLiveDb[meremaailm]=d79590_lvmm
#domainBetaDb[meremaailm]=d79590_lvmm
domainLocalDb[meremaailm]=gws_meremaailm
domainFullName[meremaailm]=meremaailm
localDomainName[meremaailm]=meremaailm

domainLiveDb[nutistuudio]=d79590_lvnut
#domainBetaDb[nutistuudio]=d79590_lvnut
domainLocalDb[nutistuudio]=gws_nutistuudio
domainFullName[nutistuudio]=nutistuudio
localDomainName[nutistuudio]=nutistuudio

domainLiveDb[gps24]=d79590_lvgps24
#domainBetaDb[gps24]=d79590_lvgps24
domainLocalDb[gps24]=gws_gps24
domainFullName[gps24]=gps24
localDomainName[gps24]=gps24

domainLiveDb[gpseesti]=d79590_lvgpe
#domainBetaDb[gpseesti]=d79590_lvgpe
domainLocalDb[gpseesti]=gws_gpseesti
domainFullName[gpseesti]=gpseesti
localDomainName[gpseesti]=gpseesti

domainLiveDb[oakley]=d79590_lvgpr
#domainBetaDb[oakley]=d79590_lvgpr
domainLocalDb[oakley]=gws_oakley
domainFullName[oakley]=oakstore
localDomainName[oakley]=oakley

domainLiveDb[intra]=d79590_livint
domainBetaDb[intra]=d79590_intbeta
domainLocalDb[intra]=gws_intra
domainFullName[intra]=intra
localDomainName[intra]=intra


domainLiveDb[garmin_master]=d79590_lvgrm
domainLocalDb[garmin_master]=gws_master_garmin
domainFullName[garmin_master]=garmineesti
localDomainName[garmin_master]=garmin

domainLiveDb[gopro_master]=d79590_lvgpr
domainLocalDb[gopro_master]=gws_master_gopro
domainFullName[gopro_master]=prokaamera
localDomainName[gopro_master]=gopro

domainLiveDb[meremaailm_master]=d79590_lvmm
domainLocalDb[meremaailm_master]=gws_master_meremaailm
domainFullName[meremaailm_master]=meremaailm
localDomainName[meremaailm_master]=meremaailm

domainLiveDb[master_nutistuudio]=d79590_lvnut
domainLocalDb[master_nutistuudio]=gws_master_nutistuudio
domainFullName[master_nutistuudio]=nutistuudio
localDomainName[master_nutistuudio]=nutistuudio

domainLiveDb[gps24_master]=d79590_lvgps24
domainLocalDb[gps24_master]=gws_master_gps24
domainFullName[gps24_master]=gps24
localDomainName[gps24_master]=gps24

domainLiveDb[gpseesti_master]=d79590_lvgpe
domainLocalDb[gpseesti_master]=gws_master_gpseesti
domainFullName[gpseesti_master]=gpseesti
localDomainName[gpseesti_master]=gpseesti

domainLiveDb[oakley_master]=d79590_lvgpr
domainLocalDb[oakley_master]=gws_master_oakley
domainFullName[oakley_master]=oakstore
localDomainName[oakley_master]=oakley

domainLiveDb[intra_master]=d79590_livint
domainLocalDb[intra_master]=gws_master_intra
domainFullName[intra_master]=intra
localDomainName[intra_master]=intra