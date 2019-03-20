# ubuntu:latest at 2019-02-12T19:22:56IST
FROM ubuntu@sha256:7a47ccc3bbe8a451b500d2b53104868b46d60ee8f5b35a24b41a86077c650210

LABEL "com.github.actions.icon"="code"
LABEL "com.github.actions.color"="purple"
LABEL "com.github.actions.name"="PHPCS Inspection"
LABEL "com.github.actions.description"="This will run phpcs on PRs"

RUN echo "tzdata tzdata/Areas select Asia" | debconf-set-selections && \
echo "tzdata tzdata/Zones/Asia select Kolkata" | debconf-set-selections

RUN set -eux; \
	apt-get update; \
	DEBIAN_FRONTEND=noninteractive apt-get install -y \
	git \
	gosu \
	php7.2-cli \
	php7.2-curl \
	php-xml \
	python \
	python-pip \
	rsync \
	sudo \
	tree \
	vim \
	wget ; \
	pip install shyaml; \
	rm -rf /var/lib/apt/lists/*; \
	# verify that the binary works
	gosu nobody true

RUN useradd -m -s /bin/bash rtbot

RUN wget https://raw.githubusercontent.com/Automattic/vip-go-ci/master/tools-init.sh -O tools-init.sh && \
	bash tools-init.sh && \
	rm -f tools-init.sh

COPY entrypoint.sh main.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/*.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
