if [[ $(/usr/bin/id -u) -ne 0 ]]; then
  echo "Not running as root"
  exit
fi

git clone --depth=1 --branch=master https://github.com/Tjstretchalot/USLBot-Site ~/website_tmp
rsync -avh ~/website_tmp/html /var/www/html
rsync -avh ~/website_tmp/includes /var/www/includes
