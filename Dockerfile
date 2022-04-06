FROM php:8.1-cli-bullseye

LABEL "com.github.actions.icon"="check-circle"
LABEL "com.github.actions.color"="green"
LABEL "com.github.actions.name"="PHPCS Code Review"
LABEL "com.github.actions.description"="This will run phpcs on PRs"
LABEL "org.opencontainers.image.source"="https://github.com/fatfaldog/action-phpcs-code-review"

RUN set -eux; \
	apt-get update; \
	apt install software-properties-common -y && \
	DEBIAN_FRONTEND=noninteractive apt-get install -y \
	git \
	wget \
	zip \
	unzip \
	gosu \
	jq \
	rsync \
	cowsay \
	;

RUN useradd -m -s /bin/bash fatfaldog

ENV VIP_GO_CI_VER 1.2.2
RUN wget https://raw.githubusercontent.com/Automattic/vip-go-ci/${VIP_GO_CI_VER}/tools-init.sh -O tools-init.sh && \
	bash tools-init.sh && \
	rm -f tools-init.sh && \
	rm -f ~/vip-go-ci-tools/vip-go-ci/main.php

COPY rewrite/main.php /home/fatfaldog/vip-go-ci-tools/vip-go-ci/

ENV VAULT_VERSION 1.9.4

# Setup Vault
RUN wget https://releases.hashicorp.com/vault/${VAULT_VERSION}/vault_${VAULT_VERSION}_linux_amd64.zip && \
        unzip vault_${VAULT_VERSION}_linux_amd64.zip && \
        rm vault_${VAULT_VERSION}_linux_amd64.zip && \
        mv vault /usr/local/bin/vault

COPY entrypoint.sh main.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/*.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
