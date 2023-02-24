# ubuntu:latest at 2023-02-24
FROM ubuntu@sha256:9a0bdde4188b896a372804be2384015e90e3f84906b750c1a53539b585fbbe7f

LABEL "com.github.actions.icon"="check-circle"
LABEL "com.github.actions.color"="green"
LABEL "com.github.actions.name"="PHPCS Code Review"
LABEL "com.github.actions.description"="This will run phpcs on PRs"
LABEL "org.opencontainers.image.source"="https://github.com/rtCamp/action-phpcs-code-review"

RUN echo "tzdata tzdata/Areas select Asia" | debconf-set-selections && \
echo "tzdata tzdata/Zones/Asia select Kolkata" | debconf-set-selections

RUN set -eux; \
	apt-get update; \
	apt install software-properties-common -y && \
	add-apt-repository ppa:ondrej/php && \
	DEBIAN_FRONTEND=noninteractive apt-get install -y \
	cowsay \
	git \
	gosu \
	jq \
	php7.4-cli \
	php7.4-common \
	php7.4-curl \
	php7.4-json \
	php7.4-mbstring \
	php7.4-mysql \
	php7.4-xml \
	php7.4-zip \
	php-xml \
	python \
	python-pip \
	rsync \
	sudo \
	tree \
	vim \
	zip \
	unzip \
	wget ; \
	pip install shyaml; \
	rm -rf /var/lib/apt/lists/*; \
	# verify that the binary works
	gosu nobody true

RUN useradd -m -s /bin/bash rtbot

RUN wget https://raw.githubusercontent.com/Automattic/vip-go-ci/main/tools-init.sh -O tools-init.sh && \
	bash tools-init.sh && \
	rm -f tools-init.sh

ENV VAULT_VERSION 1.4.3

# Setup Vault
RUN wget https://releases.hashicorp.com/vault/${VAULT_VERSION}/vault_${VAULT_VERSION}_linux_amd64.zip && \
        unzip vault_${VAULT_VERSION}_linux_amd64.zip && \
        rm vault_${VAULT_VERSION}_linux_amd64.zip && \
        mv vault /usr/local/bin/vault

COPY entrypoint.sh main.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/*.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
