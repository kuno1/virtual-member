#!/usr/bin/env bash

# Get install Path
cd $(wp-env install-path)

# Reload Apache flag
RELOAD=false

# Install PHP Excimer Extension
if [[ $(docker compose exec -it -u root wordpress php -m | grep excimer) != "excimer" ]]; then

    echo "Installing: Excimer Extension."
    docker compose exec -it -u root wordpress bash -c "apt-get update && apt-get install -y php-dev && pecl install excimer"
	docker compose exec -it -u root wordpress bash -c "echo 'extension=excimer.so' > /usr/local/etc/php/conf.d/excimer.ini"
    if [[ $(docker compose exec -it -u root wordpress php -m | grep excimer) == "excimer" ]]; then
        echo "Excimer Extension: Installed."
    else
        echo "Excimer Extension: Failed."
    fi

    RELOAD=true
fi

# Reload Apache
if [[ $RELOAD == true ]]; then
    docker compose exec -it -u root wordpress service apache2 reload
fi
