# ubuntu:latest at 2023-02-24 IST
FROM ubuntu@sha256:9a0bdde4188b896a372804be2384015e90e3f84906b750c1a53539b585fbbe7f

LABEL "com.github.actions.icon"="check-circle"
LABEL "com.github.actions.color"="green"
LABEL "com.github.actions.name"="PHPCS Code Review"
LABEL "com.github.actions.description"="This will run phpcs on PRs"
LABEL "org.opencontainers.image.source"="https://github.com/rtCamp/action-phpcs-code-review"

COPY entrypoint.sh main.sh bin/* /usr/local/bin/

RUN chmod +x /usr/local/bin/*.sh

ENV TZ=Asia/Kolkata
ENV DEBIAN_FRONTEND=noninteractive

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN apt-get update && \
	apt-get install -y \
	software-properties-common \
	cowsay \
	git \
	gosu \
	jq \
	python3 \
	python3-pip \
	rsync \
	sudo \
	tree \
	vim \
	zip \
	unzip \
	wget && \
	pip install shyaml

# verify that the binary works
RUN gosu nobody true

RUN /usr/local/bin/setup-php.sh

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

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]