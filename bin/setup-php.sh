#!/usr/bin/env bash

set -ex

DEFAULT_PHP_VERSION="8.0"

PRE_INSTALLATION_CANDIDATES=(
    "7.4"
    "8.0"
    "8.1"
    "8.2"
)

REQUIRED_EXTENSIONS=(
    "cli"
    "common" 
    "curl" 
    "mbstring" 
    "mysql" 
    "xml" 
    "zip" 
)

# Set ppa:ondrej/php repository
add-apt-repository ppa:ondrej/php -y
apt-get update -y

ALLOWED_PHP_VERSIONS=($(apt-cache showpkg php | grep -oP "php\d\.\d" | grep -oP "\d\.\d" | sort -u))

echo -e "Allowed PHP versions: ${ALLOWED_PHP_VERSIONS[*]}"

for PHP_VERSION in "${PRE_INSTALLATION_CANDIDATES[@]}"; do
    if [[ " ${ALLOWED_PHP_VERSIONS[@]} " =~ " ${PHP_VERSION} " ]]; then
        echo -e "Installing PHP version $PHP_VERSION"

        CMD=(apt-get install -y)

        for EXTENSION in "${REQUIRED_EXTENSIONS[@]}"; do
            CMD+=("php$PHP_VERSION-$EXTENSION")
        done

        if [[ "$PHP_VERSION" < "8.0" ]]; then
            # Install json extension only for PHP < 8.0 as it is embedded in PHP >= 8.0
            CMD+=("php$PHP_VERSION-json")
        fi

        "${CMD[@]}"
    else
        echo "PHP version $PHP_VERSION is not available in the ppa:ondrej/php repository."
    fi
done

SET_PHP_EXECUTABLE=$(which "php$DEFAULT_PHP_VERSION")

if [[ ! -f "$SET_PHP_EXECUTABLE" ]]; then
    echo -e "PHP is not installed. Please check the logs above."
    exit 1
fi

if [[ "$CURRENT_PHP_EXECUTABLE" != "$SET_PHP_EXECUTABLE" ]]; then
    echo -e "Switching to PHP version $DEFAULT_PHP_VERSION"
    
    if [[ ! -f $(which php) ]]; then
        update-alternatives --install /usr/bin/php php "$SET_PHP_EXECUTABLE" 1
    else
        update-alternatives --set php "$SET_PHP_EXECUTABLE"
    fi
fi

# echo all installed PHP versions
for PHP_VERSION in "${ALLOWED_PHP_VERSIONS[@]}"; do
    echo -e "Installed PHP version $PHP_VERSION"
    
    if [[ -f $(which "php$PHP_VERSION") ]]; then
        php$PHP_VERSION -v
    else
        echo -e "PHP version $PHP_VERSION is not installed."
    fi
done

# cleanup
apt-get autoremove -y
apt-get autoclean -y
rm -rf /var/lib/apt/lists/*
