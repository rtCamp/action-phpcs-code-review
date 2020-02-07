# ubuntu:latest at 2019-02-12T19:22:56IST
FROM ubuntu@sha256:7a47ccc3bbe8a451b500d2b53104868b46d60ee8f5b35a24b41a86077c650210

LABEL "com.github.actions.icon"="check-circle"
LABEL "com.github.actions.color"="green"
LABEL "com.github.actions.name"="PHPCS Code Review"
LABEL "com.github.actions.description"="This will run phpcs on PRs"

RUN echo "tzdata tzdata/Areas select Asia" | debconf-set-selections && \
echo "tzdata tzdata/Zones/Asia select Kolkata" | debconf-set-selections

RUN set -eux; \
	apt-get update; \
	DEBIAN_FRONTEND=noninteractive apt-get install -y \
	cowsay \
	git \
	gosu \
	jq \
	php7.2-cli \
	php7.2-curl \
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

RUN wget https://raw.githubusercontent.com/Automattic/vip-go-ci/master/tools-init.sh -O tools-init.sh && \
	bash tools-init.sh && \
	rm -f tools-init.sh

ENV VAULT_VERSION 1.0.2

# Setup Vault
RUN wget https://releases.hashicorp.com/vault/${VAULT_VERSION}/vault_${VAULT_VERSION}_linux_amd64.zip && \
        unzip vault_${VAULT_VERSION}_linux_amd64.zip && \
        rm vault_${VAULT_VERSION}_linux_amd64.zip && \
        mv vault /usr/local/bin/vault

COPY entrypoint.sh main.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/*.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
