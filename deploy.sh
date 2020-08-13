ssh -t virt79333@DN-68-92.TLL01.ZONEAS.EU <<HERE
 cd /data01/virt79333/domeenid/www.garmineesti.ee/www/cms
 git pull
 cd GVICMS
 composer update
HERE
echo "Installing autolaoder"
curl "https://www.garmineesti.ee/?install=1&sub=autoloader&minOutput=1"

echo "Installing autoloaderDomain"
curl "https://www.garmineesti.ee/?install=autoloaderDomain&domain=all&minOutput=1"
printf "\n"

echo "Installing updates"
curl "https://www.garmineesti.ee/?install=db&sub=updates&reset=0&domain=all&minOutput=1"
printf "\n"

echo "Installing views"
curl "https://www.garmineesti.ee/?install=db&sub=views&domain=all&minOutput=1"
printf "\n"

echo "Installing jsCssVersion"
curl "https://www.garmineesti.ee/?install=setNewJsCssVersion&domain=all&minOutput=1"
printf "\n"

echo "Flushing casche"
curl "https://www.garmineesti.ee/?install=flushCache&domain=all&minOutput=1"
printf "\n"

echo "Installing autoloaderDomain"
curl "https://www.garmineesti.ee/?install=autoloaderDomain&domain=all&minOutput=1"
