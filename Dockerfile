FROM ubuntu:24.04
LABEL "com.github.actions.icon"="check-circle"
LABEL "com.github.actions.color"="green"
LABEL "com.github.actions.name"="PHPCS Code Review"
LABEL "com.github.actions.description"="Run automated code review using PHPCS on your pull requests."
LABEL "org.opencontainers.image.source"="https://github.com/rtCamp/action-phpcs-code-review"

ARG DEFAULT_PHP_VERSION=8.3
ARG PHP_BINARIES_TO_PREINSTALL='7.4 8.0 8.1 8.2 8.3 8.4'

ENV DOCKER_USER=rtbot
ENV ACTION_WORKDIR=/home/$DOCKER_USER
ENV DEBIAN_FRONTEND=noninteractive

RUN useradd -m -s /bin/bash $DOCKER_USER \
  && mkdir -p $ACTION_WORKDIR \
  && chown -R $DOCKER_USER $ACTION_WORKDIR

RUN set -ex \
  && savedAptMark="$(apt-mark showmanual)" \
  && apt-mark auto '.*' > /dev/null \
  && apt-get update && apt-get install -y --no-install-recommends git ca-certificates wget rsync gnupg jq software-properties-common unzip \
  && LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php \
  && apt-get update \
  && for v in $PHP_BINARIES_TO_PREINSTALL; do \
      apt-get install -y --no-install-recommends \
      php"$v" \
      php"$v"-curl \
      php"$v"-tokenizer \
      php"$v"-simplexml \
      php"$v"-xmlwriter; \
    done \
  && update-alternatives --set php /usr/bin/php${DEFAULT_PHP_VERSION} \
  # cleanup
  && rm -f vault_${VAULT_VERSION}_linux_amd64.zip \
  && apt-get remove software-properties-common unzip -y \
  && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
  && { [ -z "$savedAptMark" ] || apt-mark manual $savedAptMark > /dev/null; } \
  && find /usr/local -type f -executable -exec ldd '{}' ';' \
      | awk '/=>/ { print $(NF-1) }' \
      | sort -u \
      | xargs -r dpkg-query --search \
      | cut -d: -f1 \
      | sort -u \
      | xargs -r apt-mark manual \
  && apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false \
  # smoke test
  && for v in $PHP_BINARIES_TO_PREINSTALL; do \
      php"$v" -v; \
    done \
  && php -v \
  && vault -v;

COPY entrypoint.sh main.sh /usr/local/bin/

RUN chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/main.sh

USER $DOCKER_USER

WORKDIR $ACTION_WORKDIR

RUN wget https://raw.githubusercontent.com/Automattic/vip-go-ci/latest/tools-init.sh -O tools-init.sh \
  && bash tools-init.sh \
  && rm -f tools-init.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
